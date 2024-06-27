<?php

use App\Http\Controllers\FileController;
use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/my-files/{folder?}', [FileController::class, 'myFiles'])->where('folder', '(.*)')->name("myFiles");
    Route::post('/folder/create', [FileController::class, 'createFolder'])->name("folder.create");
    Route::post('/file', [FileController::class, 'store'])->name("file.store");
    Route::delete('/file', [FileController::class, 'destroy'])->name("file.delete");
    Route::get('/file/dowload', [FileController::class, 'dowload'])->name("file.dowload");
    Route::get('/trash', [FileController::class, 'trash'])->name("file.trash");
    Route::post('/restore', [FileController::class, 'restore'])->name("file.restore");
    Route::delete('/file/delete-forever', [FileController::class, 'deleteForever'])->name("file.deleteForever");
    Route::post('/file/add-to-favorites', [FileController::class, 'addToFavorites'])->name("file.addToFavorites");
    Route::post('/file/share', [FileController::class, 'share'])->name("file.share");
    Route::get('/shared-with-me', 'sharedWithMe')->name('file.sharedWithMe');
    Route::get('/shared-by-me', 'sharedByMe')->name('file.sharedByMe');
});

require __DIR__ . '/auth.php';
