<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import Badge from '../../Components/Badge.vue';
import ConfirmDialog from '../../Components/ConfirmDialog.vue';
import PageHeader from '../../Components/PageHeader.vue';

defineProps({
    services: { type: Array, required: true },
});

const pendingDelete = ref(null);

function confirmDelete() {
    router.delete(route('admin.services.destroy', pendingDelete.value.id), { onFinish: () => (pendingDelete.value = null) });
}
</script>

<template>
    <Head title="Services" />

    <PageHeader title="Services informatiques" subtitle="Chaque service a sa page et son formulaire de demande sur le site.">
        <Link :href="route('admin.services.create')" class="rounded-lg bg-brand-700 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-800">Nouveau service</Link>
    </PageHeader>

    <div class="overflow-x-auto rounded-xl bg-white shadow-xs ring-1 ring-slate-200">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-3 font-medium">Ordre</th>
                    <th class="px-4 py-3 font-medium">Service</th>
                    <th class="px-4 py-3 font-medium">Demandes</th>
                    <th class="px-4 py-3 font-medium">Statut</th>
                    <th class="px-4 py-3 text-right font-medium">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <tr v-for="service in services" :key="service.id">
                    <td class="px-4 py-3">{{ service.sort_order }}</td>
                    <td class="px-4 py-3"><span class="font-medium text-slate-900">{{ service.title }}</span><span class="ml-2 text-xs text-slate-400">/services/{{ service.slug }}</span></td>
                    <td class="px-4 py-3">{{ service.requests_count }}</td>
                    <td class="px-4 py-3"><Badge :tone="service.is_active ? 'green' : 'slate'">{{ service.is_active ? 'Actif' : 'Inactif' }}</Badge></td>
                    <td class="px-4 py-3 text-right">
                        <Link :href="route('admin.services.edit', service.id)" class="font-medium text-brand-700 hover:underline">Modifier</Link>
                        <button type="button" class="ml-4 font-medium text-red-600 hover:underline" @click="pendingDelete = service">Supprimer</button>
                    </td>
                </tr>
                <tr v-if="!services.length"><td colspan="5" class="px-4 py-10 text-center text-slate-500">Aucun service.</td></tr>
            </tbody>
        </table>
    </div>

    <ConfirmDialog
        :show="pendingDelete !== null"
        :title="`Supprimer « ${pendingDelete?.title} » ?`"
        message="Les demandes déjà reçues sont conservées (le titre du service est mémorisé)."
        confirm-label="Supprimer"
        danger
        @confirm="confirmDelete"
        @cancel="pendingDelete = null"
    />
</template>
