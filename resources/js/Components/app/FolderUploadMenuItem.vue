<template>
    <MenuItem>
        <a href="#" class="text-gray-700 block px-4 py-2 text-sm relative">
            Folder Upload
            <input
                @change="onChange"
                type="file"
                class="absolute top-0 left-0 bottom-0 right-0 opacity-0 cursor-pointer"
                multiple
                directory
                webkitdirectory
            />
        </a>
    </MenuItem>
</template>

<script setup>
import { MenuItem } from "@headlessui/vue";
import { emitter, FILE_UPLOAD_STARTED } from "@/event-bus.js";

function onChange(event) {
    emitter.emit(FILE_UPLOAD_STARTED, event.target.files);
}
</script>

<style></style>
