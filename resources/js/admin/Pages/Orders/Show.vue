<script setup>
import { ref } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import Badge from '../../Components/Badge.vue';
import Button from '../../Components/Button.vue';
import Card from '../../Components/Card.vue';
import ConfirmDialog from '../../Components/ConfirmDialog.vue';
import FormField from '../../Components/FormField.vue';
import Modal from '../../Components/Modal.vue';
import PageHeader from '../../Components/PageHeader.vue';
import StatusBadge from '../../Components/StatusBadge.vue';
import Textarea from '../../Components/Textarea.vue';
import TextInput from '../../Components/TextInput.vue';

const props = defineProps({
    order: { type: Object, required: true },
});

const paymentTone = { submitted: 'amber', validated: 'green', rejected: 'red' };

const rejecting = ref(null);
const rejectForm = useForm({ note: '' });
const shipForm = useForm({ status: 'shipped', tracking_number: '', tracking_carrier: '', note: '' });
const statusForm = useForm({ status: '', note: '' });
const proofForm = useForm({ proofs: [] });
const pendingStatus = ref(null);
const showShip = ref(false);

function validatePayment(payment) {
    router.post(route('admin.orders.payments.validate', payment.id), {}, { preserveScroll: true });
}

function submitReject() {
    rejectForm.post(route('admin.orders.payments.reject', rejecting.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            rejecting.value = null;
            rejectForm.reset();
        },
    });
}

function changeStatus(status) {
    if (status.value === 'shipped') {
        showShip.value = true;
    } else {
        pendingStatus.value = status;
    }
}

function confirmStatus() {
    statusForm.status = pendingStatus.value.value;
    statusForm.put(route('admin.orders.status', props.order.id), {
        preserveScroll: true,
        onFinish: () => (pendingStatus.value = null),
    });
}

function submitShip() {
    shipForm.put(route('admin.orders.status', props.order.id), {
        preserveScroll: true,
        onSuccess: () => (showShip.value = false),
    });
}

function uploadProof(event) {
    proofForm.proofs = [...event.target.files];
    proofForm.post(route('admin.orders.proofs.store', props.order.id), {
        forceFormData: true,
        preserveScroll: true,
        onFinish: () => (event.target.value = ''),
    });
}

const actionLabels = {
    processing: props.order.payment_method === 'cod' ? 'Confirmer la commande' : 'Passer en préparation',
    shipped: 'Marquer comme expédiée',
    delivered: 'Marquer comme livrée',
    cancelled: 'Annuler la commande',
};
</script>

