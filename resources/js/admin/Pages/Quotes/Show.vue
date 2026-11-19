<script setup>
import { ref } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import Button from '../../Components/Button.vue';
import Card from '../../Components/Card.vue';
import ConfirmDialog from '../../Components/ConfirmDialog.vue';
import FormField from '../../Components/FormField.vue';
import PageHeader from '../../Components/PageHeader.vue';
import StatusBadge from '../../Components/StatusBadge.vue';
import Textarea from '../../Components/Textarea.vue';

const props = defineProps({
    quote: { type: Object, required: true },
});

const form = useForm({ admin_reply: props.quote.admin_reply ?? '' });
const closing = ref(false);
const isClosed = props.quote.status.value === 'closed';

function submit() {
    form.post(route('admin.quotes.reply', props.quote.id), { preserveScroll: true });
}

function close() {
    router.put(route('admin.quotes.close', props.quote.id), {}, { preserveScroll: true, onFinish: () => (closing.value = false) });
}
</script>

<template>
    <Head :title="`Devis ${quote.reference}`" />

    <PageHeader :title="`Demande ${quote.reference}`" :subtitle="`Reçue le ${quote.created_at}`">
        <StatusBadge :status="quote.status" />
        <Link :href="route('admin.quotes.index')" class="text-sm font-medium text-slate-600 hover:text-slate-900">← Devis</Link>
    </PageHeader>

    <div class="grid gap-6 xl:grid-cols-[1fr_22rem]">
        <div class="space-y-6">
            <Card title="Articles demandés" description="Les prix sont indicatifs (tarif dégressif appliqué au moment de la demande).">
                <table class="w-full text-left text-sm">
                    <thead class="text-xs uppercase tracking-wide text-slate-500">
                        <tr><th class="pb-2 font-medium">Produit</th><th class="pb-2 text-right font-medium">Quantité</th><th class="pb-2 text-right font-medium">PU HT indicatif</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="item in quote.items" :key="item.id">
                            <td class="py-2"><span class="font-medium text-slate-900">{{ item.name }}</span><span class="block text-xs text-slate-500">{{ item.reference }}</span></td>
                            <td class="py-2 text-right">{{ item.quantity }}</td>
                            <td class="py-2 text-right">{{ item.unit_price_ht }}</td>
                        </tr>
                    </tbody>
                </table>
                <p v-if="quote.message" class="mt-4 rounded-lg bg-slate-50 p-3 text-sm text-slate-700">« {{ quote.message }} »</p>
            </Card>

            <form @submit.prevent="submit">
                <Card title="Réponse au client" :description="quote.replied_at ? `Dernière réponse le ${quote.replied_at}${quote.replied_by ? ' par ' + quote.replied_by : ''}.` : 'Le client la reçoit par email.'">
                    <FormField label="Votre réponse" for="admin_reply" hint="Prix proposés, délais, conditions…" :error="form.errors.admin_reply">
                        <Textarea id="admin_reply" v-model="form.admin_reply" :rows="8" :invalid="!!form.errors.admin_reply" />
                    </FormField>
                    <template #footer>
                        <Button v-if="!isClosed" variant="secondary" @click="closing = true">Clôturer</Button>
                        <Button v-if="!isClosed" type="submit" :processing="form.processing">{{ quote.admin_reply ? 'Envoyer une nouvelle réponse' : 'Envoyer la réponse' }}</Button>
                        <p v-else class="text-sm text-slate-500">Demande clôturée.</p>
                    </template>
                </Card>
            </form>
        </div>

        <Card title="Client">
            <p class="font-medium">{{ quote.contact.name }}</p>
            <p class="text-sm text-slate-600">{{ quote.contact.email }}<br />{{ quote.contact.phone }}</p>
            <div v-if="quote.company" class="mt-3 border-t border-slate-100 pt-3 text-sm">
                <p class="font-medium">{{ quote.company.name }}</p>
                <p v-if="quote.company.ice" class="text-slate-600">ICE : {{ quote.company.ice }}</p>
            </div>
        </Card>
    </div>

    <ConfirmDialog :show="closing" title="Clôturer cette demande ?" message="Elle ne pourra plus recevoir de réponse." confirm-label="Clôturer" @confirm="close" @cancel="closing = false" />
</template>
