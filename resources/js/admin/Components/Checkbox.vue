<script setup>
defineProps({
    label: { type: String, required: true },
});

const model = defineModel({ type: Boolean, default: false });
</script>

<template>
    <label class="flex items-center gap-2 text-sm text-slate-700">
        <input v-model="model" type="checkbox" class="rounded text-brand-700 focus:ring-brand-600" />
        {{ label }}
    </label>
</template>
