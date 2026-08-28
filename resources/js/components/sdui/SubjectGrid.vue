<script setup>
import { computed, inject } from 'vue';

const props = defineProps({
    columns: {
        type: [Number, String],
        default: 3,
    },
    items: {
        type: Array,
        required: true,
        default: () => [],
    },
});

const gridClass = computed(() => {
    const cols = Number(props.columns);

    if (cols === 1) {
        return 'grid-cols-1';
    }

    if (cols === 2) {
        return 'grid-cols-1 md:grid-cols-2';
    }

    if (cols === 4) {
        return 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-4';
    }

    return 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3';
});

const sduiContext = inject('sduiContext', null);

const startItem = (item) => {
    const moduleId = item.moduleId || item.module?.id;

    if (moduleId && sduiContext) {
        sduiContext.launchModule(moduleId);
    }
};
</script>

<template>
    <div :class="['grid gap-4', gridClass]">
        <div
            v-for="(item, index) in items"
            :key="item.id || index"
            class="flex flex-col justify-between rounded-xl border border-sidebar-border/70 bg-card p-5 shadow-xs transition-all hover:border-sidebar-border hover:shadow-md dark:bg-card/50"
        >
            <div>
                <div>
                    <h3>
                        {{ item.title || item.module?.title }}
                    </h3>
                    <span
                        v-if="item.badge || item.module?.type"
                        class="shrink-0 rounded-md bg-secondary px-2 py-0.5 text-xs font-medium text-secondary-foreground"
                    >
                        {{ item.badge || item.module?.type }}
                    </span>
                </div>
                <p
                    v-if="
                        item.description ||
                        item.module?.content_meta?.description
                    "
                    class="line-clamp-2 text-xs text-muted-foreground"
                >
                    {{
                        item.description ||
                        item.module?.content_meta?.description
                    }}
                </p>
            </div>
            <div
                class="mt-5 flex items-center justify-between border-t border-sidebar-border/50 pt-4"
            >
                <span>{{
                    item.module?.content_meta?.duration_minutes
                        ? `${item.module.content_meta.duration_minutes} mins`
                        : 'Self-Paced'
                }}</span>
                <button
                    @click="startItem(item)"
                    type="button"
                    class="inline-flex items-center justify-center rounded-lg bg-primary px-3.5 py-1.5 text-xs font-medium text-primary-foreground shadow-xs transition-colors hover:bg-primary/90"
                >
                    {{ item.actionLabel || 'Start Module' }}
                </button>
            </div>
        </div>
    </div>
</template>
