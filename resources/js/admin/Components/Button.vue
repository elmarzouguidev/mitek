<script setup>
defineProps({
    type: { type: String, default: 'button' },
    variant: { type: String, default: 'primary' },
    processing: { type: Boolean, default: false },
});

const variants = {
    primary: 'bg-brand-700 text-white hover:bg-brand-800 focus-visible:outline-brand-700',
    secondary: 'bg-white text-slate-700 ring-1 ring-inset ring-slate-300 hover:bg-slate-50 focus-visible:outline-brand-700',
    danger: 'bg-red-600 text-white hover:bg-red-700 focus-visible:outline-red-600',
};
</script>

<template>
    <button
        :type="type"
        :disabled="processing"
        :class="variants[variant]"
        class="inline-flex items-center justify-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold shadow-xs transition focus-visible:outline-2 focus-visible:outline-offset-2 disabled:cursor-not-allowed disabled:opacity-60"
    >
        <slot />
    </button>
</template>
