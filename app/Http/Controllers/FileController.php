<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddToFavoritesRequest;
use App\Http\Requests\FilesActionRequest;
use App\Http\Requests\ShareFilesRequest;
use App\Http\Requests\StoreFileRequest;
use App\Http\Requests\StoreFolderRequest;
use App\Http\Requests\TrashFilesRequest;
use App\Http\Resources\FileResource;
use Illuminate\Http\Request;
use App\Models\File;
use App\Models\FileShare;
use App\Models\StaredFile;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Illuminate\Support\Str;
use ZipArchive;

class FileController extends Controller
{
    public function myFiles(Request $request, string $folder = null)
    {
        try {
            if ($folder) {
                $folder = File::query()->where("created_by", Auth::id())
                    ->where("path", $folder)
                    ->first();
            }
            if (!$folder) {
                $folder = $this->getRoot();
            }

            $favourites = (int)$request->get('favourites');

            $query = File::query()
                ->select("files.*")
                ->with('starred')
                ->where("parent_id", $folder->id)
                ->where("created_by", Auth::id())
                ->orderBy('is_folder', 'desc')
                ->orderBy("files.created_at", 'desc')
                ->orderBy("files.id", "desc");
            if ($favourites == 1) {
                $query->join("stared_files", "stared_files.id", "files.id")
                    ->where('stared_files.user_id', Auth::id());
            }

            $files = $query->paginate(10);

            $files = FileResource::collection($files);

            if ($request->wantsJson()) {
                return $files;
            }

            $ancestors = FileResource::collection([...$folder->ancestors, $folder]);

            $folder = new FileResource($folder);

            return Inertia::render('MyFiles', compact('files', 'folder', 'ancestors'));
        } catch (\Exception $e) {
            dd($e->getMessage());
        }
    }

    public function trash(Request $request)
    {
        $files = File::onlyTrashed()
            ->where('created_by', Auth::id())
            ->orderBy('is_folder', 'desc')
            ->orderBy("deleted_at", "desc")
            ->paginate(10);

        $files = FileResource::collection($files);
        if ($request->wantsJson()) {
            return $files;
        }

        return Inertia::render('Trash', compact("files"));
    }

    public function createFolder(StoreFolderRequest $request)
    {
        $data = $request->validated();
        $parent = $request->parent;

        if (!$parent) {
            $parent = $this->getRoot();
        }

        $file = new File();
        $file->is_folder = 1;
        $file->name = $data['name'];

        $parent->appendNode($file);
    }

    public function store(StoreFileRequest $request)
    {
        $data = $request->validated();
        $parent = $request->parent;
        $user = $request->user();
        $fileTree = $request->file_tree;

        if (!$parent) {
            $parent = $this->getRoot();
        }

        if (!empty($fileTree)) {
            $this->saveFileTree($fileTree, $parent, $user);
        } else {
            foreach ($data['files'] as $file) {
                $this->saveFile($file, $user, $parent);
            }
        }
    }

    public function destroy(FilesActionRequest $request)
    {
        $data = $request->validated();
        $parent = $request->parent;

        if ($data['all']) {
            $children = $parent->children;
            foreach ($children as $child) {
                $child->moveToTrash();
            }
        } else {
            foreach ($data['ids'] ?? [] as $id) {
                $file = File::find($id);
                if ($file) {
                    $file->moveToTrash();
                }
            }
        }

        return to_route('myFiles', ['folder' => $parent->path]);
    }

    public function dowload(FilesActionRequest $request)
    {
        $data = $request->validated();
        $parent = $request->parent;

        $all = $data['all'] ?? false;
        $ids = $data['ids'] ?? [];

        if (!$all && empty($ids)) {
            return ['message' => 'Please select files to dowload'];
        }

        if ($all) {
            $url = $this->createZip($parent->children);
            $filename = $parent->name . ".zip";
        } else {
            if (count($ids) == 1) {
                $file = File::find($ids[0]);
                if ($file->is_folder) {
                    if ($file->children->count() == 0) {
                        return ['message' => "The folder is empty"];
                    }
                    $url = $this->createZip($file->children);
                    $filename = $file->name . ".zip";
                } else {
                    $destination = 'public/' . pathinfo($file->storage_path, PATHINFO_BASENAME);
                    Storage::copy($file->storage_path, $destination);

                    $url = asset(Storage::url($destination));
                    $filename = $file->name;
                }
            } else {
                $files = File::query()->whereIn('id', $ids)->get();
                $url = $this->createZip($files);

                $filename = $parent->name . ".zip";
            }
        }

        return ["url" => $url, "filename" => $filename];
    }

    private function getRoot()
    {
        return File::query()->whereIsRoot()->where('created_by', Auth::id())->firstOrFail();
    }

