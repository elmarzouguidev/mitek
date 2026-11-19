<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import Button from '../../Components/Button.vue';
import Card from '../../Components/Card.vue';
import Checkbox from '../../Components/Checkbox.vue';
import FormField from '../../Components/FormField.vue';
import PageHeader from '../../Components/PageHeader.vue';
import TagInput from '../../Components/TagInput.vue';
import TextInput from '../../Components/TextInput.vue';

const props = defineProps({
    zone: { type: Object, default: null },
    citySuggestions: { type: Array, default: () => [] },
});

const isEdit = props.zone !== null;

const form = useForm({
    name: props.zone?.name ?? '',
    is_default: props.zone?.is_default ?? false,
    is_active: props.zone?.is_active ?? true,
    transit_min_days: props.zone?.transit_min_days ?? 1,
    transit_max_days: props.zone?.transit_max_days ?? 3,
    cities: props.zone?.cities ?? [],
    rates: props.zone?.rates ?? [{ min_weight_g: 0, max_weight_g: '', price_ht: '' }],
});

function submit() {
    if (isEdit) {
        form.put(route('admin.shipping.update', props.zone.id));
    } else {
        form.post(route('admin.shipping.store'));
    }
}

function addRate() {
    const last = form.rates[form.rates.length - 1];
    const nextMin = last && last.max_weight_g !== '' && last.max_weight_g !== null ? Number(last.max_weight_g) + 1 : '';
    form.rates.push({ min_weight_g: nextMin, max_weight_g: '', price_ht: '' });
}

const error = (key) => form.errors[key] ?? null;
const cityErrors = () => Object.entries(form.errors).filter(([key]) => key.startsWith('cities.')).map(([, message]) => message);
</script>

<template>
    <Head :title="isEdit ? 'Modifier la zone' : 'Nouvelle zone'" />

    <PageHeader :title="isEdit ? `Zone « ${zone.name} »` : 'Nouvelle zone de livraison'" />

    <form class="max-w-3xl space-y-6" @submit.prevent="submit">
        <Card title="Zone">
            <div class="grid gap-4 sm:grid-cols-2">
                <FormField class="sm:col-span-2" label="Nom" for="name" :error="error('name')">
                    <TextInput id="name" v-model="form.name" :invalid="!!error('name')" />
                </FormField>
                <Checkbox v-model="form.is_active" label="Zone active" />
                <Checkbox v-model="form.is_default" label="Zone par défaut (villes non listées)" />
                <FormField label="Transit minimum (jours ouvrés)" for="transit_min_days" hint="Après l'expédition." :error="error('transit_min_days')">
                    <TextInput id="transit_min_days" v-model="form.transit_min_days" type="number" :invalid="!!error('transit_min_days')" />
                </FormField>
                <FormField label="Transit maximum (jours ouvrés)" for="transit_max_days" :error="error('transit_max_days')">
                    <TextInput id="transit_max_days" v-model="form.transit_max_days" type="number" :invalid="!!error('transit_max_days')" />
                </FormField>
                <div class="sm:col-span-2">
                    <FormField label="Villes de la zone" for="cities" hint="Tapez une ville puis Entrée. Une ville ne peut appartenir qu'à une seule zone." :error="cityErrors()[0] ?? error('cities')">
                        <TagInput id="cities" v-model="form.cities" placeholder="Ajouter une ville…" />
                    </FormField>
                    <p class="mt-2 text-xs text-slate-500">Suggestions : {{ citySuggestions.slice(0, 12).join(', ') }}…</p>
                </div>
            </div>
        </Card>

        <Card title="Tarifs par tranche de poids" description="Prix HT du colis selon le poids total de la commande. Les bornes sont incluses ; sans poids maximum, la tranche n'a pas de limite.">
            <div class="space-y-3">
                <div v-for="(rate, index) in form.rates" :key="index" class="grid grid-cols-[1fr_1fr_1fr_auto] items-start gap-3">
                    <FormField label="De (g)" :error="error(`rates.${index}.min_weight_g`)">
                        <TextInput v-model="rate.min_weight_g" type="number" :invalid="!!error(`rates.${index}.min_weight_g`)" />
                    </FormField>
                    <FormField label="À (g, vide = illimité)" :error="error(`rates.${index}.max_weight_g`)">
                        <TextInput v-model="rate.max_weight_g" type="number" :invalid="!!error(`rates.${index}.max_weight_g`)" />
                    </FormField>
                    <FormField label="Prix HT (DH)" :error="error(`rates.${index}.price_ht`)">
                        <TextInput v-model="rate.price_ht" type="number" :invalid="!!error(`rates.${index}.price_ht`)" />
                    </FormField>
                    <button type="button" class="mt-7 text-sm font-medium text-red-600 hover:underline" @click="form.rates.splice(index, 1)">Retirer</button>
                </div>
            </div>
            <p v-if="error('rates')" class="mt-2 text-sm text-red-600">{{ error('rates') }}</p>
            <Button class="mt-4" variant="secondary" @click="addRate">Ajouter une tranche</Button>
        </Card>

        <div class="flex items-center gap-3">
            <Button type="submit" :processing="form.processing">Enregistrer</Button>
            <Link :href="route('admin.shipping.index')" class="text-sm font-medium text-slate-600 hover:text-slate-900">Annuler</Link>
        </div>
    </form>
</template>
