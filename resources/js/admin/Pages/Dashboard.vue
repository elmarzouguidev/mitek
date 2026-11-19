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

    <PageHeader title="Tableau de bord" subtitle="Commandes payées, en préparation, expédiées ou livrées sur les 30 derniers jours." />

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <Link :href="route('admin.orders.index', { status: 'payment_submitted' })" class="block rounded-xl bg-white p-5 shadow-xs ring-1 ring-slate-200 hover:ring-brand-300">
            <p class="text-sm font-medium text-slate-500">Paiements à valider</p>
            <p :class="stats.awaiting_validation ? 'text-brand-700' : 'text-slate-900'" class="mt-2 text-3xl font-semibold tracking-tight">{{ stats.awaiting_validation }}</p>
            <p class="mt-1 text-xs text-slate-500">justificatifs reçus, en attente</p>
        </Link>
        <Link :href="route('admin.quotes.index', { status: 'new' })" class="block rounded-xl bg-white p-5 shadow-xs ring-1 ring-slate-200 hover:ring-brand-300">
            <p class="text-sm font-medium text-slate-500">Devis à traiter</p>
            <p :class="stats.new_quotes ? 'text-brand-700' : 'text-slate-900'" class="mt-2 text-3xl font-semibold tracking-tight">{{ stats.new_quotes }}</p>
            <p class="mt-1 text-xs text-slate-500">demandes sans réponse</p>
        </Link>
        <Link :href="route('admin.service-requests.index', { status: 'new' })" class="block rounded-xl bg-white p-5 shadow-xs ring-1 ring-slate-200 hover:ring-brand-300">
            <p class="text-sm font-medium text-slate-500">Demandes de service</p>
            <p :class="stats.new_service_requests ? 'text-brand-700' : 'text-slate-900'" class="mt-2 text-3xl font-semibold tracking-tight">{{ stats.new_service_requests }}</p>
            <p class="mt-1 text-xs text-slate-500">nouvelles, à traiter</p>
        </Link>
        <Link :href="route('admin.callbacks.index', { status: 'new' })" class="block rounded-xl bg-white p-5 shadow-xs ring-1 ring-slate-200 hover:ring-brand-300">
            <p class="text-sm font-medium text-slate-500">Demandes de rappel</p>
            <p :class="stats.new_callbacks ? 'text-brand-700' : 'text-slate-900'" class="mt-2 text-3xl font-semibold tracking-tight">{{ stats.new_callbacks }}</p>
            <p class="mt-1 text-xs text-slate-500">à rappeler</p>
        </Link>
        <Link :href="route('admin.messages.index', { status: 'unread' })" class="block rounded-xl bg-white p-5 shadow-xs ring-1 ring-slate-200 hover:ring-brand-300">
            <p class="text-sm font-medium text-slate-500">Messages non lus</p>
            <p :class="stats.unread_messages ? 'text-brand-700' : 'text-slate-900'" class="mt-2 text-3xl font-semibold tracking-tight">{{ stats.unread_messages }}</p>
            <p class="mt-1 text-xs text-slate-500">formulaire de contact</p>
        </Link>
        <StatCard label="Commandes à traiter" :value="stats.to_process" />
        <StatCard label="Chiffre d'affaires (30 jours)" :value="stats.revenue_30_days" />
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