    private function saveFileTree($fileTree, $parent, $user)
    {
        foreach ($fileTree as $name => $file) {
            if (is_array($file)) {
                $folder = new File();
                $folder->is_folder = 1;
                $folder->name = $name;

                $parent->appendNode($folder);
                $this->saveFileTree($file, $folder, $user);
            } else {
                $this->saveFile($file, $user, $parent);
            }
        }
    }
    private function saveFile($file, $user, $parent): void
    {
        $path = $file->store('/files/' . $user->id);

        $model = new File();
        $model->storage_path = $path;
        $model->is_folder = false;
        $model->name = $file->getClientOriginalName();
        $model->mime = $file->getMimeType();
        $model->size = $file->getSize();


        $parent->appendNode($model);
    }

    private function createZip($files): string
    {
        $zipPath = "zip/" . Str::random() . ".zip";
        $publicPath = "public/$zipPath";

        if (!is_dir(dirname($publicPath))) {
            Storage::makeDirectory(dirname($publicPath));
        }

        $zipFile = Storage::path($publicPath);

        $zip = new ZipArchive();

        if ($zip->open($zipFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true) {
            $this->addFileToZip($zip, $files);
        }
        $zip->close();
        return asset(Storage::url($zipPath));
    }

    private function addFileToZip($zip, $files, $ancestors = "")
    {
        foreach ($files as $file) {
            if ($file->is_folder) {
                $this->addFileToZip($zip, $file->children, $ancestors . $file->name . "/");
            } else {
                $zip->addFile(Storage::path($file->storage_path), $ancestors . $file->name);
            }
        }
    }
    public function restore(TrashFilesRequest $request)
    {
        $data = $request->validated();

        if ($data['all']) {
            $children = File::onlyTrashed()->get();
            foreach ($children as $child) {
                $child->restore();
            }
        } else {
            $ids = $data['ids'] ?? [];
            $children = File::onlyTrashed()->whereIn('id', $ids)->get();
            foreach ($children as $child) {
                $child->restore();
            }
        }
        return to_route('file.trash');
    }

    public function deleteForever(TrashFilesRequest $request)
    {
        $data = $request->validated();

        if ($data['all']) {
            $children = File::onlyTrashed()->get();
            foreach ($children as $child) {
                $child->deleteForever();
            }
        } else {
            $ids = $data['ids'] ?? [];
            $children = File::onlyTrashed()->whereIn('id', $ids)->get();
            foreach ($children as $child) {
                $child->deleteForever();
            }
        }
        return to_route('file.trash');
    }

    public function addToFavorites(AddToFavoritesRequest $request)
    {
        $data = $request->validated();

        $id = $data['id'];
        $file = File::find($id);
        $user_id = Auth::id();

        $starredFile = StaredFile::query()
            ->where('file_id', $file->id)
            ->where('user_id', $user_id)
            ->first();

        if ($starredFile) {
            $starredFile->delete();
        } else {
            StaredFile::create([
                'file_id' => $file->id,
                'user_id' => $user_id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }

        return redirect()->back();
    }

    public function share(ShareFilesRequest $request)
    {
        $data = $request->validated();
        $parent = $request->parent;

        $all = $data['all'] ?? false;
        $email = $data['email'] ?? false;
        $ids = $data['ids'] ?? [];

        if (!$all && empty($ids)) {
            return [
                'message' => 'Please select files to share'
            ];
        }

        $user = User::query()->where('email', $email)->first();

        if (!$user) {
            return redirect()->back();
        }

        if ($all) {
            $files = $parent->children;
        } else {
            $files = File::find($ids);
        }

        $data = [];
        $ids = Arr::pluck($files, 'id');
        $existingFileIds = FileShare::query()
            ->whereIn('file_id', $ids)
            ->where('user_id', $user->id)
            ->get()
            ->keyBy('file_id');

        foreach ($files as $file) {
            if ($existingFileIds->has($file->id)) {
                continue;
            }
            $data[] = [
                'file_id' => $file->id,
                'user_id' => $user->id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ];
        }
        FileShare::insert($data);

        //Mail::to($user)->send(new ShareFilesMail($user, Auth::user(), $files));

        return redirect()->back();
    }

    public function sharedWithMe(Request $request)
    {
        $search = $request->get('search');
        $query = File::getSharedWithMe();

        if ($search) {
            $query->where('name', 'like', "%$search%");
        }

        $files = $query->paginate(10);

        $files = FileResource::collection($files);

        if ($request->wantsJson()) {
            return $files;
        }

        return Inertia::render('SharedWithMe', compact('files'));
    }

    public function sharedByMe(Request $request)
    {
        $search = $request->get('search');
        $query = File::getSharedByMe();

        if ($search) {
            $query->where('name', 'like', "%$search%");
        }

        $files = $query->paginate(10);
        $files = FileResource::collection($files);

        if ($request->wantsJson()) {
            return $files;
        }

        return Inertia::render('SharedByMe', compact('files'));
    }
}
