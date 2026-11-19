<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import Button from '../../Components/Button.vue';
import Card from '../../Components/Card.vue';
import FormField from '../../Components/FormField.vue';
import PageHeader from '../../Components/PageHeader.vue';
import Select from '../../Components/Select.vue';
import Textarea from '../../Components/Textarea.vue';

const props = defineProps({
    serviceRequest: { type: Object, required: true },
    statuses: { type: Array, required: true },
});

const form = useForm({
    status: props.serviceRequest.status,
    internal_notes: props.serviceRequest.internal_notes ?? '',
});

function submit() {
    form.put(route('admin.service-requests.update', props.serviceRequest.id), { preserveScroll: true });
}
</script>

<template>
    <Head :title="`Demande ${serviceRequest.reference}`" />

    <PageHeader :title="`Demande ${serviceRequest.reference}`" :subtitle="`${serviceRequest.service} · reçue le ${serviceRequest.created_at}`">
        <Link :href="route('admin.service-requests.index')" class="text-sm font-medium text-slate-600 hover:text-slate-900">← Demandes</Link>
    </PageHeader>

    <div class="grid gap-6 xl:grid-cols-[1fr_22rem]">
        <div class="space-y-6">
            <Card title="Besoin du client">
                <dl class="grid gap-3 text-sm sm:grid-cols-3">
                    <div><dt class="text-slate-500">Intervention</dt><dd class="font-medium">{{ serviceRequest.intervention }}</dd></div>
                    <div><dt class="text-slate-500">Urgence</dt><dd class="font-medium">{{ serviceRequest.urgency }}</dd></div>
                    <div><dt class="text-slate-500">Ville</dt><dd class="font-medium">{{ serviceRequest.contact.city }}</dd></div>
                </dl>
                <p class="mt-4 whitespace-pre-line rounded-lg bg-slate-50 p-4 text-sm text-slate-800">{{ serviceRequest.message }}</p>
            </Card>

            <form @submit.prevent="submit">
                <Card title="Suivi" description="Les notes internes ne sont jamais visibles du client.">
                    <div class="space-y-4">
                        <FormField label="Statut" for="status" :error="form.errors.status">
                            <Select id="status" v-model="form.status" :options="statuses" />
                        </FormField>
                        <FormField label="Notes internes" for="notes" :error="form.errors.internal_notes">
                            <Textarea id="notes" v-model="form.internal_notes" :rows="5" :invalid="!!form.errors.internal_notes" />
                        </FormField>
                    </div>
                    <template #footer><Button type="submit" :processing="form.processing">Enregistrer</Button></template>
                </Card>
            </form>
        </div>

        <Card title="Client">
            <p class="font-medium">{{ serviceRequest.contact.name }}</p>
            <p v-if="serviceRequest.contact.company" class="text-sm text-slate-600">{{ serviceRequest.contact.company }}</p>
            <p class="mt-2 text-sm text-slate-600">
                <a :href="`tel:${serviceRequest.contact.phone}`" class="text-brand-700 hover:underline">{{ serviceRequest.contact.phone }}</a><br />
                <a :href="`mailto:${serviceRequest.contact.email}`" class="text-brand-700 hover:underline">{{ serviceRequest.contact.email }}</a>
            </p>
        </Card>
    </div>
</template>
