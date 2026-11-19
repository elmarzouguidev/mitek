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
    item: { type: Object, default: null },
    categories: { type: Array, required: true },
});

const isEdit = props.item !== null;

const form = useForm({
    faq_category_id: props.item?.faq_category_id ?? props.categories[0]?.id ?? '',
    question: props.item?.question ?? '',
    answer: props.item?.answer ?? '',
    sort_order: props.item?.sort_order ?? 0,
    is_active: props.item?.is_active ?? true,
});

function submit() {
    if (isEdit) {
        form.put(route('admin.faq.items.update', props.item.id));
    } else {
        form.post(route('admin.faq.items.store'));
    }
}
</script>

<template>
    <Head :title="isEdit ? 'Modifier la question' : 'Nouvelle question'" />

    <PageHeader :title="isEdit ? 'Modifier la question' : 'Nouvelle question'" />

    <form class="max-w-3xl space-y-6" @submit.prevent="submit">
        <Card>
            <div class="space-y-4">
                <FormField label="Catégorie" for="category" :error="form.errors.faq_category_id"><Select id="category" v-model="form.faq_category_id" :options="categories" /></FormField>
                <FormField label="Question" for="question" :error="form.errors.question"><TextInput id="question" v-model="form.question" :invalid="!!form.errors.question" /></FormField>
                <FormField label="Réponse" for="answer" hint="Markdown accepté. Jetons possibles : {{cod_limit}}, {{dispatch_days}}, {{delivery_estimate}}, {{bank_transfer_delay}}…" :error="form.errors.answer"><Textarea id="answer" v-model="form.answer" :rows="6" :invalid="!!form.errors.answer" /></FormField>
                <div class="grid gap-4 sm:grid-cols-2">
                    <FormField label="Ordre" for="sort_order" :error="form.errors.sort_order"><TextInput id="sort_order" v-model="form.sort_order" type="number" :invalid="!!form.errors.sort_order" /></FormField>
                    <div class="flex items-end pb-2"><Checkbox v-model="form.is_active" label="Question visible sur le site" /></div>
                </div>
            </div>
        </Card>
        <div class="flex items-center gap-3">
            <Button type="submit" :processing="form.processing">Enregistrer</Button>
            <Link :href="route('admin.faq.index')" class="text-sm font-medium text-slate-600 hover:text-slate-900">Annuler</Link>
        </div>
    </form>
</template>
