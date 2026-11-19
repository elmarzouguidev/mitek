<script setup>
import Button from './Button.vue';
import Modal from './Modal.vue';

defineProps({
    show: { type: Boolean, default: false },
    title: { type: String, required: true },
    message: { type: String, default: '' },
    confirmLabel: { type: String, default: 'Confirmer' },
    cancelLabel: { type: String, default: 'Annuler' },
    danger: { type: Boolean, default: false },
    processing: { type: Boolean, default: false },
});

defineEmits(['confirm', 'cancel']);
</script>

<template>
    <Modal :show="show" @close="$emit('cancel')">
        <div class="p-6">
            <h2 class="text-lg font-semibold text-slate-900">{{ title }}</h2>
            <p v-if="message" class="mt-2 text-sm text-slate-600">{{ message }}</p>
            <div class="mt-6 flex justify-end gap-3">
                <Button variant="secondary" @click="$emit('cancel')">{{ cancelLabel }}</Button>
                <Button :variant="danger ? 'danger' : 'primary'" :processing="processing" @click="$emit('confirm')">{{ confirmLabel }}</Button>
            </div>
        </div>
    </Modal>
</template>
