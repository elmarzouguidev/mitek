<script setup>
import { computed, ref } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import Button from '../../Components/Button.vue';
import Card from '../../Components/Card.vue';
import Checkbox from '../../Components/Checkbox.vue';
import ConfirmDialog from '../../Components/ConfirmDialog.vue';
import FileUpload from '../../Components/FileUpload.vue';
import FormField from '../../Components/FormField.vue';
import PageHeader from '../../Components/PageHeader.vue';
import Select from '../../Components/Select.vue';
import TagInput from '../../Components/TagInput.vue';
import Textarea from '../../Components/Textarea.vue';
import TextInput from '../../Components/TextInput.vue';

const props = defineProps({
    product: { type: Object, default: null },
    options: { type: Object, required: true },
    defaultVatRate: { type: Number, required: true },
});

const isEdit = props.product !== null;

const form = useForm({
    category_id: props.product?.category_id ?? '',
    brand_id: props.product?.brand_id ?? '',
    reference: props.product?.reference ?? '',
    name: props.product?.name ?? '',
    excerpt: props.product?.excerpt ?? '',
    description: props.product?.description ?? '',
    price_ht: props.product?.price_ht ?? '',
    vat_rate: props.product?.vat_rate ?? '',
    stock_quantity: props.product?.stock_quantity ?? 0,
    low_stock_threshold: props.product?.low_stock_threshold ?? 5,
    weight_grams: props.product?.weight_grams ?? 0,
    is_active: props.product?.is_active ?? true,
    is_featured: props.product?.is_featured ?? false,
    meta_title: props.product?.meta_title ?? '',
    meta_description: props.product?.meta_description ?? '',
    tiers: props.product?.tiers ?? [],
    specifications: props.product?.specifications ?? [],
    compatible_models: props.product?.compatible_models ?? [],
});

const tabs = [
    { key: 'general', label: 'Général', fields: ['category_id', 'brand_id', 'reference', 'name', 'excerpt', 'description', 'is_active', 'is_featured'] },
    { key: 'prices', label: 'Prix et paliers', fields: ['price_ht', 'vat_rate', 'tiers'] },
    { key: 'stock', label: 'Stock', fields: ['stock_quantity', 'low_stock_threshold', 'weight_grams'] },
    { key: 'images', label: 'Images', fields: ['images'] },
    { key: 'specifications', label: 'Caractéristiques', fields: ['specifications'] },
    { key: 'compatibility', label: 'Compatibilité', fields: ['compatible_models'] },
    { key: 'seo', label: 'SEO', fields: ['meta_title', 'meta_description'] },
];

const activeTab = ref('general');
const confirmingDelete = ref(false);

const tabsWithErrors = computed(() =>
    tabs.filter((tab) => Object.keys(form.errors).some((key) => tab.fields.some((field) => key === field || key.startsWith(`${field}.`)))).map((tab) => tab.key),
);

function submit() {
    if (isEdit) {
        form.put(route('admin.products.update', props.product.id), { preserveScroll: true });
    } else {
        form.post(route('admin.products.store'));
    }
}

function addTier() {
    form.tiers.push({ min_quantity: '', price_ht: '' });
}

function addSpecification() {
    form.specifications.push({ name: '', value: '' });
}

function destroy() {
    router.delete(route('admin.products.destroy', props.product.id), { onFinish: () => (confirmingDelete.value = false) });
}

const error = (key) => form.errors[key] ?? null;
</script>

