<script setup>
import { ref } from 'vue';

const props = defineProps({
    title: {
        type: String,
        default: 'Document Viewer',
    },
    fileUrl: {
        type: String,
        required: true,
        default: '',
    },
    description: {
        type: String,
        default: '',
    }
});

const isFullscreen = ref(false);

const toggleFullscreen = () => {
    isFullscreen.value = !isFullscreen.value;
};
</script>

<template>
    <div
        :class="[
            'flex flex-col gap-4 rounded-xl border border-sidebar-border/70 bg-card p-4 transition-all dark:bg-card/50',
            isFullscreen ? 'fixed inset-4 z-50 shadow-2xl bg-card' : ''
        ]">
        <div class="flex items-center justify-between border-b border-sidebar-border/50 pb-3">
            <div class="flex flex-col">
                <h3 class="text-sm font-bold text-foreground">{{ title }}</h3>
                <p v-if="description" class="text-xs text-muted-foreground">{{ description }}</p>
            </div>

            <div>
                <button type="button" @click="toggleFullscreen"
                    class="rounded-lg border border-sidebar-border/70 bg-sidebar px-3 py-1.5 text-xs font-medium text-foreground hover:bg-sidebar-accent transition-colors">
                    {{ isFullscreen ? 'Exit Fullscreen ↙' : 'Fullscreen Mode ↗' }}
                </button>
                <a v-if="fileUrl" :href="fileUrl" target="_blank"
                    class="rounded-lg bg-primary px-3 py-1.5 text-xs font-medium text-primary-foreground hover:bg-primary/90 transition-colors">
                    Open PDF ↗
                </a>
            </div>
        </div>

        <!-- Embedded Viewer Iframe -->
        <div :class="['w-full overflow-hidden rounded-lg border border-sidebar-border/50 bg-sidebar/20', isFullscreen ? 'flex-1' : 'h-[650px]']">
            <iframe v-if="fileUrl" :src="fileUrl" class="h-full w-full border-none" title="PDF Document" />
            <div v-else class="flex h-full items-center justify-center text-xs text-muted-foreground">No PDF URL configured.</div>
        </div>
    </div>
</template>