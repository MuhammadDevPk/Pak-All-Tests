<script setup>
import { computed } from 'vue';
import { componentRegistry } from './componentRegistry.js';

const props = defineProps({
    node: {
        type: Object,
        required: true,
        default: () => ({}),
    },
});

// Resolve string type to Vue Component
const resolvedComponent = computed(() => {
    // props.node.type: Contains the string name of the component to load (e.g. 'EmptyStateLayout', 'TabbedLayout', 'SubjectGrid').
    if (!props.node || !props.node.type) {
        return componentRegistry.EmptyStateLayout;
    }

    const comp = componentRegistry[props.node.type];

    if (!comp) {
        console.warn(
            `[SDUI Engine] Unknown component type: "${props.node.type}". Falling back to EmptyStateLayout.`,
        );

        return componentRegistry.EmptyStateLayout;
    }

    return comp;
});

const componentProps = computed(() => {
    // props.node?.props: Contains an object of properties that should be passed to that component (e.g. { title: '...', message: '...' }).
    return props.node?.props || {};
});
</script>

<template>
    <component :is="resolvedComponent" v-bind="componentProps" />
</template>
