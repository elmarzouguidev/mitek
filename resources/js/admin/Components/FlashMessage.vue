<script setup>
import { computed, ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const visible = ref(true);

const message = computed(() => page.props.flash?.success ?? page.props.flash?.error ?? null);
const isError = computed(() => !page.props.flash?.success && !!page.props.flash?.error);

watch(message, (value) => {
    visible.value = true;

    if (value) {
        setTimeout(() => (visible.value = false), 5000);
    }
});
</script>

<template>
    <div
        v-if="message && visible"
        role="status"
        :class="isError ? 'bg-red-50 text-red-800 ring-red-200' : 'bg-emerald-50 text-emerald-800 ring-emerald-200'"
        class="mb-6 flex items-start justify-between gap-4 rounded-lg px-4 py-3 text-sm ring-1"
    >
        <span>{{ message }}</span>
        <button type="button" class="font-medium opacity-70 hover:opacity-100" aria-label="Fermer" @click="visible = false">×</button>
    </div>
</template>
