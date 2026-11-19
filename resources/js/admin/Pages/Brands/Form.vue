<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import Button from '../../Components/Button.vue';
import Card from '../../Components/Card.vue';
import Checkbox from '../../Components/Checkbox.vue';
import FormField from '../../Components/FormField.vue';
import ImageInput from '../../Components/ImageInput.vue';
import PageHeader from '../../Components/PageHeader.vue';
import TextInput from '../../Components/TextInput.vue';

const props = defineProps({
    brand: { type: Object, default: null },
});

const isEdit = props.brand !== null;

const form = useForm({
    _method: isEdit ? 'put' : 'post',
    name: props.brand?.name ?? '',
    is_active: props.brand?.is_active ?? true,
    logo: null,
    remove_logo: false,
});

function submit() {
    form.post(isEdit ? route('admin.brands.update', props.brand.id) : route('admin.brands.store'), { forceFormData: true });
}
</script>

<template>
    <Head :title="isEdit ? 'Modifier la marque' : 'Nouvelle marque'" />

    <PageHeader :title="isEdit ? `Modifier « ${brand.name} »` : 'Nouvelle marque'" />

    <form class="max-w-xl space-y-6" @submit.prevent="submit">
        <Card>
            <div class="space-y-4">
                <FormField label="Nom" for="name" :error="form.errors.name">
                    <TextInput id="name" v-model="form.name" :invalid="!!form.errors.name" />
                </FormField>
                <ImageInput v-model="form.logo" v-model:remove="form.remove_logo" label="Logo" :current-url="brand?.logo_url" :error="form.errors.logo" />
                <Checkbox v-model="form.is_active" label="Marque active" />
            </div>
        </Card>

        <div class="flex items-center gap-3">
            <Button type="submit" :processing="form.processing">Enregistrer</Button>
            <Link :href="route('admin.brands.index')" class="text-sm font-medium text-slate-600 hover:text-slate-900">Annuler</Link>
        </div>
    </form>
</template>
