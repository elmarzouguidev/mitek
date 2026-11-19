<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import Button from '../../Components/Button.vue';
import Card from '../../Components/Card.vue';
import FormField from '../../Components/FormField.vue';
import PageHeader from '../../Components/PageHeader.vue';
import Select from '../../Components/Select.vue';
import Textarea from '../../Components/Textarea.vue';

const props = defineProps({
    callback: { type: Object, required: true },
    statuses: { type: Array, required: true },
});

const form = useForm({
    status: props.callback.status,
    internal_notes: props.callback.internal_notes ?? '',
});

function submit() {
    form.put(route('admin.callbacks.update', props.callback.id), { preserveScroll: true });
}
</script>

<template>
    <Head :title="`Rappel : ${callback.name}`" />

    <PageHeader :title="`Rappel : ${callback.name}`" :subtitle="`Demandé le ${callback.created_at}${callback.contacted_at ? ` · rappelé le ${callback.contacted_at}` : ''}`">
        <Link :href="route('admin.callbacks.index')" class="text-sm font-medium text-slate-600 hover:text-slate-900">← Rappels</Link>
    </PageHeader>

    <div class="grid gap-6 xl:grid-cols-[1fr_22rem]">
        <div class="space-y-6">
            <Card title="Demande">
                <dl class="grid gap-3 text-sm sm:grid-cols-2">
                    <div><dt class="text-slate-500">Sujet</dt><dd class="font-medium">{{ callback.subject }}</dd></div>
                    <div><dt class="text-slate-500">Créneau souhaité</dt><dd class="font-medium">{{ callback.time_slot }}</dd></div>
                </dl>
                <p v-if="callback.note" class="mt-4 whitespace-pre-line rounded-lg bg-slate-50 p-4 text-sm text-slate-800">{{ callback.note }}</p>
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

        <Card title="Contact">
            <p class="font-medium">{{ callback.name }}</p>
            <p class="mt-2 text-sm"><a :href="`tel:${callback.phone}`" class="text-lg font-semibold text-brand-700 hover:underline">{{ callback.phone }}</a></p>
        </Card>
    </div>
</template>
