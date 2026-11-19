<script setup>
import { Head, Link } from '@inertiajs/vue3';
import DataTable from '../../Components/DataTable.vue';
import PageHeader from '../../Components/PageHeader.vue';
import Select from '../../Components/Select.vue';
import StatusBadge from '../../Components/StatusBadge.vue';

defineProps({
    requests: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    options: { type: Object, required: true },
});

const columns = [
    { key: 'reference', label: 'Demande', sortable: true },
    { key: 'service', label: 'Service' },
    { key: 'customer', label: 'Client' },
    { key: 'urgency', label: 'Urgence' },
    { key: 'status', label: 'Statut' },
    { key: 'created_at', label: 'Date', sortable: true },
];
</script>

<template>
    <Head title="Demandes de service" />

    <PageHeader title="Demandes de service" />

    <DataTable :columns="columns" :paginator="requests" :query="filters" search-placeholder="Référence, nom, email, service…" empty-text="Aucune demande de service.">
        <template #filters="{ query, apply }">
            <div class="w-52"><Select :model-value="query.status ?? ''" :options="options.statuses" placeholder="Tous les statuts" @update:model-value="apply({ status: $event })" /></div>
        </template>

        <template #cell-reference="{ row }"><Link :href="route('admin.service-requests.show', row.id)" class="font-medium text-brand-700 hover:underline">{{ row.reference }}</Link></template>
        <template #cell-customer="{ row }">{{ row.customer }}<p class="text-xs text-slate-500">{{ row.city }}</p></template>
        <template #cell-status="{ row }"><StatusBadge :status="row.status" /></template>
    </DataTable>
</template>
