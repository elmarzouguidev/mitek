<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import Button from '../../Components/Button.vue';
import Card from '../../Components/Card.vue';
import ConfirmDialog from '../../Components/ConfirmDialog.vue';
import PageHeader from '../../Components/PageHeader.vue';

const props = defineProps({
    message: { type: Object, required: true },
});

const confirmingDelete = ref(false);

function markUnread() {
    router.put(route('admin.messages.unread', props.message.id));
}

function destroy() {
    router.delete(route('admin.messages.destroy', props.message.id));
}
</script>

<template>
    <Head :title="message.subject" />

    <PageHeader :title="message.subject" :subtitle="`De ${message.name} · reçu le ${message.created_at}`">
        <Link :href="route('admin.messages.index')" class="text-sm font-medium text-slate-600 hover:text-slate-900">← Messages</Link>
    </PageHeader>

    <div class="max-w-3xl space-y-6">
        <Card>
            <p class="whitespace-pre-line text-sm text-slate-800">{{ message.body }}</p>
            <template #footer>
                <Button variant="secondary" @click="markUnread">Marquer comme non lu</Button>
                <Button variant="danger" @click="confirmingDelete = true">Supprimer</Button>
            </template>
        </Card>
        <Card title="Répondre">
            <p class="text-sm text-slate-600">
                <a :href="`mailto:${message.email}?subject=${encodeURIComponent('Re: ' + message.subject)}`" class="font-medium text-brand-700 hover:underline">{{ message.email }}</a>
                <template v-if="message.phone"> · <a :href="`tel:${message.phone}`" class="font-medium text-brand-700 hover:underline">{{ message.phone }}</a></template>
            </p>
        </Card>
    </div>

    <ConfirmDialog :show="confirmingDelete" title="Supprimer ce message ?" message="Cette action est définitive." confirm-label="Supprimer" danger @confirm="destroy" @cancel="confirmingDelete = false" />
</template>
