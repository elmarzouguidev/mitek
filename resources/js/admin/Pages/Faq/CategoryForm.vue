<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import Button from '../../Components/Button.vue';
import Card from '../../Components/Card.vue';
import FormField from '../../Components/FormField.vue';
import PageHeader from '../../Components/PageHeader.vue';
import TextInput from '../../Components/TextInput.vue';

const props = defineProps({
    category: { type: Object, default: null },
});

const isEdit = props.category !== null;

const form = useForm({
    name: props.category?.name ?? '',
    slug: props.category?.slug ?? '',
    sort_order: props.category?.sort_order ?? 0,
});

function submit() {
    if (isEdit) {
        form.put(route('admin.faq.categories.update', props.category.id));
    } else {
        form.post(route('admin.faq.categories.store'));
    }
}
</script>

<template>
    <Head :title="isEdit ? 'Modifier la catégorie' : 'Nouvelle catégorie'" />

    <PageHeader :title="isEdit ? category.name : 'Nouvelle catégorie'" />

    <form class="max-w-xl space-y-6" @submit.prevent="submit">
        <Card>
            <div class="space-y-4">
                <FormField label="Nom" for="name" :error="form.errors.name"><TextInput id="name" v-model="form.name" :invalid="!!form.errors.name" /></FormField>
                <FormField label="Identifiant" for="slug" hint="Minuscules et tirets. « paiement », « livraison » et « services » alimentent les pages du même nom." :error="form.errors.slug"><TextInput id="slug" v-model="form.slug" :invalid="!!form.errors.slug" /></FormField>
                <FormField label="Ordre" for="sort_order" :error="form.errors.sort_order"><TextInput id="sort_order" v-model="form.sort_order" type="number" :invalid="!!form.errors.sort_order" /></FormField>
            </div>
        </Card>
        <div class="flex items-center gap-3">
            <Button type="submit" :processing="form.processing">Enregistrer</Button>
            <Link :href="route('admin.faq.index')" class="text-sm font-medium text-slate-600 hover:text-slate-900">Annuler</Link>
        </div>
    </form>
</template>
