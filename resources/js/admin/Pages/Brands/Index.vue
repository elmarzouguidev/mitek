<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import Badge from '../../Components/Badge.vue';
import ConfirmDialog from '../../Components/ConfirmDialog.vue';
import DataTable from '../../Components/DataTable.vue';
import PageHeader from '../../Components/PageHeader.vue';

defineProps({
    brands: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
});

const columns = [
    { key: 'logo', label: 'Logo' },
    { key: 'name', label: 'Nom', sortable: true },
    { key: 'products_count', label: 'Produits' },
    { key: 'is_active', label: 'Statut' },
    { key: 'actions', label: '', align: 'right' },
];

const pendingDelete = ref(null);

function confirmDelete() {
    router.delete(route('admin.brands.destroy', pendingDelete.value.id), {
        onFinish: () => (pendingDelete.value = null),
    });
}
</script>

<template>
    <Head title="Marques" />

    <PageHeader title="Marques">
        <Link :href="route('admin.brands.create')" class="rounded-lg bg-brand-700 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-800">Nouvelle marque</Link>
    </PageHeader>

    <DataTable :columns="columns" :paginator="brands" :query="filters" search-placeholder="Rechercher une marque…" empty-text="Aucune marque.">
        <template #cell-logo="{ row }">
            <img v-if="row.logo" :src="row.logo" :alt="row.name" class="h-8 w-auto max-w-20 object-contain" />
            <span v-else class="text-slate-300">—</span>
        </template>
        <template #cell-name="{ row }"><span class="font-medium text-slate-900">{{ row.name }}</span></template>
        <template #cell-is_active="{ row }"><Badge :tone="row.is_active ? 'green' : 'slate'">{{ row.is_active ? 'Active' : 'Inactive' }}</Badge></template>
        <template #cell-actions="{ row }">
            <Link :href="route('admin.brands.edit', row.id)" class="font-medium text-brand-700 hover:underline">Modifier</Link>
            <button type="button" class="ml-4 font-medium text-red-600 hover:underline" @click="pendingDelete = row">Supprimer</button>
        </template>
    </DataTable>

    <ConfirmDialog
        :show="pendingDelete !== null"
        :title="`Supprimer « ${pendingDelete?.name} » ?`"
        :message="pendingDelete?.products_count ? `${pendingDelete.products_count} produit(s) perdront leur marque.` : 'Cette action est définitive.'"
        confirm-label="Supprimer"
        danger
        @confirm="confirmDelete"
        @cancel="pendingDelete = null"
    />
</template>
