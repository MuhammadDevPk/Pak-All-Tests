<script setup>
import { Head } from '@inertiajs/vue3';
import SduiRenderer from '@/components/sdui/SduiRenderer.vue';

const props = defineProps({
    sdui: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <Head :title="sdui?.meta?.domain?.name || 'Dashboard'" />
    <div class="flex h-full flex-1 flex-col gap-4 p-4 md:p-6">
        <div class="flex flex-col gap-1 border-b border-sidebar-border/70 pb-4 dark:border-sidebar-border">
             <h1 class="text-2xl font-bold tracking-tight text-foreground">
                {{ sdui?.meta?.domain?.name }}
            </h1>
            <p class="text-xs text-muted-foreground">
                Path: <span>{{ sdui?.meta?.domain?.path }}</span>
            </p>
        </div>
    </div>

    <div class="mt-2 flex-1">
        <SduiRenderer v-if="sdui?.layout" :node="sdui.layout" />
    </div>
</template>