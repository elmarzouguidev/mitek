<script setup>
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import ConfirmDialog from './ConfirmDialog.vue';

/**
 * Multi-image uploader for an existing record: files upload immediately, images can be reordered
 * by drag and drop (the first one is the main image) and deleted.
 */
const props = defineProps({
    images: { type: Array, default: () => [] },
    uploadUrl: { type: String, required: true },
    reorderUrl: { type: String, required: true },
    destroyUrl: { type: Function, required: true },
    max: { type: Number, default: 10 },
    errors: { type: Object, default: () => ({}) },
});

const items = ref([...props.images]);
const uploading = ref(false);
const dragOverZone = ref(false);
const draggedIndex = ref(null);
const pendingDelete = ref(null);
const input = ref(null);

watch(
    () => props.images,
    (images) => (items.value = [...images]),
);

function upload(fileList) {
    const files = [...fileList].filter((file) => file.type.startsWith('image/'));

    if (!files.length) {
        return;
    }

    router.post(
        props.uploadUrl,
        { images: files },
        {
            forceFormData: true,
            preserveScroll: true,
            onStart: () => (uploading.value = true),
            onFinish: () => {
                uploading.value = false;
                if (input.value) {
                    input.value.value = '';
                }
            },
        },
    );
}

function onDropZone(event) {
    dragOverZone.value = false;
    upload(event.dataTransfer.files);
}

function onItemDrop(index) {
    if (draggedIndex.value === null || draggedIndex.value === index) {
        return;
    }

    const reordered = [...items.value];
    const [moved] = reordered.splice(draggedIndex.value, 1);
    reordered.splice(index, 0, moved);
    items.value = reordered;
    draggedIndex.value = null;

    router.put(props.reorderUrl, { ids: reordered.map((image) => image.id) }, { preserveScroll: true });
}

function confirmDelete() {
    router.delete(props.destroyUrl(pendingDelete.value.id), {
        preserveScroll: true,
        onFinish: () => (pendingDelete.value = null),
    });
}
</script>

<template>
    <div>
        <ul v-if="items.length" class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4">
            <li
                v-for="(image, index) in items"
                :key="image.id"
                draggable="true"
                :class="draggedIndex === index ? 'opacity-40' : ''"
                class="group relative aspect-square cursor-grab overflow-hidden rounded-lg bg-slate-50 ring-1 ring-slate-200"
                @dragstart="draggedIndex = index"
                @dragend="draggedIndex = null"
                @dragover.prevent
                @drop.prevent.stop="onItemDrop(index)"
            >
                <img :src="image.url" :alt="image.name" class="size-full object-contain p-2" />
                <span v-if="index === 0" class="absolute left-2 top-2 rounded bg-brand-700 px-1.5 py-0.5 text-xs font-medium text-white">Principale</span>
                <button
                    type="button"
                    class="absolute right-2 top-2 rounded-full bg-white/90 px-2 py-0.5 text-xs font-semibold text-red-600 opacity-0 shadow-xs ring-1 ring-slate-200 focus:opacity-100 group-hover:opacity-100"
                    @click="pendingDelete = image"
                >
                    Supprimer
                </button>
            </li>
        </ul>

        <div
            v-if="items.length < max"
            :class="dragOverZone ? 'border-brand-500 bg-brand-50' : 'border-slate-300'"
            class="mt-3 flex flex-col items-center justify-center rounded-lg border-2 border-dashed px-6 py-8 text-center"
            @dragover.prevent="dragOverZone = true"
            @dragleave="dragOverZone = false"
            @drop.prevent="onDropZone"
        >
            <p class="text-sm text-slate-600">Glissez des images ici ou</p>
            <label class="mt-2 cursor-pointer rounded-lg bg-white px-4 py-2 text-sm font-semibold text-brand-700 ring-1 ring-inset ring-slate-300 hover:bg-slate-50">
                Parcourir
                <input ref="input" type="file" accept="image/jpeg,image/png,image/webp" multiple class="sr-only" @change="upload($event.target.files)" />
            </label>
            <p class="mt-2 text-xs text-slate-500">JPG, PNG ou WebP, 5 Mo max, {{ max }} images au plus. Glissez les vignettes pour changer l'image principale.</p>
            <p v-if="uploading" class="mt-2 text-sm font-medium text-brand-700">Envoi en cours…</p>
        </div>

        <p v-if="errors.images" class="mt-2 text-sm text-red-600">{{ errors.images }}</p>
        <p v-for="(message, key) in errors" v-show="key.startsWith('images.')" :key="key" class="mt-1 text-sm text-red-600">{{ message }}</p>

        <ConfirmDialog
            :show="pendingDelete !== null"
            title="Supprimer cette image ?"
            message="L'image sera définitivement supprimée."
            confirm-label="Supprimer"
            danger
            @confirm="confirmDelete"
            @cancel="pendingDelete = null"
        />
    </div>
</template>
