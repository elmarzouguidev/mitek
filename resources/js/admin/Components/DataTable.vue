<script setup>
import { computed, ref, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';

/**
 * Server-side table: search, sorting, filters and pagination all go through the URL query string,
 * so the state survives reloads and can be shared. `paginator` is a Laravel resource collection.
 */
const props = defineProps({
    columns: { type: Array, required: true },
    paginator: { type: Object, required: true },
    query: { type: Object, default: () => ({}) },
    searchPlaceholder: { type: String, default: 'Rechercher…' },
    searchable: { type: Boolean, default: true },
    selectable: { type: Boolean, default: false },
    emptyText: { type: String, default: 'Aucun résultat.' },
});

const selected = defineModel('selected', { type: Array, default: () => [] });

const search = ref(props.query.q ?? '');
let searchTimer = null;

const rows = computed(() => props.paginator.data);
const meta = computed(() => props.paginator.meta);
const pageLinks = computed(() => (meta.value?.links ?? []).filter((link) => link.url !== null || link.active));

const allSelected = computed(() => rows.value.length > 0 && rows.value.every((row) => selected.value.includes(row.id)));

function apply(patch = {}) {
    const params = { ...props.query, ...patch };

    if (!('page' in patch)) {
        delete params.page;
    }

    Object.keys(params).forEach((key) => {
        if (params[key] === null || params[key] === '' || params[key] === undefined) {
            delete params[key];
        }
    });

    router.get(window.location.pathname, params, { preserveState: true, preserveScroll: true, replace: true });
}

function sortBy(column) {
    if (!column.sortable) {
        return;
    }

    const isCurrent = props.query.sort === column.key;
    apply({ sort: column.key, direction: isCurrent && props.query.direction !== 'desc' ? 'desc' : 'asc' });
}

function toggleAll() {
    selected.value = allSelected.value ? [] : rows.value.map((row) => row.id);
}

watch(search, (value) => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => apply({ q: value }), 300);
});

watch(rows, () => {
    selected.value = selected.value.filter((id) => rows.value.some((row) => row.id === id));
});
</script>

<template>
    <div class="rounded-xl bg-white shadow-xs ring-1 ring-slate-200">
        <div class="flex flex-wrap items-center gap-3 border-b border-slate-200 p-4">
            <input
                v-if="searchable"
                v-model="search"
                type="search"
                :placeholder="searchPlaceholder"
                class="w-full max-w-xs rounded-lg border-0 px-3 py-2 text-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-brand-600"
            />
            <slot name="filters" :query="query" :apply="apply" />
            <div v-if="selected.length" class="ml-auto flex items-center gap-2 text-sm text-slate-600">
                <span>{{ selected.length }} sélectionné(s)</span>
                <slot name="bulk" :selected="selected" />
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th v-if="selectable" class="w-10 px-4 py-3">
                            <input type="checkbox" :checked="allSelected" class="rounded text-brand-700 focus:ring-brand-600" aria-label="Tout sélectionner" @change="toggleAll" />
                        </th>
                        <th
                            v-for="column in columns"
                            :key="column.key"
                            scope="col"
                            :class="[column.align === 'right' ? 'text-right' : '', column.sortable ? 'cursor-pointer select-none hover:text-slate-700' : '']"
                            class="px-4 py-3 font-medium"
                            @click="sortBy(column)"
                        >
                            {{ column.label }}
                            <span v-if="query.sort === column.key" aria-hidden="true">{{ query.direction === 'desc' ? '↓' : '↑' }}</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-for="row in rows" :key="row.id" class="hover:bg-slate-50/60">
                        <td v-if="selectable" class="px-4 py-3">
                            <input v-model="selected" type="checkbox" :value="row.id" class="rounded text-brand-700 focus:ring-brand-600" :aria-label="`Sélectionner ${row.name ?? row.id}`" />
                        </td>
                        <td v-for="column in columns" :key="column.key" :class="column.align === 'right' ? 'text-right' : ''" class="px-4 py-3 align-middle">
                            <slot :name="`cell-${column.key}`" :row="row">{{ row[column.key] }}</slot>
                        </td>
                    </tr>
                    <tr v-if="!rows.length">
                        <td :colspan="columns.length + (selectable ? 1 : 0)" class="px-4 py-10 text-center text-slate-500">{{ emptyText }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="meta && meta.last_page > 1" class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 px-4 py-3 text-sm text-slate-600">
            <p>{{ meta.from }}–{{ meta.to }} sur {{ meta.total }}</p>
            <nav class="flex flex-wrap gap-1" aria-label="Pagination">
                <component
                    :is="link.url ? Link : 'span'"
                    v-for="(link, index) in pageLinks"
                    :key="index"
                    :href="link.url ?? undefined"
                    :preserve-scroll="true"
                    :preserve-state="true"
                    :class="link.active ? 'bg-brand-700 text-white' : link.url ? 'bg-white text-slate-700 ring-1 ring-inset ring-slate-300 hover:bg-slate-50' : 'text-slate-400'"
                    class="rounded-lg px-3 py-1.5 text-sm font-medium"
                    v-html="link.label"
                />
            </nav>
        </div>
        <p v-else-if="meta" class="border-t border-slate-200 px-4 py-3 text-sm text-slate-600">{{ meta.total }} résultat(s)</p>
    </div>
</template>
