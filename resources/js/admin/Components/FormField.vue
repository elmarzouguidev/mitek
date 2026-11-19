<script setup>
defineProps({
    label: { type: String, required: true },
    for: { type: String, default: null },
    error: { type: String, default: null },
    hint: { type: String, default: null },
});
</script>

<template>
    <div>
        <label :for="$props.for" class="block text-sm font-medium text-slate-700">{{ label }}</label>
        <div class="mt-1">
            <slot />
        </div>
        <p v-if="hint && !error" class="mt-1 text-xs text-slate-500">{{ hint }}</p>
        <p v-if="error" class="mt-1 text-xs text-red-600">{{ error }}</p>
    </div>
</template>
