<script setup>
import { Head } from '@inertiajs/vue3';
import StatCard from '../Components/StatCard.vue';

defineProps({
    stats: { type: Object, required: true },
});
</script>

<template>
    <Head title="Tableau de bord" />

    <h1 class="text-2xl font-semibold tracking-tight">Tableau de bord</h1>
    <p class="mt-1 text-sm text-slate-500">Les indicateurs de ventes arriveront avec les commandes (phase 3).</p>

    <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <StatCard label="Clients" :value="stats.customers" />
        <StatCard label="Produits" :value="stats.products" />
    </div>
</template>
