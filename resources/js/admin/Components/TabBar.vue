<script setup>
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';

const props = defineProps({
    tabs: { type: Array, required: true },
    modelValue: { type: String, required: true },
    errorKeys: { type: Array, default: () => [] },
});
const emit = defineEmits(['update:modelValue']);

const scroller = ref(null);
const canScrollLeft = ref(false);
const canScrollRight = ref(false);

function updateEdges() {
    const el = scroller.value;
    if (!el) {
        return;
    }
    canScrollLeft.value = el.scrollLeft > 4;
    canScrollRight.value = el.scrollLeft + el.clientWidth < el.scrollWidth - 4;
}

function revealActive() {
    const el = scroller.value;
    const active = el?.querySelector('[aria-selected="true"]');
    if (!el || !active) {
        return;
    }
    const target = active.offsetLeft - (el.clientWidth - active.offsetWidth) / 2;
    el.scrollTo({ left: Math.max(0, target), behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' });
}

watch(() => props.modelValue, () => nextTick(revealActive));
onMounted(() => {
    nextTick(() => {
        revealActive();
        updateEdges();
    });
    window.addEventListener('resize', updateEdges);
});
onBeforeUnmount(() => window.removeEventListener('resize', updateEdges));
</script>

<template>
    <div class="relative border-b border-slate-200">
        <div ref="scroller" class="flex gap-1 overflow-x-auto overscroll-x-contain [scrollbar-width:none] [&::-webkit-scrollbar]:hidden" role="tablist" @scroll.passive="updateEdges">
            <button
                v-for="item in tabs"
                :key="item.key"
                type="button"
                role="tab"
                :aria-selected="modelValue === item.key"
                :class="modelValue === item.key ? 'border-brand-700 text-brand-700' : 'border-transparent text-slate-600 hover:text-slate-900'"
                class="-mb-px flex shrink-0 items-center gap-1.5 border-b-2 px-4 py-2.5 text-sm font-medium"
                @click="emit('update:modelValue', item.key)"
            >
                {{ item.label }}
                <span v-if="errorKeys.includes(item.key)" class="size-2 rounded-full bg-red-500" title="Erreurs dans cet onglet" />
            </button>
        </div>
        <div v-show="canScrollLeft" class="pointer-events-none absolute inset-y-0 left-0 w-8 bg-gradient-to-r from-white to-transparent" aria-hidden="true" />
        <div v-show="canScrollRight" class="pointer-events-none absolute inset-y-0 right-0 w-8 bg-gradient-to-l from-white to-transparent" aria-hidden="true" />
    </div>
</template>
