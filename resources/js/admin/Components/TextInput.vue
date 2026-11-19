<script setup>
defineProps({
    id: { type: String, default: null },
    type: { type: String, default: 'text' },
    invalid: { type: Boolean, default: false },
});

const model = defineModel({ type: [String, Number], default: '' });
</script>

<template>
    <input
        :id="id"
        v-model="model"
        :type="type"
        :class="invalid ? 'ring-red-400 focus:ring-red-500' : 'ring-slate-300 focus:ring-brand-600'"
        class="block w-full rounded-lg border-0 px-3 py-2 text-sm text-slate-900 shadow-xs ring-1 ring-inset placeholder:text-slate-400 focus:ring-2 focus:ring-inset"
    />
</template>
