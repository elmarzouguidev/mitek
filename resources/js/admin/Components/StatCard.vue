<script setup>
defineProps({
    label: { type: String, required: true },
    value: { type: [String, Number], required: true },
});
</script>

<template>
    <div class="rounded-xl bg-white p-5 shadow-xs ring-1 ring-slate-200">
        <p class="text-sm font-medium text-slate-500">{{ label }}</p>
        <p class="mt-2 text-3xl font-semibold tracking-tight text-slate-900">{{ value }}</p>
    </div>
</template>
