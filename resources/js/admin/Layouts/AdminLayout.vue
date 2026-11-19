<script setup>
import { ref } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import FlashMessage from '../Components/FlashMessage.vue';

const page = usePage();
const sidebarOpen = ref(false);

const navigation = [
    { name: 'Tableau de bord', route: 'admin.dashboard', match: 'admin.dashboard' },
    { name: 'Commandes', route: 'admin.orders.index', match: 'admin.orders.*' },
    { name: 'Produits', route: 'admin.products.index', match: 'admin.products.*' },
    { name: 'Catégories', route: 'admin.categories.index', match: 'admin.categories.*' },
    { name: 'Marques', route: 'admin.brands.index', match: 'admin.brands.*' },
    { name: 'Livraison', route: 'admin.shipping.index', match: 'admin.shipping.*' },
    { name: 'Paramètres', route: 'admin.settings.edit', match: 'admin.settings.*' },
];

function logout() {
    router.post(route('logout'));
}
</script>

<template>
    <div class="min-h-screen lg:flex">
        <div v-if="sidebarOpen" class="fixed inset-0 z-30 bg-slate-900/50 lg:hidden" @click="sidebarOpen = false" />

        <aside
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
            class="fixed inset-y-0 left-0 z-40 flex w-64 flex-col bg-brand-950 text-brand-100 transition-transform lg:static lg:shrink-0"
        >
            <div class="flex h-16 items-center px-6 text-lg font-semibold text-white">
                {{ page.props.app.name }}
                <span class="ml-2 rounded bg-brand-700 px-1.5 py-0.5 text-xs font-medium">Admin</span>
            </div>

            <nav class="flex-1 space-y-1 px-3 py-4">
                <Link
                    v-for="item in navigation"
                    :key="item.route"
                    :href="route(item.route)"
                    :class="route().current(item.match) ? 'bg-brand-800 text-white' : 'text-brand-200 hover:bg-brand-900 hover:text-white'"
                    class="block rounded-lg px-3 py-2 text-sm font-medium"
                    @click="sidebarOpen = false"
                >
                    {{ item.name }}
                </Link>
            </nav>

            <div class="space-y-1 border-t border-brand-900 px-3 py-4 text-sm">
                <a :href="route('home')" class="block rounded-lg px-3 py-2 text-brand-200 hover:bg-brand-900 hover:text-white">Voir le site</a>
                <button type="button" class="block w-full rounded-lg px-3 py-2 text-left text-brand-200 hover:bg-brand-900 hover:text-white" @click="logout">
                    Déconnexion
                </button>
            </div>
        </aside>

        <div class="flex min-w-0 flex-1 flex-col">
            <header class="flex h-16 items-center justify-between border-b border-slate-200 bg-white px-4 sm:px-6">
                <button type="button" class="rounded-lg p-2 text-slate-600 hover:bg-slate-100 lg:hidden" aria-label="Ouvrir le menu" @click="sidebarOpen = true">
                    <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5" /></svg>
                </button>
                <p class="ml-auto text-sm text-slate-600">{{ page.props.auth.user?.name }}</p>
            </header>

            <main class="flex-1 p-4 sm:p-6 lg:p-8">
                <FlashMessage />
                <slot />
            </main>
        </div>
    </div>
</template>
