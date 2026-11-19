<script setup>
import { Head, Link } from '@inertiajs/vue3';
import Badge from '../Components/Badge.vue';
import Card from '../Components/Card.vue';
import PageHeader from '../Components/PageHeader.vue';
import StatCard from '../Components/StatCard.vue';

defineProps({
    stats: { type: Object, required: true },
    lowStockProducts: { type: Array, default: () => [] },
});
</script>

<template>
    <Head title="Tableau de bord" />

    <PageHeader title="Tableau de bord" subtitle="Les indicateurs de ventes arriveront avec les commandes (phase 3)." />

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <StatCard label="Clients" :value="stats.customers" />
        <StatCard label="Produits" :value="stats.products" />
        <Link :href="route('admin.products.index', { stock: 'low' })" class="block rounded-xl bg-white p-5 shadow-xs ring-1 ring-slate-200 hover:ring-brand-300">
            <p class="text-sm font-medium text-slate-500">Stock bas</p>
            <p :class="stats.low_stock ? 'text-amber-600' : 'text-slate-900'" class="mt-2 text-3xl font-semibold tracking-tight">{{ stats.low_stock }}</p>
            <p class="mt-1 text-xs text-slate-500">produits actifs au seuil ou en dessous</p>
        </Link>
    </div>

    <Card v-if="lowStockProducts.length" class="mt-6 max-w-3xl" title="Produits à réapprovisionner" description="Les moins bien approvisionnés en premier.">
        <ul class="divide-y divide-slate-100">
            <li v-for="product in lowStockProducts" :key="product.id" class="flex items-center justify-between gap-4 py-2.5 text-sm">
                <Link :href="route('admin.products.edit', product.id)" class="min-w-0 truncate font-medium text-slate-900 hover:text-brand-700">
                    {{ product.name }} <span class="font-normal text-slate-500">· {{ product.reference }}</span>
                </Link>
                <Badge :tone="product.stock_quantity === 0 ? 'red' : 'amber'">{{ product.stock_quantity === 0 ? 'Rupture' : `${product.stock_quantity} en stock` }} (seuil {{ product.low_stock_threshold }})</Badge>
            </li>
        </ul>
    </Card>
</template>
