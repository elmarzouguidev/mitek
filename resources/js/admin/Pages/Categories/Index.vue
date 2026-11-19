<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import Badge from '../../Components/Badge.vue';
import ConfirmDialog from '../../Components/ConfirmDialog.vue';
import PageHeader from '../../Components/PageHeader.vue';

defineProps({
    categories: { type: Array, required: true },
});

const pendingDelete = ref(null);

function confirmDelete() {
    router.delete(route('admin.categories.destroy', pendingDelete.value.id), {
        onFinish: () => (pendingDelete.value = null),
    });
}
</script>

<template>
    <Head title="Catégories" />

    <PageHeader title="Catégories" subtitle="Deux niveaux au maximum : catégorie et sous-catégorie.">
        <Link :href="route('admin.categories.create')" class="rounded-lg bg-brand-700 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-800">Nouvelle catégorie</Link>
    </PageHeader>

    <div class="overflow-x-auto rounded-xl bg-white shadow-xs ring-1 ring-slate-200">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-3 font-medium">Nom</th>
                    <th class="px-4 py-3 font-medium">Produits</th>
                    <th class="px-4 py-3 font-medium">Ordre</th>
                    <th class="px-4 py-3 font-medium">Statut</th>
                    <th class="px-4 py-3 text-right font-medium">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <tr v-for="category in categories" :key="category.id">
                    <td class="px-4 py-3" :class="category.is_child ? 'pl-10 text-slate-700' : 'font-semibold text-slate-900'">
                        <span v-if="category.is_child" class="mr-1 text-slate-400" aria-hidden="true">↳</span>{{ category.name }}
                        <span class="ml-2 text-xs font-normal text-slate-400">/{{ category.slug }}</span>
                    </td>
                    <td class="px-4 py-3">{{ category.products_count }}</td>
                    <td class="px-4 py-3">{{ category.sort_order }}</td>
                    <td class="px-4 py-3"><Badge :tone="category.is_active ? 'green' : 'slate'">{{ category.is_active ? 'Active' : 'Inactive' }}</Badge></td>
                    <td class="px-4 py-3 text-right">
                        <Link :href="route('admin.categories.edit', category.id)" class="font-medium text-brand-700 hover:underline">Modifier</Link>
                        <button type="button" class="ml-4 font-medium text-red-600 hover:underline" @click="pendingDelete = category">Supprimer</button>
                    </td>
                </tr>
                <tr v-if="!categories.length">
                    <td colspan="5" class="px-4 py-10 text-center text-slate-500">Aucune catégorie.</td>
                </tr>
            </tbody>
        </table>
    </div>

    <ConfirmDialog
        :show="pendingDelete !== null"
        :title="`Supprimer « ${pendingDelete?.name} » ?`"
        message="Une catégorie qui contient des sous-catégories ou des produits ne peut pas être supprimée."
        confirm-label="Supprimer"
        danger
        @confirm="confirmDelete"
        @cancel="pendingDelete = null"
    />
</template>