<template>
    <Head :title="`Commande ${order.reference}`" />

    <PageHeader :title="`Commande ${order.reference}`" :subtitle="`Passée le ${order.created_at}`">
        <StatusBadge :status="order.status" />
        <Link :href="route('admin.orders.index')" class="text-sm font-medium text-slate-600 hover:text-slate-900">← Commandes</Link>
    </PageHeader>

    <div class="grid gap-6 xl:grid-cols-[1fr_22rem]">
        <div class="space-y-6">
            <Card title="Articles">
                <table class="w-full text-left text-sm">
                    <thead class="text-xs uppercase tracking-wide text-slate-500">
                        <tr><th class="pb-2 font-medium">Produit</th><th class="pb-2 text-right font-medium">Qté</th><th class="pb-2 text-right font-medium">PU HT</th><th class="pb-2 text-right font-medium">Total TTC</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="item in order.items" :key="item.id">
                            <td class="py-2"><span class="font-medium text-slate-900">{{ item.name }}</span><span class="block text-xs text-slate-500">{{ item.reference }}</span></td>
                            <td class="py-2 text-right">{{ item.quantity }}</td>
                            <td class="py-2 text-right">{{ item.unit_price_ht }}</td>
                            <td class="py-2 text-right font-medium">{{ item.line_ttc }}</td>
                        </tr>
                    </tbody>
                </table>
                <dl class="ml-auto mt-4 max-w-xs space-y-1 text-sm">
                    <div class="flex justify-between"><dt class="text-slate-600">Sous-total HT</dt><dd>{{ order.totals.subtotal_ht }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-600">Livraison HT</dt><dd>{{ order.totals.shipping_ht }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-600">TVA</dt><dd>{{ order.totals.vat_total }}</dd></div>
                    <div class="flex justify-between border-t border-slate-200 pt-2 text-base font-bold"><dt>Total TTC</dt><dd>{{ order.totals.total_ttc }}</dd></div>
                </dl>
            </Card>

            <Card title="Paiement" :description="order.payment_method_label">
                <ul v-if="order.payments.length" class="space-y-4">
                    <li v-for="payment in order.payments" :key="payment.id" class="rounded-lg p-4 ring-1 ring-slate-200">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <p class="text-sm font-medium">{{ payment.amount }} <span class="text-slate-500">· reçu le {{ payment.created_at }}</span></p>
                            <Badge :tone="paymentTone[payment.status]">{{ payment.status_label }}</Badge>
                        </div>
                        <ul v-if="payment.proofs.length" class="mt-3 flex flex-wrap gap-3">
                            <li v-for="proof in payment.proofs" :key="proof.id">
                                <a :href="proof.url" target="_blank" rel="noopener" class="block rounded-lg bg-slate-50 px-3 py-2 text-sm font-medium text-brand-700 ring-1 ring-slate-200 hover:bg-brand-50">
                                    {{ proof.is_image ? '🖼' : '📄' }} {{ proof.name }}
                                </a>
                            </li>
                        </ul>
                        <p v-if="payment.admin_note" class="mt-2 text-sm text-slate-600">Note : {{ payment.admin_note }}</p>
                        <p v-if="payment.reviewed_at" class="mt-1 text-xs text-slate-500">Traité le {{ payment.reviewed_at }}</p>
                        <div v-if="payment.status === 'submitted'" class="mt-3 flex gap-2">
                            <Button @click="validatePayment(payment)">Valider le paiement</Button>
                            <Button variant="danger" @click="rejecting = payment">Refuser</Button>
                        </div>
                    </li>
                </ul>
                <p v-else class="text-sm text-slate-500">{{ order.payment_method === 'cod' ? 'Paiement à la livraison : rien à valider.' : 'Aucun justificatif reçu.' }}</p>

                <div v-if="order.accepts_proof" class="mt-4 border-t border-slate-200 pt-4">
                    <label class="text-sm font-medium text-slate-700">Ajouter un justificatif au nom du client</label>
                    <input type="file" multiple accept=".jpg,.jpeg,.png,.pdf" class="mt-2 block text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-3 file:py-1.5 file:font-medium file:text-brand-700" @change="uploadProof" />
                    <p v-if="proofForm.errors.proofs || proofForm.errors['proofs.0']" class="mt-1 text-xs text-red-600">{{ proofForm.errors.proofs ?? proofForm.errors['proofs.0'] }}</p>
                </div>
            </Card>

            <Card title="Historique">
                <ol class="space-y-3 border-l-2 border-slate-200 pl-4 text-sm">
                    <li v-for="entry in order.history" :key="entry.id">
                        <p><span class="font-medium text-slate-900">{{ entry.to }}</span> <span class="text-xs text-slate-500">· {{ entry.created_at }}<template v-if="entry.by"> · {{ entry.by }}</template></span></p>
                        <p v-if="entry.note" class="text-slate-600">{{ entry.note }}</p>
                    </li>
                </ol>
            </Card>
        </div>

        <div class="space-y-6">
            <Card v-if="order.next_statuses.length" title="Actions">
                <div class="flex flex-col gap-2">
                    <Button
                        v-for="status in order.next_statuses"
                        :key="status.value"
                        :variant="status.value === 'cancelled' ? 'danger' : 'primary'"
                        @click="changeStatus(status)"
                    >
                        {{ actionLabels[status.value] ?? status.label }}
                    </Button>
                </div>
                <p v-if="$page.props.flash?.error" class="mt-3 text-sm text-red-600">{{ $page.props.flash.error }}</p>
            </Card>

            <Card v-if="order.invoice" title="Facture">
                <a :href="order.invoice.url" target="_blank" rel="noopener" class="text-sm font-medium text-brand-700 hover:underline">Télécharger {{ order.invoice.number }} (PDF)</a>
            </Card>

            <Card title="Client">
                <p class="font-medium">{{ order.contact.name }}</p>
                <p class="text-sm text-slate-600">{{ order.contact.email }}<br />{{ order.contact.phone }}</p>
                <div v-if="order.company" class="mt-3 border-t border-slate-100 pt-3 text-sm">
                    <p class="font-medium">{{ order.company.name }}</p>
                    <p v-if="order.company.ice" class="text-slate-600">ICE : {{ order.company.ice }}</p>
                    <p v-if="order.company.if" class="text-slate-600">IF : {{ order.company.if }}</p>
                    <p v-if="order.company.rc" class="text-slate-600">RC : {{ order.company.rc }}</p>
                </div>
                <p v-if="order.customer_note" class="mt-3 rounded-lg bg-slate-50 p-3 text-sm text-slate-700">« {{ order.customer_note }} »</p>
            </Card>

            <Card title="Livraison">
                <p class="text-sm text-slate-700">{{ order.shipping.address }}<br />{{ order.shipping.postal_code }} {{ order.shipping.city }}</p>
                <dl class="mt-3 space-y-1 text-sm">
                    <div class="flex justify-between"><dt class="text-slate-500">Mode</dt><dd>{{ order.shipping.carrier_mode_label }}</dd></div>
                    <div v-if="order.shipping.zone" class="flex justify-between"><dt class="text-slate-500">Zone</dt><dd>{{ order.shipping.zone }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Poids</dt><dd>{{ (order.shipping.weight_grams / 1000).toFixed(2) }} kg</dd></div>
                    <div v-if="order.shipping.tracking_number" class="flex justify-between"><dt class="text-slate-500">Suivi</dt><dd>{{ order.shipping.tracking_number }}<template v-if="order.shipping.tracking_carrier"> ({{ order.shipping.tracking_carrier }})</template></dd></div>
                </dl>
                <p v-if="order.shipping.own_carrier_note" class="mt-3 rounded-lg bg-amber-50 p-3 text-sm text-amber-900">Transporteur du client : {{ order.shipping.own_carrier_note }}</p>
            </Card>
        </div>
    </div>

    <Modal :show="rejecting !== null" @close="rejecting = null">
        <form class="p-6" @submit.prevent="submitReject">
            <h2 class="text-lg font-semibold">Refuser le justificatif</h2>
            <p class="mt-1 text-sm text-slate-600">Le client verra ce motif et pourra envoyer un nouveau justificatif.</p>
            <div class="mt-4">
                <FormField label="Motif" for="reject-note" :error="rejectForm.errors.note">
                    <Textarea id="reject-note" v-model="rejectForm.note" :rows="3" :invalid="!!rejectForm.errors.note" />
                </FormField>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <Button variant="secondary" @click="rejecting = null">Annuler</Button>
                <Button type="submit" variant="danger" :processing="rejectForm.processing">Refuser</Button>
            </div>
        </form>
    </Modal>

    <Modal :show="showShip" @close="showShip = false">
        <form class="p-6" @submit.prevent="submitShip">
            <h2 class="text-lg font-semibold">Expédier la commande</h2>
            <div class="mt-4 space-y-4">
                <FormField label="Numéro de suivi" for="tracking" :hint="order.shipping.carrier_mode === 'own' ? 'Facultatif : le client utilise son propre transporteur.' : null" :error="shipForm.errors.tracking_number">
                    <TextInput id="tracking" v-model="shipForm.tracking_number" :invalid="!!shipForm.errors.tracking_number" />
                </FormField>
                <FormField label="Transporteur" for="carrier" :error="shipForm.errors.tracking_carrier">
                    <TextInput id="carrier" v-model="shipForm.tracking_carrier" />
                </FormField>
                <p v-if="$page.props.flash?.error" class="text-sm text-red-600">{{ $page.props.flash.error }}</p>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <Button variant="secondary" @click="showShip = false">Annuler</Button>
                <Button type="submit" :processing="shipForm.processing">Marquer comme expédiée</Button>
            </div>
        </form>
    </Modal>

    <ConfirmDialog
        :show="pendingStatus !== null"
        :title="pendingStatus ? (actionLabels[pendingStatus.value] ?? pendingStatus.label) + ' ?' : ''"
        :message="pendingStatus?.value === 'cancelled' ? 'Le stock des articles sera remis en vente. Cette action est définitive.' : ''"
        confirm-label="Confirmer"
        :danger="pendingStatus?.value === 'cancelled'"
        :processing="statusForm.processing"
        @confirm="confirmStatus"
        @cancel="pendingStatus = null"
    />
</template>
