<script setup>
import SduiRenderer from './SduiRenderer.vue';

defineProps({
    children: {
        type: Array,
        required: true,
        default: () => [],
    },
});
</script>

<template>
    <div class="flex flex-col gap-6">
        <SduiRenderer
            v-for="(child, index) in children"
            :key="index"
            :node="child"
        />
    </div>
</template>
