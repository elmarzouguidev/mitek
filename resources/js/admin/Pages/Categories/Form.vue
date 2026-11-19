<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import Button from '../../Components/Button.vue';
import Card from '../../Components/Card.vue';
import Checkbox from '../../Components/Checkbox.vue';
import FormField from '../../Components/FormField.vue';
import ImageInput from '../../Components/ImageInput.vue';
import PageHeader from '../../Components/PageHeader.vue';
import Select from '../../Components/Select.vue';
import Textarea from '../../Components/Textarea.vue';
import TextInput from '../../Components/TextInput.vue';

const props = defineProps({
    category: { type: Object, default: null },
    parents: { type: Array, required: true },
});

const isEdit = props.category !== null;

const form = useForm({
    _method: isEdit ? 'put' : 'post',
    name: props.category?.name ?? '',
    short_name: props.category?.short_name ?? '',
    parent_id: props.category?.parent_id ?? '',
    description: props.category?.description ?? '',
    sort_order: props.category?.sort_order ?? 0,
    is_active: props.category?.is_active ?? true,
    meta_title: props.category?.meta_title ?? '',
    meta_description: props.category?.meta_description ?? '',
    image: null,
    remove_image: false,
});

function submit() {
    form.post(isEdit ? route('admin.categories.update', props.category.id) : route('admin.categories.store'), { forceFormData: true });
}
</script>

<template>
    <Head :title="isEdit ? 'Modifier la catégorie' : 'Nouvelle catégorie'" />

    <PageHeader :title="isEdit ? `Modifier « ${category.name} »` : 'Nouvelle catégorie'" />

    <form class="max-w-3xl space-y-6" @submit.prevent="submit">
        <Card title="Général">
            <div class="grid gap-4 sm:grid-cols-2">
                <FormField label="Nom" for="name" :error="form.errors.name">
                    <TextInput id="name" v-model="form.name" :invalid="!!form.errors.name" />
                </FormField>
                <FormField label="Nom court (menu)" for="short_name" hint="Libellé compact de la barre de navigation. Vide : le nom complet." :error="form.errors.short_name">
                    <TextInput id="short_name" v-model="form.short_name" :invalid="!!form.errors.short_name" />
                </FormField>
                <FormField label="Catégorie parente" for="parent_id" hint="Laissez vide pour une catégorie principale." :error="form.errors.parent_id">
                    <Select id="parent_id" v-model="form.parent_id" :options="parents" placeholder="— Aucune (catégorie principale) —" :invalid="!!form.errors.parent_id" />
                </FormField>
                <FormField class="sm:col-span-2" label="Description" for="description" :error="form.errors.description">
                    <Textarea id="description" v-model="form.description" :invalid="!!form.errors.description" />
                </FormField>
                <FormField label="Ordre d'affichage" for="sort_order" :error="form.errors.sort_order">
                    <TextInput id="sort_order" v-model="form.sort_order" type="number" :invalid="!!form.errors.sort_order" />
                </FormField>
                <div class="flex items-end pb-2"><Checkbox v-model="form.is_active" label="Catégorie active (visible sur le site)" /></div>
                <div class="sm:col-span-2">
                    <ImageInput v-model="form.image" v-model:remove="form.remove_image" label="Image" :current-url="category?.image_url" :error="form.errors.image" />
                </div>
            </div>
        </Card>

        <Card title="SEO" description="Laissez vide pour utiliser le nom et la description.">
            <div class="grid gap-4">
                <FormField label="Titre SEO" for="meta_title" :error="form.errors.meta_title">
                    <TextInput id="meta_title" v-model="form.meta_title" :invalid="!!form.errors.meta_title" />
                </FormField>
                <FormField label="Description SEO" for="meta_description" hint="160 caractères environ." :error="form.errors.meta_description">
                    <Textarea id="meta_description" v-model="form.meta_description" :rows="3" :invalid="!!form.errors.meta_description" />
                </FormField>
            </div>
        </Card>

        <div class="flex items-center gap-3">
            <Button type="submit" :processing="form.processing">Enregistrer</Button>
            <Link :href="route('admin.categories.index')" class="text-sm font-medium text-slate-600 hover:text-slate-900">Annuler</Link>
        </div>
    </form>
</template>
