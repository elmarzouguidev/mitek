<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import Badge from '../../Components/Badge.vue';
import Card from '../../Components/Card.vue';
import ConfirmDialog from '../../Components/ConfirmDialog.vue';
import PageHeader from '../../Components/PageHeader.vue';

defineProps({
    categories: { type: Array, required: true },
});

const pendingDelete = ref(null);

function confirmDelete() {
    const target = pendingDelete.value;
    const url = target.type === 'category' ? route('admin.faq.categories.destroy', target.id) : route('admin.faq.items.destroy', target.id);

    router.delete(url, { preserveScroll: true, onFinish: () => (pendingDelete.value = null) });
}
</script>

<template>
    <Head title="FAQ" />

    <PageHeader title="Questions fréquentes" subtitle="Affichées sur /faq, et sous les pages de paiement, de livraison et de service.">
        <Link :href="route('admin.faq.categories.create')" class="rounded-lg px-4 py-2 text-sm font-semibold text-brand-700 ring-1 ring-inset ring-brand-200 hover:bg-brand-50">Nouvelle catégorie</Link>
        <Link :href="route('admin.faq.items.create')" class="rounded-lg bg-brand-700 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-800">Nouvelle question</Link>
    </PageHeader>

    <div class="space-y-6">
        <Card v-for="category in categories" :key="category.id" :title="category.name" :description="`Identifiant : ${category.slug} · ordre ${category.sort_order}`">
            <ul v-if="category.items.length" class="divide-y divide-slate-100">
                <li v-for="item in category.items" :key="item.id" class="flex items-center justify-between gap-4 py-2.5 text-sm">
                    <span class="min-w-0 truncate text-slate-900">{{ item.question }}</span>
                    <span class="flex shrink-0 items-center gap-3">
                        <Badge :tone="item.is_active ? 'green' : 'slate'">{{ item.is_active ? 'Active' : 'Masquée' }}</Badge>
                        <Link :href="route('admin.faq.items.edit', item.id)" class="font-medium text-brand-700 hover:underline">Modifier</Link>
                        <button type="button" class="font-medium text-red-600 hover:underline" @click="pendingDelete = { type: 'item', id: item.id, label: item.question }">Supprimer</button>
                    </span>
                </li>
            </ul>
            <p v-else class="text-sm text-slate-500">Aucune question dans cette catégorie.</p>
            <template #footer>
                <Link :href="route('admin.faq.categories.edit', category.id)" class="text-sm font-medium text-brand-700 hover:underline">Modifier la catégorie</Link>
                <button type="button" class="text-sm font-medium text-red-600 hover:underline" @click="pendingDelete = { type: 'category', id: category.id, label: category.name }">Supprimer la catégorie</button>
            </template>
        </Card>
        <p v-if="!categories.length" class="text-sm text-slate-500">Aucune catégorie.</p>
    </div>

    <ConfirmDialog
        :show="pendingDelete !== null"
        :title="`Supprimer « ${pendingDelete?.label} » ?`"
        :message="pendingDelete?.type === 'category' ? 'Toutes les questions de cette catégorie seront supprimées.' : 'Cette action est définitive.'"
        confirm-label="Supprimer"
        danger
        @confirm="confirmDelete"
        @cancel="pendingDelete = null"
    />
</template>
