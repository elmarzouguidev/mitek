<script setup>
import { Head, Link } from '@inertiajs/vue3';
import DataTable from '../../Components/DataTable.vue';
import PageHeader from '../../Components/PageHeader.vue';
import Select from '../../Components/Select.vue';
import StatusBadge from '../../Components/StatusBadge.vue';

defineProps({
    callbacks: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    options: { type: Object, required: true },
});

const columns = [
    { key: 'name', label: 'Nom' },
    { key: 'phone', label: 'Téléphone' },
    { key: 'subject', label: 'Sujet' },
    { key: 'time_slot', label: 'Créneau' },
    { key: 'status', label: 'Statut' },
    { key: 'created_at', label: 'Date', sortable: true },
];
</script>

<template>
    <Head title="Demandes de rappel" />

    <PageHeader title="Demandes de rappel" />

    <DataTable :columns="columns" :paginator="callbacks" :query="filters" search-placeholder="Nom ou téléphone…" empty-text="Aucune demande de rappel.">
        <template #filters="{ query, apply }">
            <div class="w-52"><Select :model-value="query.status ?? ''" :options="options.statuses" placeholder="Tous les statuts" @update:model-value="apply({ status: $event })" /></div>
        </template>

        <template #cell-name="{ row }"><Link :href="route('admin.callbacks.show', row.id)" class="font-medium text-brand-700 hover:underline">{{ row.name }}</Link></template>
        <template #cell-phone="{ row }"><a :href="`tel:${row.phone}`" class="text-slate-700 hover:underline">{{ row.phone }}</a></template>
        <template #cell-status="{ row }"><StatusBadge :status="row.status" /></template>
    </DataTable>
</template>
