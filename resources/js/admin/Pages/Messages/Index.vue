<script setup>
import { Head, Link } from '@inertiajs/vue3';
import Badge from '../../Components/Badge.vue';
import DataTable from '../../Components/DataTable.vue';
import PageHeader from '../../Components/PageHeader.vue';
import Select from '../../Components/Select.vue';

defineProps({
    messages: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    options: { type: Object, required: true },
});

const columns = [
    { key: 'subject', label: 'Message' },
    { key: 'name', label: 'De' },
    { key: 'is_read', label: 'État' },
    { key: 'created_at', label: 'Reçu le' },
];
</script>

<template>
    <Head title="Messages" />

    <PageHeader title="Messages de contact" />

    <DataTable :columns="columns" :paginator="messages" :query="filters" search-placeholder="Nom, email, sujet…" empty-text="Aucun message.">
        <template #filters="{ query, apply }">
            <div class="w-40"><Select :model-value="query.status ?? ''" :options="options.statuses" placeholder="Tous" @update:model-value="apply({ status: $event })" /></div>
        </template>

        <template #cell-subject="{ row }">
            <Link :href="route('admin.messages.show', row.id)" :class="row.is_read ? 'font-normal text-slate-700' : 'font-semibold text-slate-900'" class="hover:text-brand-700">{{ row.subject }}</Link>
        </template>
        <template #cell-name="{ row }">{{ row.name }}<p class="text-xs text-slate-500">{{ row.email }}</p></template>
        <template #cell-is_read="{ row }"><Badge :tone="row.is_read ? 'slate' : 'amber'">{{ row.is_read ? 'Lu' : 'Non lu' }}</Badge></template>
    </DataTable>
</template>
