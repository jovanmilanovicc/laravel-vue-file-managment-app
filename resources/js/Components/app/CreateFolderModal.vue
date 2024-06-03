<template>
    <modal
        :show="modelValue"
        @show="onShow"
        max-width="xl"
        @update:show="closeModal"
    >
        <div class="p-6">
            <h2 class="text-lg font-medium text-gray-900">Create new folder</h2>
            <div class="mt-6">
                <InputLabel
                    for="folderName"
                    value="Folder name"
                    class="sr-only"
                />
                <TextInput
                    type="text"
                    id="folderName"
                    v-model="form.name"
                    class="mt-1 block w-full"
                    ref="folderNameInput"
                    :class="
                        form.errors.name
                            ? 'border-red-500 focus:border-red-500 focus:ring-red-500'
                            : ''
                    "
                    placeholder="Folder name"
                    @keyup.enter="createFolder"
                />
                <InputError :message="form.errors.name" class="mt-2" />
            </div>
            <div class="mt-6 flex justify-end">
                <SecondaryButton @click="closeModal">Cancel</SecondaryButton>
                <PrimaryButton
                    class="ml-3"
                    :class="{ 'opacity-25': form.processing }"
                    @click="createFolder"
                    :disabled="form.processing"
                    >Create</PrimaryButton
                >
            </div>
        </div>
    </modal>
</template>

<script setup>
import Modal from "@/Components/Modal.vue";
import TextInput from "@/Components/TextInput.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import { ref, nextTick } from "vue";
import { useForm } from "@inertiajs/vue3";
import PrimaryButton from "../PrimaryButton.vue";

const form = useForm({
    name: "",
});

const folderNameInput = ref(null);

const { modelValue } = defineProps({
    modelValue: Boolean,
});

const emit = defineEmits(["update:modelValue"]);

function createFolder() {
    form.post(route("folder.create"), {
        preserveScroll: true,
        onSuccess: () => {
            closeModal();
            form.reset();
            //show success message
        },
        onError: () => {
            folderNameInput.value.focus();
        },
    });
}

function onShow() {
    nextTick(() => {
        folderNameInput.value.focus();
    });
}

function closeModal() {
    emit("update:modelValue", false);
    form.clearErrors();
    form.reset();
}
</script>
