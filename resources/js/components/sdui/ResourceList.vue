<script setup>
import { inject } from 'vue';

defineProps({
    title: {
        type: String,
        default: 'Study Resources & Notes',
    },
    items: {
        type: Array,
        required: true,
        default: () => [],
    },
});

const sduiContext = inject('sduiContext', null);

const startResource = (item) => {
    if (item.moduleId && sduiContext) {
        sduiContext.launchModule(item.moduleId);
    } else if (item.fileUrl) {
        window.open(item.fileUrl, '_blank', 'noopener,noreferrer');
    }
};
</script>

<template>
    <div
        class="flex flex-col gap-4 rounded-xl border border-sidebar-border/70 bg-card p-5 dark:bg-card/50"
    >
        <h3 v-if="title" class="text-base font-semibold text-foreground">
            {{ title }}
        </h3>
        <div>
            <div
                v-for="(item, index) in items"
                :key="item.id || index"
                class="flex items-center justify-between py-3 first:pt-0 last:pb-0"
            >
                <div class="flex items-center gap-3">
                    <div
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary"
                    >
                        <svg
                            class="h-5 w-5"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="1.5"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"
                            />
                        </svg>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-sm font-medium text-foreground">{{
                            item.title
                        }}</span>
                        <span class="text-xs text-muted-foreground"
                            >{{ item.fileType || 'PDF Document' }}
                            <template v-if="item.fileSize"
                                >• {{ item.fileSize }}</template
                            >
                        </span>
                    </div>
                </div>
                <button
                    @click="startResource(item)"
                    type="button"
                    class="rounded-lg border border-sidebar-border/70 bg-sidebar px-3 py-1.5 text-xs font-medium text-foreground transition-colors hover:bg-sidebar-accent"
                >
                    {{ item.actionLabel || 'View Document' }}
                </button>
            </div>
        </div>
    </div>
</template>
