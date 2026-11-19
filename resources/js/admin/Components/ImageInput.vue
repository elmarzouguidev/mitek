<script setup>
import { computed, onBeforeUnmount, ref, watch } from 'vue';

/**
 * Single image field used inside a form (category image, brand logo).
 */
const props = defineProps({
    label: { type: String, required: true },
    currentUrl: { type: String, default: null },
    error: { type: String, default: null },
});

const file = defineModel({ type: [File, null], default: null });
const remove = defineModel('remove', { type: Boolean, default: false });

const preview = ref(null);

watch(file, (value) => {
    if (preview.value) {
        URL.revokeObjectURL(preview.value);
    }

    preview.value = value ? URL.createObjectURL(value) : null;

    if (value) {
        remove.value = false;
    }
});

onBeforeUnmount(() => preview.value && URL.revokeObjectURL(preview.value));

const shownUrl = computed(() => preview.value ?? (remove.value ? null : props.currentUrl));
</script>

<template>
    <div>
        <span class="block text-sm font-medium text-slate-700">{{ label }}</span>
        <div class="mt-2 flex items-center gap-4">
            <div class="flex size-20 items-center justify-center overflow-hidden rounded-lg bg-slate-100 ring-1 ring-slate-200">
                <img v-if="shownUrl" :src="shownUrl" alt="" class="size-full object-contain" />
                <span v-else class="text-xs text-slate-400">Aucune</span>
            </div>
            <div class="space-y-2 text-sm">
                <input type="file" accept="image/jpeg,image/png,image/webp" class="block text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-3 file:py-1.5 file:font-medium file:text-brand-700 hover:file:bg-brand-100" @change="file = $event.target.files[0] ?? null" />
                <label v-if="currentUrl && !file" class="flex items-center gap-2 text-slate-600">
                    <input v-model="remove" type="checkbox" class="rounded text-brand-700 focus:ring-brand-600" /> Supprimer l'image actuelle
                </label>
            </div>
        </div>
        <p v-if="error" class="mt-1 text-xs text-red-600">{{ error }}</p>
    </div>
</template>
