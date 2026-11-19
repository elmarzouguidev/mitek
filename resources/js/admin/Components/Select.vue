<script setup>
defineProps({
    id: { type: String, default: null },
    options: { type: Array, required: true },
    placeholder: { type: String, default: null },
    invalid: { type: Boolean, default: false },
    valueKey: { type: String, default: 'id' },
    labelKey: { type: String, default: 'name' },
});

const model = defineModel({ type: [String, Number, null], default: '' });
</script>

<template>
    <select
        :id="id"
        v-model="model"
        :class="invalid ? 'ring-red-400 focus:ring-red-500' : 'ring-slate-300 focus:ring-brand-600'"
        class="block w-full rounded-lg border-0 px-3 py-2 text-sm text-slate-900 shadow-xs ring-1 ring-inset focus:ring-2 focus:ring-inset"
    >
        <option v-if="placeholder !== null" value="">{{ placeholder }}</option>
        <option v-for="option in options" :key="option[valueKey]" :value="option[valueKey]">{{ option[labelKey] }}</option>
    </select>
</template>
