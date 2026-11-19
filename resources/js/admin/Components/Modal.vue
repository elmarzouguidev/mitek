<script setup>
import { nextTick, onBeforeUnmount, ref, watch } from 'vue';

const props = defineProps({
    show: { type: Boolean, default: false },
    maxWidth: { type: String, default: 'md' },
    closeable: { type: Boolean, default: true },
});

const emit = defineEmits(['close']);
const panel = ref(null);

const widths = { sm: 'max-w-sm', md: 'max-w-md', lg: 'max-w-lg', xl: 'max-w-xl' };

function close() {
    if (props.closeable) {
        emit('close');
    }
}

function onKeydown(event) {
    if (event.key === 'Escape') {
        close();
    }
}

watch(
    () => props.show,
    async (visible) => {
        document.body.style.overflow = visible ? 'hidden' : '';

        if (visible) {
            document.addEventListener('keydown', onKeydown);
            await nextTick();
            panel.value?.focus();
        } else {
            document.removeEventListener('keydown', onKeydown);
        }
    },
);

onBeforeUnmount(() => {
    document.removeEventListener('keydown', onKeydown);
    document.body.style.overflow = '';
});
</script>

<template>
    <Teleport to="body">
        <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto p-4" role="dialog" aria-modal="true">
            <div class="fixed inset-0 bg-slate-900/50" @click="close" />
            <div ref="panel" tabindex="-1" :class="widths[maxWidth]" class="relative w-full rounded-xl bg-white shadow-xl outline-none">
                <slot />
            </div>
        </div>
    </Teleport>
</template>
