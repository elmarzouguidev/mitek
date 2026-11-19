<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import Button from '../../Components/Button.vue';
import Card from '../../Components/Card.vue';
import Checkbox from '../../Components/Checkbox.vue';
import FormField from '../../Components/FormField.vue';
import PageHeader from '../../Components/PageHeader.vue';
import Textarea from '../../Components/Textarea.vue';
import TextInput from '../../Components/TextInput.vue';

const props = defineProps({
    page: { type: Object, required: true },
    tokens: { type: Array, required: true },
});

const form = useForm({
    title: props.page.title,
    content: props.page.content,
    meta_title: props.page.meta_title ?? '',
    meta_description: props.page.meta_description ?? '',
    is_published: props.page.is_published,
});

function submit() {
    form.put(route('admin.pages.update', props.page.id), { preserveScroll: true });
}
</script>

<template>
    <Head :title="`Modifier ${page.title}`" />

    <PageHeader :title="page.title">
        <a :href="page.url" target="_blank" rel="noopener" class="text-sm font-medium text-slate-600 hover:text-slate-900">Voir sur le site ↗</a>
        <Link :href="route('admin.pages.index')" class="text-sm font-medium text-slate-600 hover:text-slate-900">← Pages</Link>
    </PageHeader>

    <div class="grid gap-6 xl:grid-cols-[1fr_20rem]">
        <form class="space-y-6" @submit.prevent="submit">
            <Card title="Contenu">
                <div class="space-y-4">
                    <FormField label="Titre" for="title" :error="form.errors.title">
                        <TextInput id="title" v-model="form.title" :invalid="!!form.errors.title" />
                    </FormField>
                    <FormField label="Texte" for="content" hint="Markdown : ## titre, **gras**, - liste, [lien](https://…). Les jetons {{…}} sont remplacés par les paramètres du site." :error="form.errors.content">
                        <Textarea id="content" v-model="form.content" :rows="22" :invalid="!!form.errors.content" class="font-mono" />
                    </FormField>
                    <Checkbox v-model="form.is_published" label="Page publiée (visible sur le site et dans le plan du site)" />
                </div>
            </Card>

            <Card title="SEO" description="Laissez vide pour générer le titre et la description depuis le contenu.">
                <div class="grid gap-4">
                    <FormField label="Titre SEO" for="meta_title" :error="form.errors.meta_title">
                        <TextInput id="meta_title" v-model="form.meta_title" :invalid="!!form.errors.meta_title" />
                    </FormField>
                    <FormField label="Description SEO" for="meta_description" hint="160 caractères environ." :error="form.errors.meta_description">
                        <Textarea id="meta_description" v-model="form.meta_description" :rows="3" :invalid="!!form.errors.meta_description" />
                    </FormField>
                </div>
            </Card>

            <Button type="submit" :processing="form.processing">Enregistrer</Button>
        </form>

        <Card title="Jetons disponibles" description="Valeurs lues dans Paramètres. Une valeur vide s'affiche [À COMPLÉTER].">
            <ul class="max-h-[34rem] space-y-2 overflow-y-auto text-xs">
                <li v-for="token in tokens" :key="token.token">
                    <code class="rounded bg-slate-100 px-1.5 py-0.5 font-mono text-slate-800">{{ token.token }}</code>
                    <span class="ml-1 text-slate-500">{{ token.value }}</span>
                </li>
            </ul>
        </Card>
    </div>
</template>