<template>
    <Head :title="isEdit ? 'Modifier le produit' : 'Nouveau produit'" />

    <PageHeader :title="isEdit ? product.name : 'Nouveau produit'" :subtitle="isEdit ? `Référence ${product.reference}` : null">
        <Link :href="route('admin.products.index')" class="text-sm font-medium text-slate-600 hover:text-slate-900">← Retour à la liste</Link>
    </PageHeader>

    <form class="max-w-4xl" @submit.prevent="submit">
        <div class="mb-6 flex gap-1 overflow-x-auto border-b border-slate-200" role="tablist">
            <button
                v-for="tab in tabs"
                :key="tab.key"
                type="button"
                role="tab"
                :aria-selected="activeTab === tab.key"
                :class="activeTab === tab.key ? 'border-brand-700 text-brand-700' : 'border-transparent text-slate-600 hover:text-slate-900'"
                class="-mb-px flex shrink-0 items-center gap-1.5 border-b-2 px-4 py-2.5 text-sm font-medium"
                @click="activeTab = tab.key"
            >
                {{ tab.label }}
                <span v-if="tabsWithErrors.includes(tab.key)" class="size-2 rounded-full bg-red-500" title="Erreurs dans cet onglet" />
            </button>
        </div>

        <Card v-show="activeTab === 'general'" title="Informations générales">
            <div class="grid gap-4 sm:grid-cols-2">
                <FormField class="sm:col-span-2" label="Nom" for="name" :error="error('name')">
                    <TextInput id="name" v-model="form.name" :invalid="!!error('name')" />
                </FormField>
                <FormField label="Référence (SKU)" for="reference" hint="Laissez vide pour générer une référence automatiquement." :error="error('reference')">
                    <TextInput id="reference" v-model="form.reference" :invalid="!!error('reference')" />
                </FormField>
                <FormField label="Catégorie" for="category_id" :error="error('category_id')">
                    <Select id="category_id" v-model="form.category_id" :options="options.categories" placeholder="— Choisir —" :invalid="!!error('category_id')" />
                </FormField>
                <FormField label="Marque" for="brand_id" :error="error('brand_id')">
                    <Select id="brand_id" v-model="form.brand_id" :options="options.brands" placeholder="— Aucune —" :invalid="!!error('brand_id')" />
                </FormField>
                <div class="flex flex-col justify-end gap-2 pb-1">
                    <Checkbox v-model="form.is_active" label="Produit actif (visible sur le site)" />
                    <Checkbox v-model="form.is_featured" label="Produit vedette" />
                </div>
                <FormField class="sm:col-span-2" label="Description courte" for="excerpt" :error="error('excerpt')">
                    <Textarea id="excerpt" v-model="form.excerpt" :rows="2" :invalid="!!error('excerpt')" />
                </FormField>
                <FormField class="sm:col-span-2" label="Description détaillée" for="description" hint="Séparez les paragraphes par une ligne vide." :error="error('description')">
                    <Textarea id="description" v-model="form.description" :rows="8" :invalid="!!error('description')" />
                </FormField>
            </div>
        </Card>

        <div v-show="activeTab === 'prices'" class="space-y-6">
            <Card title="Prix de base">
                <div class="grid gap-4 sm:grid-cols-2">
                    <FormField label="Prix unitaire HT (DH)" for="price_ht" :error="error('price_ht')">
                        <TextInput id="price_ht" v-model="form.price_ht" type="number" :invalid="!!error('price_ht')" />
                    </FormField>
                    <FormField label="TVA (%)" for="vat_rate" :hint="`Laissez vide pour le taux par défaut (${defaultVatRate} %).`" :error="error('vat_rate')">
                        <TextInput id="vat_rate" v-model="form.vat_rate" type="number" :invalid="!!error('vat_rate')" />
                    </FormField>
                </div>
            </Card>

            <Card title="Tarifs dégressifs" description="Prix unitaire HT à partir d'une quantité. Un palier ne peut pas dépasser le prix de base.">
                <div v-if="form.tiers.length" class="space-y-3">
                    <div v-for="(tier, index) in form.tiers" :key="index" class="grid grid-cols-[1fr_1fr_auto] items-start gap-3">
                        <FormField label="À partir de (quantité)" :error="error(`tiers.${index}.min_quantity`)">
                            <TextInput v-model="tier.min_quantity" type="number" :invalid="!!error(`tiers.${index}.min_quantity`)" />
                        </FormField>
                        <FormField label="Prix unitaire HT (DH)" :error="error(`tiers.${index}.price_ht`)">
                            <TextInput v-model="tier.price_ht" type="number" :invalid="!!error(`tiers.${index}.price_ht`)" />
                        </FormField>
                        <button type="button" class="mt-7 text-sm font-medium text-red-600 hover:underline" @click="form.tiers.splice(index, 1)">Retirer</button>
                    </div>
                </div>
                <p v-else class="text-sm text-slate-500">Aucun palier : le prix de base s'applique à toutes les quantités.</p>
                <p v-if="error('tiers')" class="mt-2 text-sm text-red-600">{{ error('tiers') }}</p>
                <Button class="mt-4" variant="secondary" @click="addTier">Ajouter un palier</Button>
            </Card>
        </div>

        <Card v-show="activeTab === 'stock'" title="Stock et logistique">
            <div class="grid gap-4 sm:grid-cols-3">
                <FormField label="Quantité en stock" for="stock_quantity" :error="error('stock_quantity')">
                    <TextInput id="stock_quantity" v-model="form.stock_quantity" type="number" :invalid="!!error('stock_quantity')" />
                </FormField>
                <FormField label="Seuil de stock bas" for="low_stock_threshold" :error="error('low_stock_threshold')">
                    <TextInput id="low_stock_threshold" v-model="form.low_stock_threshold" type="number" :invalid="!!error('low_stock_threshold')" />
                </FormField>
                <FormField label="Poids (grammes)" for="weight_grams" hint="Sert au calcul des frais de livraison." :error="error('weight_grams')">
                    <TextInput id="weight_grams" v-model="form.weight_grams" type="number" :invalid="!!error('weight_grams')" />
                </FormField>
            </div>
        </Card>

        <Card v-show="activeTab === 'images'" title="Images" description="La première image est l'image principale. Le texte alternatif est le nom du produit.">
            <FileUpload
                v-if="isEdit"
                :images="product.images"
                :upload-url="route('admin.products.images.store', product.id)"
                :reorder-url="route('admin.products.images.reorder', product.id)"
                :destroy-url="(id) => route('admin.products.images.destroy', [product.id, id])"
                :errors="$page.props.errors"
            />
            <p v-else class="text-sm text-slate-600">Enregistrez d'abord le produit : vous pourrez ensuite ajouter ses images.</p>
        </Card>

        <Card v-show="activeTab === 'specifications'" title="Caractéristiques" description="Affichées sous forme de tableau sur la fiche produit.">
            <div v-if="form.specifications.length" class="space-y-3">
                <div v-for="(specification, index) in form.specifications" :key="index" class="grid grid-cols-[1fr_1fr_auto] items-start gap-3">
                    <FormField label="Nom" :error="error(`specifications.${index}.name`)">
                        <TextInput v-model="specification.name" :invalid="!!error(`specifications.${index}.name`)" />
                    </FormField>
                    <FormField label="Valeur" :error="error(`specifications.${index}.value`)">
                        <TextInput v-model="specification.value" :invalid="!!error(`specifications.${index}.value`)" />
                    </FormField>
                    <button type="button" class="mt-7 text-sm font-medium text-red-600 hover:underline" @click="form.specifications.splice(index, 1)">Retirer</button>
                </div>
            </div>
            <p v-else class="text-sm text-slate-500">Aucune caractéristique.</p>
            <Button class="mt-4" variant="secondary" @click="addSpecification">Ajouter une caractéristique</Button>
        </Card>

        <Card v-show="activeTab === 'compatibility'" title="Imprimantes compatibles" description="Pour les toners et cartouches : ces modèles sont consultables et recherchables sur le site.">
            <FormField label="Modèles" for="compatible" hint="Tapez un modèle puis Entrée (ex. HP Color LaserJet Pro M476dn)." :error="error('compatible_models')">
                <TagInput id="compatible" v-model="form.compatible_models" placeholder="Ajouter un modèle…" />
            </FormField>
        </Card>

        <Card v-show="activeTab === 'seo'" title="Référencement" description="Laissez vide pour utiliser le nom et la description courte.">
            <div class="grid gap-4">
                <FormField label="Titre SEO" for="meta_title" :error="error('meta_title')">
                    <TextInput id="meta_title" v-model="form.meta_title" :invalid="!!error('meta_title')" />
                </FormField>
                <FormField label="Description SEO" for="meta_description" hint="160 caractères environ." :error="error('meta_description')">
                    <Textarea id="meta_description" v-model="form.meta_description" :rows="3" :invalid="!!error('meta_description')" />
                </FormField>
            </div>
        </Card>

        <div class="mt-6 flex items-center gap-3">
            <Button type="submit" :processing="form.processing">Enregistrer</Button>
            <Link :href="route('admin.products.index')" class="text-sm font-medium text-slate-600 hover:text-slate-900">Annuler</Link>
            <button v-if="isEdit" type="button" class="ml-auto text-sm font-medium text-red-600 hover:underline" @click="confirmingDelete = true">Supprimer le produit</button>
        </div>
    </form>

    <ConfirmDialog
        :show="confirmingDelete"
        title="Supprimer ce produit ?"
        message="Le produit disparaît du site et de l'administration (suppression logique)."
        confirm-label="Supprimer"
        danger
        @confirm="destroy"
        @cancel="confirmingDelete = false"
    />
</template>
