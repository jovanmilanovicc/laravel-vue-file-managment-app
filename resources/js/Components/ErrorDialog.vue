<template>
    <Modal :show="show" max-width="md">
        <div class="p-6">
            <h2 class="text-xl mb-2 text-red-600 font-semibold">Error</h2>
            <div class="mt-6 flex justify-end">
                <PrimaryButton @click.self="close">Ok</PrimaryButton>
            </div>
        </div>
    </Modal>
</template>

<script>
import Modal from "@/Components/Modal.vue";
import { onMounted, ref } from "vue";
import PrimaryButton from "./PrimaryButton.vue";
import emmiter from "@/event-bus.js"

const show = ref(false);
const message = ref("");

const emit = defineEmits(['close']);



const close = () => {
    show.value = false;
    message.value = "";
}

onMounted(() => {
    emmiter.on('SHOW_ERROR_DIALOG');
})

</script>

<style></style>