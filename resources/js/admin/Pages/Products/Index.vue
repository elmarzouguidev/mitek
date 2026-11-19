<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import Badge from '../../Components/Badge.vue';
import Button from '../../Components/Button.vue';
import ConfirmDialog from '../../Components/ConfirmDialog.vue';
import DataTable from '../../Components/DataTable.vue';
import PageHeader from '../../Components/PageHeader.vue';
import Select from '../../Components/Select.vue';

defineProps({
    products: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    options: { type: Object, required: true },
});

const columns = [
    { key: 'thumb', label: '' },
    { key: 'name', label: 'Produit', sortable: true },
    { key: 'category', label: 'Catégorie' },
    { key: 'brand', label: 'Marque' },
    { key: 'price_ht', label: 'Prix HT', sortable: true, align: 'right' },
    { key: 'stock_quantity', label: 'Stock', sortable: true, align: 'right' },
    { key: 'is_active', label: 'Statut' },
    { key: 'actions', label: '', align: 'right' },
];

const statusOptions = [
    { id: 'active', name: 'Actifs' },
    { id: 'inactive', name: 'Inactifs' },
];

const stockOptions = [
    { id: 'low', name: 'Stock bas' },
    { id: 'out', name: 'Rupture' },
];

const selected = ref([]);
const pendingDelete = ref(null);
const processing = ref(false);

function bulk(action) {
    router.post(
        route('admin.products.bulk'),
        { action, ids: selected.value },
        {
            preserveScroll: true,
            onStart: () => (processing.value = true),
            onSuccess: () => (selected.value = []),
            onFinish: () => (processing.value = false),
        },
    );
}

function confirmDelete() {
    router.delete(route('admin.products.destroy', pendingDelete.value.id), {
        onFinish: () => (pendingDelete.value = null),
    });
}
</script>

<template>
    <Head title="Produits" />

    <PageHeader title="Produits">
        <Link :href="route('admin.products.create')" class="rounded-lg bg-brand-700 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-800">Nouveau produit</Link>
    </PageHeader>

    <DataTable v-model:selected="selected" :columns="columns" :paginator="products" :query="filters" selectable search-placeholder="Nom ou référence…" empty-text="Aucun produit ne correspond.">
        <template #filters="{ query, apply }">
            <div class="w-44"><Select :model-value="query.category_id ?? ''" :options="options.categories" placeholder="Toutes les catégories" @update:model-value="apply({ category_id: $event })" /></div>
            <div class="w-40"><Select :model-value="query.brand_id ?? ''" :options="options.brands" placeholder="Toutes les marques" @update:model-value="apply({ brand_id: $event })" /></div>
            <div class="w-32"><Select :model-value="query.status ?? ''" :options="statusOptions" placeholder="Statut" @update:model-value="apply({ status: $event })" /></div>
            <div class="w-32"><Select :model-value="query.stock ?? ''" :options="stockOptions" placeholder="Stock" @update:model-value="apply({ stock: $event })" /></div>
        </template>

        <template #bulk>
            <Button variant="secondary" :processing="processing" @click="bulk('activate')">Activer</Button>
            <Button variant="secondary" :processing="processing" @click="bulk('deactivate')">Désactiver</Button>
        </template>

        <template #cell-thumb="{ row }">
            <img v-if="row.thumb" :src="row.thumb" alt="" class="size-10 rounded object-cover" />
            <div v-else class="size-10 rounded bg-slate-100" />
        </template>
        <template #cell-name="{ row }">
            <Link :href="route('admin.products.edit', row.id)" class="font-medium text-slate-900 hover:text-brand-700">{{ row.name }}</Link>
            <p class="text-xs text-slate-500">{{ row.reference }}</p>
        </template>
        <template #cell-category="{ row }">{{ row.category }}</template>
        <template #cell-brand="{ row }">{{ row.brand ?? '—' }}</template>
        <template #cell-stock_quantity="{ row }">
            <Badge :tone="row.stock_quantity === 0 ? 'red' : row.is_low_stock ? 'amber' : 'green'">{{ row.stock_quantity }}</Badge>
        </template>
        <template #cell-is_active="{ row }">
            <Badge :tone="row.is_active ? 'green' : 'slate'">{{ row.is_active ? 'Actif' : 'Inactif' }}</Badge>
            <Badge v-if="row.is_featured" tone="blue" class="ml-1">Vedette</Badge>
        </template>
        <template #cell-actions="{ row }">
            <Link :href="route('admin.products.edit', row.id)" class="font-medium text-brand-700 hover:underline">Modifier</Link>
            <button type="button" class="ml-4 font-medium text-red-600 hover:underline" @click="pendingDelete = row">Supprimer</button>
        </template>
    </DataTable>

    <ConfirmDialog
        :show="pendingDelete !== null"
        :title="`Supprimer « ${pendingDelete?.name} » ?`"
        message="Le produit disparaît du site et de l'administration (suppression logique)."
        confirm-label="Supprimer"
        danger
        @confirm="confirmDelete"
        @cancel="pendingDelete = null"
    />
</template>
