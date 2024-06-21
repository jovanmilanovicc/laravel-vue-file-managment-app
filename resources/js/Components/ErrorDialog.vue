<template>
    <Modal :show="show" max-width="md">
        <div class="p-6">
            <h2 class="text-xl mb-2 text-red-600 font-semibold">Error</h2>
            <p class="">{{ message }}</p>
            <div class="mt-6 flex justify-end">
                <PrimaryButton @click.self="close">Ok</PrimaryButton>
            </div>
        </div>
    </Modal>
</template>

<script setup>
import Modal from "@/Components/Modal.vue";
import { onMounted, ref } from "vue";
import PrimaryButton from "./PrimaryButton.vue";
import { emitter } from "@/event-bus.js"

const show = ref(false);
const message = ref("");

const emit = defineEmits(['close']);



const close = () => {
    show.value = false;
    message.value = "";
}

onMounted(() => {
    emitter.on('SHOW_ERROR_DIALOG', ({ message: msg }) => {
        show.value = true;
        message.value = msg;
    });
})

</script>

<style></style>