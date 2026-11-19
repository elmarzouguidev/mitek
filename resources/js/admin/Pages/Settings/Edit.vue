<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import TabBar from '../../Components/TabBar.vue';
import Button from '../../Components/Button.vue';
import Card from '../../Components/Card.vue';
import Checkbox from '../../Components/Checkbox.vue';
import FormField from '../../Components/FormField.vue';
import TagInput from '../../Components/TagInput.vue';
import Textarea from '../../Components/Textarea.vue';
import TextInput from '../../Components/TextInput.vue';

const props = defineProps({
    general: { type: Object, required: true },
    payment: { type: Object, required: true },
    shipping: { type: Object, required: true },
    announcement: { type: Object, required: true },
    claims: { type: Object, required: true },
    catalog: { type: Object, required: true },
});

const tabs = [
    { key: 'general', label: 'Général' },
    { key: 'announcement', label: 'Barre d\'annonce' },
    { key: 'claims', label: 'Engagements' },
    { key: 'payment', label: 'Paiement' },
    { key: 'shipping', label: 'Livraison' },
    { key: 'catalog', label: 'Catalogue' },
];
const tab = ref('general');

const weekdays = [
    { value: 1, label: 'Lundi' }, { value: 2, label: 'Mardi' }, { value: 3, label: 'Mercredi' }, { value: 4, label: 'Jeudi' },
    { value: 5, label: 'Vendredi' }, { value: 6, label: 'Samedi' }, { value: 7, label: 'Dimanche' },
];

const generalForm = useForm({
    site_name: props.general.site_name,
    legal_name: props.general.legal_name,
    email: props.general.email,
    phone: props.general.phone ?? '',
    whatsapp: props.general.whatsapp ?? '',
    address: props.general.address,
    maps_embed_url: props.general.maps_embed_url ?? '',
    vat_rate: props.general.vat_rate,
    ice: props.general.ice ?? '',
    if_number: props.general.if_number ?? '',
    rc_number: props.general.rc_number ?? '',
    tp_number: props.general.tp_number ?? '',
    cnss_number: props.general.cnss_number ?? '',
    legal_form: props.general.legal_form,
    share_capital: props.general.share_capital ?? '',
    registered_address: props.general.registered_address ?? '',
    city: props.general.city,
    admin_notification_email: props.general.admin_notification_email,
    opening_hours: props.general.opening_hours ?? '',
    whatsapp_hours: props.general.whatsapp_hours ?? '',
    facebook_url: props.general.facebook_url ?? '',
    instagram_url: props.general.instagram_url ?? '',
    linkedin_url: props.general.linkedin_url ?? '',
    youtube_url: props.general.youtube_url ?? '',
    tiktok_url: props.general.tiktok_url ?? '',
    cndp_number: props.general.cndp_number ?? '',
    warranty_text: props.general.warranty_text ?? '',
});

const announcementForm = useForm({
    is_active: props.announcement.is_active,
    dismissible: props.announcement.dismissible,
    text: props.announcement.text ?? '',
    link_url: props.announcement.link_url ?? '',
    link_label: props.announcement.link_label ?? '',
    starts_at: props.announcement.starts_at ?? '',
    ends_at: props.announcement.ends_at ?? '',
});

const claimsForm = useForm({ ...props.claims.values });

const catalogForm = useForm({ best_seller_min_sales: props.catalog.best_seller_min_sales });

const paymentForm = useForm({
    bank_name: props.payment.bank_name,
    account_holder: props.payment.account_holder,
    rib: props.payment.rib ?? '',
    cod_limit: props.payment.cod_limit,
    cheque_payee: props.payment.cheque_payee,
    bank_transfer_delay: props.payment.bank_transfer_delay,
    cash_deposit_delay: props.payment.cash_deposit_delay,
    cheque_delay: props.payment.cheque_delay,
});

const shippingForm = useForm({
    dispatch_days: props.shipping.dispatch_days,
    delivery_estimate: props.shipping.delivery_estimate,
    own_carrier_fee: props.shipping.own_carrier_fee,
    working_days: [...props.shipping.working_days],
    holidays: [...props.shipping.holidays],
});

function toggleDay(day) {
    shippingForm.working_days = shippingForm.working_days.includes(day) ? shippingForm.working_days.filter((value) => value !== day) : [...shippingForm.working_days, day];
}
</script>

