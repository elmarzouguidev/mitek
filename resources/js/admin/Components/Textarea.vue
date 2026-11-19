<script setup>
defineProps({
    id: { type: String, default: null },
    rows: { type: Number, default: 4 },
    invalid: { type: Boolean, default: false },
});

const model = defineModel({ type: String, default: '' });
</script>

<template>
    <textarea
        :id="id"
        v-model="model"
        :rows="rows"
        :class="invalid ? 'ring-red-400 focus:ring-red-500' : 'ring-slate-300 focus:ring-brand-600'"
        class="block w-full rounded-lg border-0 px-3 py-2 text-sm text-slate-900 shadow-xs ring-1 ring-inset placeholder:text-slate-400 focus:ring-2 focus:ring-inset"
    />
</template>
