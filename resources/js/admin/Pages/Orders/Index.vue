<script setup>
import { Head, Link } from '@inertiajs/vue3';
import DataTable from '../../Components/DataTable.vue';
import PageHeader from '../../Components/PageHeader.vue';
import Select from '../../Components/Select.vue';
import StatusBadge from '../../Components/StatusBadge.vue';

defineProps({
    orders: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    options: { type: Object, required: true },
});

const columns = [
    { key: 'reference', label: 'Commande', sortable: true },
    { key: 'customer', label: 'Client' },
    { key: 'payment_method', label: 'Paiement' },
    { key: 'status', label: 'Statut' },
    { key: 'total_ttc', label: 'Total TTC', sortable: true, align: 'right' },
    { key: 'created_at', label: 'Date', sortable: true },
];
</script>

<template>
    <Head title="Commandes" />

    <PageHeader title="Commandes" />

    <DataTable :columns="columns" :paginator="orders" :query="filters" search-placeholder="Référence, nom, email, téléphone…" empty-text="Aucune commande.">
        <template #filters="{ query, apply }">
            <div class="w-52"><Select :model-value="query.status ?? ''" :options="options.statuses" placeholder="Tous les statuts" @update:model-value="apply({ status: $event })" /></div>
            <div class="w-56"><Select :model-value="query.payment_method ?? ''" :options="options.methods" placeholder="Tous les paiements" @update:model-value="apply({ payment_method: $event })" /></div>
        </template>

        <template #cell-reference="{ row }">
            <Link :href="route('admin.orders.show', row.id)" class="font-medium text-brand-700 hover:underline">{{ row.reference }}</Link>
        </template>
        <template #cell-customer="{ row }">
            <span class="font-medium text-slate-900">{{ row.customer }}</span>
            <p class="text-xs text-slate-500">{{ row.email }}</p>
        </template>
        <template #cell-status="{ row }"><StatusBadge :status="row.status" /></template>
        <template #cell-total_ttc="{ row }"><span class="font-medium">{{ row.total_ttc }}</span></template>
    </DataTable>
</template>
