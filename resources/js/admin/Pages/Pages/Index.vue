<script setup>
import { Head, Link } from '@inertiajs/vue3';
import Badge from '../../Components/Badge.vue';
import PageHeader from '../../Components/PageHeader.vue';

defineProps({
    pages: { type: Array, required: true },
});
</script>

<template>
    <Head title="Pages" />

    <PageHeader title="Pages du site" subtitle="À propos, paiement, livraison, pages légales… Leur adresse est fixe ; vous modifiez le contenu et la publication." />

    <div class="overflow-x-auto rounded-xl bg-white shadow-xs ring-1 ring-slate-200">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-3 font-medium">Page</th>
                    <th class="px-4 py-3 font-medium">Statut</th>
                    <th class="px-4 py-3 font-medium">Modifiée le</th>
                    <th class="px-4 py-3 text-right font-medium">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <tr v-for="page in pages" :key="page.id">
                    <td class="px-4 py-3 font-medium text-slate-900">{{ page.title }}</td>
                    <td class="px-4 py-3"><Badge :tone="page.is_published ? 'green' : 'amber'">{{ page.is_published ? 'Publiée' : 'Brouillon' }}</Badge></td>
                    <td class="px-4 py-3 text-slate-600">{{ page.updated_at }}</td>
                    <td class="px-4 py-3 text-right">
                        <a :href="page.url" target="_blank" rel="noopener" class="font-medium text-slate-600 hover:underline">Voir</a>
                        <Link :href="route('admin.pages.edit', page.id)" class="ml-4 font-medium text-brand-700 hover:underline">Modifier</Link>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
