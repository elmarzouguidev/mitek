<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import Button from '../../Components/Button.vue';
import Card from '../../Components/Card.vue';
import Checkbox from '../../Components/Checkbox.vue';
import FormField from '../../Components/FormField.vue';
import PageHeader from '../../Components/PageHeader.vue';
import Select from '../../Components/Select.vue';
import Textarea from '../../Components/Textarea.vue';
import TextInput from '../../Components/TextInput.vue';

const props = defineProps({
    service: { type: Object, default: null },
    icons: { type: Array, required: true },
});

const isEdit = props.service !== null;

const form = useForm({
    title: props.service?.title ?? '',
    icon: props.service?.icon ?? 'wrench',
    summary: props.service?.summary ?? '',
    bullets: props.service?.bullets ?? [''],
    content: props.service?.content ?? '',
    meta_title: props.service?.meta_title ?? '',
    meta_description: props.service?.meta_description ?? '',
    sort_order: props.service?.sort_order ?? 0,
    is_active: props.service?.is_active ?? true,
});

const iconOptions = props.icons.map((icon) => ({ id: icon, name: icon }));

function submit() {
    if (isEdit) {
        form.put(route('admin.services.update', props.service.id));
    } else {
        form.post(route('admin.services.store'));
    }
}

const error = (key) => form.errors[key] ?? null;
</script>

<template>
    <Head :title="isEdit ? 'Modifier le service' : 'Nouveau service'" />

    <PageHeader :title="isEdit ? service.title : 'Nouveau service'" />

    <form class="max-w-3xl space-y-6" @submit.prevent="submit">
        <Card title="Service">
            <div class="grid gap-4 sm:grid-cols-2">
                <FormField class="sm:col-span-2" label="Titre" for="title" :error="error('title')">
                    <TextInput id="title" v-model="form.title" :invalid="!!error('title')" />
                </FormField>
                <FormField label="Icône" for="icon" :error="error('icon')">
                    <Select id="icon" v-model="form.icon" :options="iconOptions" />
                </FormField>
                <FormField label="Ordre d'affichage" for="sort_order" :error="error('sort_order')">
                    <TextInput id="sort_order" v-model="form.sort_order" type="number" :invalid="!!error('sort_order')" />
                </FormField>
                <FormField class="sm:col-span-2" label="Résumé" for="summary" hint="Affiché sur la liste des services." :error="error('summary')">
                    <Textarea id="summary" v-model="form.summary" :rows="2" :invalid="!!error('summary')" />
                </FormField>
                <div class="sm:col-span-2"><Checkbox v-model="form.is_active" label="Service actif (visible sur le site)" /></div>
            </div>
        </Card>

        <Card title="Ce service comprend" description="Une ligne par point.">
            <div class="space-y-3">
                <div v-for="(bullet, index) in form.bullets" :key="index" class="flex items-start gap-3">
                    <div class="flex-1">
                        <TextInput v-model="form.bullets[index]" :invalid="!!error(`bullets.${index}`)" />
                        <p v-if="error(`bullets.${index}`)" class="mt-1 text-xs text-red-600">{{ error(`bullets.${index}`) }}</p>
                    </div>
                    <button type="button" class="mt-2 text-sm font-medium text-red-600 hover:underline" @click="form.bullets.splice(index, 1)">Retirer</button>
                </div>
            </div>
            <p v-if="error('bullets')" class="mt-2 text-sm text-red-600">{{ error('bullets') }}</p>
            <Button class="mt-4" variant="secondary" @click="form.bullets.push('')">Ajouter un point</Button>
        </Card>

        <Card title="Contenu détaillé" description="Facultatif. Markdown accepté (titres, listes, liens, gras).">
            <FormField label="Contenu" for="content" :error="error('content')">
                <Textarea id="content" v-model="form.content" :rows="8" :invalid="!!error('content')" />
            </FormField>
        </Card>

        <Card title="SEO" description="Laissez vide pour utiliser le titre et le résumé.">
            <div class="grid gap-4">
                <FormField label="Titre SEO" for="meta_title" :error="error('meta_title')">
                    <TextInput id="meta_title" v-model="form.meta_title" :invalid="!!error('meta_title')" />
                </FormField>
                <FormField label="Description SEO" for="meta_description" hint="160 caractères environ." :error="error('meta_description')">
                    <Textarea id="meta_description" v-model="form.meta_description" :rows="3" :invalid="!!error('meta_description')" />
                </FormField>
            </div>
        </Card>

        <div class="flex items-center gap-3">
            <Button type="submit" :processing="form.processing">Enregistrer</Button>
            <Link :href="route('admin.services.index')" class="text-sm font-medium text-slate-600 hover:text-slate-900">Annuler</Link>
        </div>
    </form>
</template>
