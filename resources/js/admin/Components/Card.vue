<script setup>
defineProps({
    title: { type: String, default: null },
    description: { type: String, default: null },
});
</script>

<template>
    <section class="rounded-xl bg-white shadow-xs ring-1 ring-slate-200">
        <header v-if="title" class="border-b border-slate-200 px-5 py-4">
            <h2 class="text-base font-semibold text-slate-900">{{ title }}</h2>
            <p v-if="description" class="mt-1 text-sm text-slate-500">{{ description }}</p>
        </header>
        <div class="p-5">
            <slot />
        </div>
        <footer v-if="$slots.footer" class="flex justify-end gap-3 rounded-b-xl border-t border-slate-200 bg-slate-50 px-5 py-3">
            <slot name="footer" />
        </footer>
    </section>
</template>
