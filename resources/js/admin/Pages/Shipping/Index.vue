<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import Badge from '../../Components/Badge.vue';
import ConfirmDialog from '../../Components/ConfirmDialog.vue';
import PageHeader from '../../Components/PageHeader.vue';

defineProps({
    zones: { type: Array, required: true },
});

const pendingDelete = ref(null);

function confirmDelete() {
    router.delete(route('admin.shipping.destroy', pendingDelete.value.id), { onFinish: () => (pendingDelete.value = null) });
}
</script>

<template>
    <Head title="Livraison" />

    <PageHeader title="Zones de livraison" subtitle="Le tarif dépend de la zone de la ville et du poids total de la commande. Les villes absentes de toute zone utilisent la zone par défaut.">
        <Link :href="route('admin.shipping.create')" class="rounded-lg bg-brand-700 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-800">Nouvelle zone</Link>
    </PageHeader>

    <div class="overflow-x-auto rounded-xl bg-white shadow-xs ring-1 ring-slate-200">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-3 font-medium">Zone</th>
                    <th class="px-4 py-3 font-medium">Villes</th>
                    <th class="px-4 py-3 font-medium">Transit</th>
                    <th class="px-4 py-3 font-medium">Tranches de poids</th>
                    <th class="px-4 py-3 font-medium">Statut</th>
                    <th class="px-4 py-3 text-right font-medium">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <tr v-for="zone in zones" :key="zone.id">
                    <td class="px-4 py-3 font-medium text-slate-900">{{ zone.name }} <Badge v-if="zone.is_default" tone="blue" class="ml-1">Par défaut</Badge></td>
                    <td class="px-4 py-3">{{ zone.cities_count }}</td>
                    <td class="px-4 py-3">{{ zone.transit }}</td>
                    <td class="px-4 py-3">{{ zone.rates_count }}</td>
                    <td class="px-4 py-3"><Badge :tone="zone.is_active ? 'green' : 'slate'">{{ zone.is_active ? 'Active' : 'Inactive' }}</Badge></td>
                    <td class="px-4 py-3 text-right">
                        <Link :href="route('admin.shipping.edit', zone.id)" class="font-medium text-brand-700 hover:underline">Modifier</Link>
                        <button type="button" class="ml-4 font-medium text-red-600 hover:underline" @click="pendingDelete = zone">Supprimer</button>
                    </td>
                </tr>
                <tr v-if="!zones.length"><td colspan="6" class="px-4 py-10 text-center text-slate-500">Aucune zone : les commandes livrées par Mitek.ma sont impossibles tant qu'aucune zone par défaut n'existe.</td></tr>
            </tbody>
        </table>
    </div>

    <ConfirmDialog
        :show="pendingDelete !== null"
        :title="`Supprimer la zone « ${pendingDelete?.name} » ?`"
        message="Les commandes passées gardent leur tarif. Les villes de cette zone utiliseront la zone par défaut."
        confirm-label="Supprimer"
        danger
        @confirm="confirmDelete"
        @cancel="pendingDelete = null"
    />
</template>
