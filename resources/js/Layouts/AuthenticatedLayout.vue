<template>
    <div class="min-h-screen bg-gray-100 flex w-full gap-4">
        <Navigation />
        <!-- Page Content -->
        <main
            @drop.prevent="handleDrop"
            @dragover.prevent="onDragOver"
            @dragleave.prevent="onDragLeave"
            class="flex flex-col px-4 flex-1 overflow-hidden min-h-full w-full"
        >
            <div v-if="dragOver" class="text-gray-500 text-center py-8 text-sm">
                Drop Files to upload
            </div>
            <div v-else>
                <div class="px-3 flex items-center justify-between w-full">
                    <SearchForm />
                    <UserSettingsDropdown />
                </div>
                <div class="flex-1 flex flex-col overflow-hidden"><slot /></div>
            </div>
        </main>
    </div>
</template>

<script setup>
import { onMounted, ref } from "vue";
import SearchForm from "@/Components/app/SearchForm.vue";
import Navigation from "@/Components/app/Navigation.vue";
import UserSettingsDropdown from "@/Components/app/UserSettingsDropdown.vue";
import { emitter, FILE_UPLOAD_STARTED } from "@/event-bus";
import { useForm, usePage } from "@inertiajs/vue3";

const page = usePage();

const fileUploadForm = useForm({
    files: [],
    relative_paths: Array,
    parent_id: null,
});

const dragOver = ref(false);

function onDragOver() {
    dragOver.value = true;
}

function onDragLeave() {
    dragOver.value = false;
}

function handleDrop(ev) {
    dragOver.value = false;
    const files = ev.dataTransfer.files;
    if (!files.length) {
        return;
    }

    uploadFiles(files);
}

function uploadFiles(files) {
    fileUploadForm.parent_id = page.props.folder.id;
    fileUploadForm.files = files;
    fileUploadForm.relative_paths = [...files].map(
        (file) => file.webkitRelativePath
    );

    fileUploadForm.post(route("file.store"));
}

onMounted(() => {
    emitter.on(FILE_UPLOAD_STARTED, uploadFiles);
});
</script>

<style scoped>
.dropzone {
    width: 100%;
    height: 100%;
    color: #8d8d8d;
    border: 2px dashed gray;
    display: flex;
    justify-content: center;
    align-items: center;
}
</style>
