<script setup>
import { Head, Link } from '@inertiajs/vue3';
import DataTable from '../../Components/DataTable.vue';
import PageHeader from '../../Components/PageHeader.vue';
import Select from '../../Components/Select.vue';
import StatusBadge from '../../Components/StatusBadge.vue';

defineProps({
    quotes: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    options: { type: Object, required: true },
});

const columns = [
    { key: 'reference', label: 'Demande', sortable: true },
    { key: 'customer', label: 'Client' },
    { key: 'items_count', label: 'Articles' },
    { key: 'status', label: 'Statut' },
    { key: 'created_at', label: 'Date', sortable: true },
];
</script>

<template>
    <Head title="Devis" />

    <PageHeader title="Demandes de devis" />

    <DataTable :columns="columns" :paginator="quotes" :query="filters" search-placeholder="Référence, nom, email, entreprise…" empty-text="Aucune demande de devis.">
        <template #filters="{ query, apply }">
            <div class="w-56"><Select :model-value="query.status ?? ''" :options="options.statuses" placeholder="Tous les statuts" @update:model-value="apply({ status: $event })" /></div>
        </template>

        <template #cell-reference="{ row }">
            <Link :href="route('admin.quotes.show', row.id)" class="font-medium text-brand-700 hover:underline">{{ row.reference }}</Link>
        </template>
        <template #cell-customer="{ row }">
            <span class="font-medium text-slate-900">{{ row.customer }}</span>
            <p class="text-xs text-slate-500">{{ row.email }}</p>
        </template>
        <template #cell-status="{ row }"><StatusBadge :status="row.status" /></template>
    </DataTable>
</template>
