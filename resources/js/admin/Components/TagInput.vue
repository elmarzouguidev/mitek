<script setup>
import { ref } from 'vue';

/**
 * Free-text list (e.g. compatible printer models): Enter or comma adds a tag.
 */
defineProps({
    id: { type: String, default: null },
    placeholder: { type: String, default: 'Ajouter…' },
});

const tags = defineModel({ type: Array, default: () => [] });
const draft = ref('');

function commit() {
    const value = draft.value.replace(/\s+/g, ' ').trim();

    if (value && !tags.value.some((tag) => tag.toLowerCase() === value.toLowerCase())) {
        tags.value = [...tags.value, value];
    }

    draft.value = '';
}

function remove(index) {
    tags.value = tags.value.filter((_, position) => position !== index);
}

function onKeydown(event) {
    if (event.key === 'Enter' || event.key === ',') {
        event.preventDefault();
        commit();
    } else if (event.key === 'Backspace' && !draft.value && tags.value.length) {
        remove(tags.value.length - 1);
    }
}
</script>

<template>
    <div class="flex flex-wrap items-center gap-2 rounded-lg px-2 py-1.5 ring-1 ring-inset ring-slate-300 focus-within:ring-2 focus-within:ring-brand-600">
        <span v-for="(tag, index) in tags" :key="tag" class="inline-flex items-center gap-1 rounded-full bg-brand-50 px-2.5 py-1 text-xs font-medium text-brand-800">
            {{ tag }}
            <button type="button" class="text-brand-600 hover:text-brand-900" :aria-label="`Retirer ${tag}`" @click="remove(index)">×</button>
        </span>
        <input :id="id" v-model="draft" type="text" :placeholder="placeholder" class="min-w-40 flex-1 border-0 bg-transparent p-1 text-sm focus:ring-0" @keydown="onKeydown" @blur="commit" />
    </div>
</template>