<template>
    <Head title="Paramètres" />

    <h1 class="text-2xl font-semibold tracking-tight">Paramètres</h1>

    <TabBar v-model="tab" class="mt-4" :tabs="tabs" />

    <div class="mt-6 max-w-3xl space-y-6">
        <form v-show="tab === 'general'" @submit.prevent="generalForm.put(route('admin.settings.general'), { preserveScroll: true })">
            <Card title="Général" description="Coordonnées affichées sur le site et sur les factures.">
                <div class="grid gap-4 sm:grid-cols-2">
                    <FormField label="Nom du site" for="site_name" :error="generalForm.errors.site_name">
                        <TextInput id="site_name" v-model="generalForm.site_name" :invalid="!!generalForm.errors.site_name" />
                    </FormField>
                    <FormField label="Raison sociale" for="legal_name" :error="generalForm.errors.legal_name">
                        <TextInput id="legal_name" v-model="generalForm.legal_name" :invalid="!!generalForm.errors.legal_name" />
                    </FormField>
                    <FormField label="Email" for="email" :error="generalForm.errors.email">
                        <TextInput id="email" type="email" v-model="generalForm.email" :invalid="!!generalForm.errors.email" />
                    </FormField>
                    <FormField label="Téléphone" for="phone" :error="generalForm.errors.phone">
                        <TextInput id="phone" v-model="generalForm.phone" :invalid="!!generalForm.errors.phone" />
                    </FormField>
                    <FormField label="WhatsApp" for="whatsapp" hint="Format international, chiffres uniquement (ex. 212600000000)." :error="generalForm.errors.whatsapp">
                        <TextInput id="whatsapp" v-model="generalForm.whatsapp" :invalid="!!generalForm.errors.whatsapp" />
                    </FormField>
                    <FormField label="TVA (%)" for="vat_rate" :error="generalForm.errors.vat_rate">
                        <TextInput id="vat_rate" type="number" v-model="generalForm.vat_rate" :invalid="!!generalForm.errors.vat_rate" />
                    </FormField>
                    <FormField label="ICE (15 chiffres)" for="ice" hint="Imprimé sur les factures." :error="generalForm.errors.ice">
                        <TextInput id="ice" v-model="generalForm.ice" :invalid="!!generalForm.errors.ice" />
                    </FormField>
                    <FormField label="Identifiant fiscal (IF)" for="if_number" :error="generalForm.errors.if_number">
                        <TextInput id="if_number" v-model="generalForm.if_number" :invalid="!!generalForm.errors.if_number" />
                    </FormField>
                    <FormField label="Registre de commerce (RC)" for="rc_number" :error="generalForm.errors.rc_number">
                        <TextInput id="rc_number" v-model="generalForm.rc_number" :invalid="!!generalForm.errors.rc_number" />
                    </FormField>
                    <FormField label="Patente (TP)" for="tp_number" :error="generalForm.errors.tp_number">
                        <TextInput id="tp_number" v-model="generalForm.tp_number" :invalid="!!generalForm.errors.tp_number" />
                    </FormField>
                    <FormField label="CNSS" for="cnss_number" :error="generalForm.errors.cnss_number">
                        <TextInput id="cnss_number" v-model="generalForm.cnss_number" :invalid="!!generalForm.errors.cnss_number" />
                    </FormField>
                    <FormField label="Forme juridique" for="legal_form" :error="generalForm.errors.legal_form">
                        <TextInput id="legal_form" v-model="generalForm.legal_form" :invalid="!!generalForm.errors.legal_form" />
                    </FormField>
                    <FormField label="Capital social" for="share_capital" hint="Ex. 10 000 DH" :error="generalForm.errors.share_capital">
                        <TextInput id="share_capital" v-model="generalForm.share_capital" :invalid="!!generalForm.errors.share_capital" />
                    </FormField>
                    <FormField class="sm:col-span-2" label="Siège social" for="registered_address" hint="Si différent de l'adresse ci-dessous." :error="generalForm.errors.registered_address">
                        <TextInput id="registered_address" v-model="generalForm.registered_address" :invalid="!!generalForm.errors.registered_address" />
                    </FormField>
                    <FormField label="Ville" for="city" :error="generalForm.errors.city">
                        <TextInput id="city" v-model="generalForm.city" :invalid="!!generalForm.errors.city" />
                    </FormField>
                    <FormField label="Email de notification admin" for="admin_notification_email" hint="Nouvelles commandes, justificatifs, devis, demandes de service et messages." :error="generalForm.errors.admin_notification_email">
                        <TextInput id="admin_notification_email" type="email" v-model="generalForm.admin_notification_email" :invalid="!!generalForm.errors.admin_notification_email" />
                    </FormField>
                    <FormField class="sm:col-span-2" label="Adresse" for="address" :error="generalForm.errors.address">
                        <TextInput id="address" v-model="generalForm.address" :invalid="!!generalForm.errors.address" />
                    </FormField>
                    <FormField class="sm:col-span-2" label="Lien d'intégration Google Maps" hint="https://www.google.com/maps/embed… (laisser vide pour masquer la carte)" for="maps_embed_url" :error="generalForm.errors.maps_embed_url">
                        <TextInput id="maps_embed_url" v-model="generalForm.maps_embed_url" :invalid="!!generalForm.errors.maps_embed_url" />
                    </FormField>
                </div>
                <div class="mt-6 border-t border-slate-100 pt-6">
                    <h3 class="text-sm font-semibold text-slate-900">Horaires, réseaux et mentions</h3>
                    <p class="mt-1 text-xs text-slate-500">Chaque valeur est masquée sur le site tant qu'elle est vide.</p>
                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <FormField label="Horaires d'ouverture" for="opening_hours" hint="Ex. Lun–Sam, 9h–18h" :error="generalForm.errors.opening_hours">
                            <TextInput id="opening_hours" v-model="generalForm.opening_hours" :invalid="!!generalForm.errors.opening_hours" />
                        </FormField>
                        <FormField label="Horaires WhatsApp" for="whatsapp_hours" :error="generalForm.errors.whatsapp_hours">
                            <TextInput id="whatsapp_hours" v-model="generalForm.whatsapp_hours" :invalid="!!generalForm.errors.whatsapp_hours" />
                        </FormField>
                        <FormField label="Facebook" for="facebook_url" hint="https://…" :error="generalForm.errors.facebook_url">
                            <TextInput id="facebook_url" v-model="generalForm.facebook_url" :invalid="!!generalForm.errors.facebook_url" />
                        </FormField>
                        <FormField label="Instagram" for="instagram_url" :error="generalForm.errors.instagram_url">
                            <TextInput id="instagram_url" v-model="generalForm.instagram_url" :invalid="!!generalForm.errors.instagram_url" />
                        </FormField>
                        <FormField label="LinkedIn" for="linkedin_url" :error="generalForm.errors.linkedin_url">
                            <TextInput id="linkedin_url" v-model="generalForm.linkedin_url" :invalid="!!generalForm.errors.linkedin_url" />
                        </FormField>
                        <FormField label="YouTube" for="youtube_url" :error="generalForm.errors.youtube_url">
                            <TextInput id="youtube_url" v-model="generalForm.youtube_url" :invalid="!!generalForm.errors.youtube_url" />
                        </FormField>
                        <FormField label="TikTok" for="tiktok_url" :error="generalForm.errors.tiktok_url">
                            <TextInput id="tiktok_url" v-model="generalForm.tiktok_url" :invalid="!!generalForm.errors.tiktok_url" />
                        </FormField>
                        <FormField label="Numéro CNDP" for="cndp_number" hint="Déclaration ou autorisation, loi 09-08." :error="generalForm.errors.cndp_number">
                            <TextInput id="cndp_number" v-model="generalForm.cndp_number" :invalid="!!generalForm.errors.cndp_number" />
                        </FormField>
                        <FormField class="sm:col-span-2" label="Texte de garantie" for="warranty_text" hint="Affiché sur les produits seulement si l'engagement « garantie » est activé." :error="generalForm.errors.warranty_text">
                            <TextInput id="warranty_text" v-model="generalForm.warranty_text" :invalid="!!generalForm.errors.warranty_text" />
                        </FormField>
                    </div>
                </div>
                <template #footer>
                    <Button type="submit" :processing="generalForm.processing">Enregistrer</Button>
                </template>
            </Card>
        </form>

        <form v-show="tab === 'payment'" @submit.prevent="paymentForm.put(route('admin.settings.payment'), { preserveScroll: true })">
            <Card title="Paiement" description="Coordonnées bancaires et règles de paiement.">
                <div class="grid gap-4 sm:grid-cols-2">
                    <FormField label="Banque" for="bank_name" :error="paymentForm.errors.bank_name">
                        <TextInput id="bank_name" v-model="paymentForm.bank_name" :invalid="!!paymentForm.errors.bank_name" />
                    </FormField>
                    <FormField label="Titulaire du compte" for="account_holder" :error="paymentForm.errors.account_holder">
                        <TextInput id="account_holder" v-model="paymentForm.account_holder" :invalid="!!paymentForm.errors.account_holder" />
                    </FormField>
                    <FormField label="RIB" for="rib" :error="paymentForm.errors.rib">
                        <TextInput id="rib" v-model="paymentForm.rib" :invalid="!!paymentForm.errors.rib" />
                    </FormField>
                    <FormField label="Plafond paiement à la livraison (DH TTC)" for="cod_limit" :error="paymentForm.errors.cod_limit">
                        <TextInput id="cod_limit" type="number" v-model="paymentForm.cod_limit" :invalid="!!paymentForm.errors.cod_limit" />
                    </FormField>
                    <FormField label="Chèques à l'ordre de" for="cheque_payee" :error="paymentForm.errors.cheque_payee">
                        <TextInput id="cheque_payee" v-model="paymentForm.cheque_payee" :invalid="!!paymentForm.errors.cheque_payee" />
                    </FormField>
                    <FormField label="Délai du virement" for="bank_transfer_delay" :error="paymentForm.errors.bank_transfer_delay">
                        <TextInput id="bank_transfer_delay" v-model="paymentForm.bank_transfer_delay" :invalid="!!paymentForm.errors.bank_transfer_delay" />
                    </FormField>
                    <FormField label="Délai du dépôt en espèces" for="cash_deposit_delay" :error="paymentForm.errors.cash_deposit_delay">
                        <TextInput id="cash_deposit_delay" v-model="paymentForm.cash_deposit_delay" :invalid="!!paymentForm.errors.cash_deposit_delay" />
                    </FormField>
                    <FormField label="Délai d'encaissement du chèque" for="cheque_delay" :error="paymentForm.errors.cheque_delay">
                        <TextInput id="cheque_delay" v-model="paymentForm.cheque_delay" :invalid="!!paymentForm.errors.cheque_delay" />
                    </FormField>
                </div>
                <template #footer>
                    <Button type="submit" :processing="paymentForm.processing">Enregistrer</Button>
                </template>
            </Card>
        </form>

        <form v-show="tab === 'shipping'" @submit.prevent="shippingForm.put(route('admin.settings.shipping'), { preserveScroll: true })">
            <Card title="Livraison" description="Délais et transporteur personnel. Les tarifs par zone et par poids se règlent dans « Livraison ».">
                <div class="grid gap-4 sm:grid-cols-2">
                    <FormField label="Expédition (jours après validation)" for="dispatch_days" :error="shippingForm.errors.dispatch_days">
                        <TextInput id="dispatch_days" type="number" v-model="shippingForm.dispatch_days" :invalid="!!shippingForm.errors.dispatch_days" />
                    </FormField>
                    <FormField label="Supplément transporteur personnel (DH HT)" for="own_carrier_fee" :error="shippingForm.errors.own_carrier_fee">
                        <TextInput id="own_carrier_fee" type="number" v-model="shippingForm.own_carrier_fee" :invalid="!!shippingForm.errors.own_carrier_fee" />
                    </FormField>
                    <FormField class="sm:col-span-2" label="Texte de secours pour le délai de livraison" for="delivery_estimate" hint="Utilisé seulement tant qu'aucune zone de livraison n'est active. Sinon le délai affiché (page Livraison, FAQ) est calculé à partir des jours de transport des zones." :error="shippingForm.errors.delivery_estimate">
                        <TextInput id="delivery_estimate" v-model="shippingForm.delivery_estimate" :invalid="!!shippingForm.errors.delivery_estimate" />
                    </FormField>
                </div>
                <div class="mt-6 border-t border-slate-100 pt-6">
                    <h3 class="text-sm font-semibold text-slate-900">Calendrier de livraison</h3>
                    <p class="mt-1 text-xs text-slate-500">Sert à estimer la date de livraison : l'expédition (jours ci-dessus) et le transit de la zone ne comptent que les jours ouvrés.</p>
                    <fieldset class="mt-4">
                        <legend class="text-sm font-medium text-slate-700">Jours ouvrés</legend>
                        <div class="mt-2 flex flex-wrap gap-4">
                            <label v-for="day in weekdays" :key="day.value" class="flex items-center gap-2 text-sm text-slate-700">
                                <input type="checkbox" :checked="shippingForm.working_days.includes(day.value)" class="rounded text-brand-700 focus:ring-brand-600" @change="toggleDay(day.value)" />
                                {{ day.label }}
                            </label>
                        </div>
                        <p v-if="shippingForm.errors.working_days" class="mt-1 text-xs text-red-600">{{ shippingForm.errors.working_days }}</p>
                    </fieldset>
                    <div class="mt-4">
                        <FormField label="Jours fériés" for="holidays" hint="Une date par entrée, au format AAAA-MM-JJ (ex. 2026-12-25), puis Entrée." :error="shippingForm.errors.holidays ?? shippingForm.errors['holidays.0']">
                            <TagInput id="holidays" v-model="shippingForm.holidays" placeholder="2026-12-25" />
                        </FormField>
                    </div>
                </div>
                <template #footer>
                    <Button type="submit" :processing="shippingForm.processing">Enregistrer</Button>
                </template>
            </Card>
        </form>

        <form v-show="tab === 'announcement'" @submit.prevent="announcementForm.put(route('admin.settings.announcement'), { preserveScroll: true })">
            <Card title="Barre d'annonce" description="Bandeau au-dessus de l'en-tête. Il s'affiche uniquement pendant la période choisie.">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="flex flex-col gap-2 sm:col-span-2">
                        <Checkbox v-model="announcementForm.is_active" label="Barre d'annonce active" />
                        <Checkbox v-model="announcementForm.dismissible" label="Le visiteur peut la fermer" />
                    </div>
                    <FormField class="sm:col-span-2" label="Texte" for="announcement_text" hint="200 caractères au plus." :error="announcementForm.errors.text">
                        <TextInput id="announcement_text" v-model="announcementForm.text" :invalid="!!announcementForm.errors.text" />
                    </FormField>
                    <FormField label="Lien" for="announcement_link" hint="https://… ou /page-du-site" :error="announcementForm.errors.link_url">
                        <TextInput id="announcement_link" v-model="announcementForm.link_url" :invalid="!!announcementForm.errors.link_url" />
                    </FormField>
                    <FormField label="Libellé du lien" for="announcement_label" :error="announcementForm.errors.link_label">
                        <TextInput id="announcement_label" v-model="announcementForm.link_label" :invalid="!!announcementForm.errors.link_label" />
                    </FormField>
                    <FormField label="Début (facultatif)" for="announcement_start" :error="announcementForm.errors.starts_at">
                        <TextInput id="announcement_start" v-model="announcementForm.starts_at" type="datetime-local" :invalid="!!announcementForm.errors.starts_at" />
                    </FormField>
                    <FormField label="Fin (facultatif)" for="announcement_end" :error="announcementForm.errors.ends_at">
                        <TextInput id="announcement_end" v-model="announcementForm.ends_at" type="datetime-local" :invalid="!!announcementForm.errors.ends_at" />
                    </FormField>
                </div>
                <template #footer>
                    <Button type="submit" :processing="announcementForm.processing">Enregistrer</Button>
                </template>
            </Card>
        </form>

        <form v-show="tab === 'claims'" @submit.prevent="claimsForm.put(route('admin.settings.claims'), { preserveScroll: true })">
            <Card title="Engagements" description="Promesses commerciales non confirmées dans Mitek.md. Elles restent désactivées tant que vous ne les confirmez pas : rien ne s'affiche sur le site sans votre accord.">
                <ul class="space-y-4">
                    <li v-for="(description, key) in claims.descriptions" :key="key">
                        <Checkbox v-model="claimsForm[key]" :label="description.label" />
                        <p class="ml-6 text-xs text-slate-500">{{ description.help }}</p>
                    </li>
                </ul>
                <template #footer>
                    <Button type="submit" :processing="claimsForm.processing">Enregistrer</Button>
                </template>
            </Card>
        </form>

        <form v-show="tab === 'catalog'" @submit.prevent="catalogForm.put(route('admin.settings.catalog'), { preserveScroll: true })">
            <Card title="Catalogue" description="Le badge « meilleure vente » se règle produit par produit ; il peut aussi être attribué automatiquement.">
                <FormField label="Ventes minimales sur 90 jours" for="best_seller_min_sales" hint="0 désactive l'attribution automatique : seul le réglage manuel s'applique." :error="catalogForm.errors.best_seller_min_sales">
                    <TextInput id="best_seller_min_sales" v-model="catalogForm.best_seller_min_sales" type="number" :invalid="!!catalogForm.errors.best_seller_min_sales" />
                </FormField>
                <template #footer>
                    <Button type="submit" :processing="catalogForm.processing">Enregistrer</Button>
                </template>
            </Card>
        </form>
    </div>
</template>
