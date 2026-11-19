import '../css/app.css';
import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { ZiggyVue } from 'ziggy-js';
import AdminLayout from './admin/Layouts/AdminLayout.vue';

createInertiaApp({
    title: (title) => (title ? `${title} · Administration` : 'Administration'),
    resolve: (name) => {
        const pages = import.meta.glob('./admin/Pages/**/*.vue', { eager: true });
        const page = pages[`./admin/Pages/${name}.vue`];

        page.default.layout ??= AdminLayout;

        return page;
    },
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .mount(el);
    },
    progress: { color: '#2a62a8' },
});
