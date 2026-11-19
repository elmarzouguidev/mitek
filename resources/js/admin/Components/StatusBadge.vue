<script setup>
import Badge from './Badge.vue';

/**
 * Order status badge; `status` is {value, label, tone} as sent by the server.
 */
defineProps({
    status: { type: Object, required: true },
});

const tones = { amber: 'amber', green: 'green', red: 'red', blue: 'blue' };
</script>

<template>
    <Badge :tone="tones[status.tone] ?? 'slate'">{{ status.label }}</Badge>
</template>
