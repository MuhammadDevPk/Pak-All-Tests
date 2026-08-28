<script setup>
import { computed } from 'vue';

const props = defineProps({
    accuracy: {
        type: Number,
        default: 0,
    },
    completedCount: {
        type: Number,
        default: 0,
    },
    totalCount: {
        type: Number,
        default: 0,
    },
});

// SVG Circular Progress Constants
const radius = 36;
const circumference = 2 * Math.PI * radius;

const strokeDashoffset = computed(() => {
    const progress = Math.min(Math.max(props.accuracy, 0), 100);

    return circumference - (progress / 100) * circumference;
});
</script>

<template>
    <div
        class="flex items-center justify-between rounded-xl border border-sidebar-border/70 bg-card p-5 dark:bg-card/50"
    >
        <div class="flex flex-col gap-1.5">
            <span
                class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                >Test Accuracy</span
            >
            <span class="text-2xl font-extrabold text-foreground"
                >{{ accuracy }}%</span
            >
            <span class="text-xs text-muted-foreground">
                Completed {{ completedCount }} of {{ totalCount }} study modules
            </span>
        </div>

        <!-- Circular Progress Ring SVG -->
        <div class="relative flex items-center justify-center">
            <svg class="h-20 w-20 -rotate-90" viewBox="0 0 80 80">
                <!-- Track Ring -->
                <circle
                    cx="40"
                    cy="40"
                    :r="radius"
                    stroke="currentColor"
                    stroke-width="6"
                    fill="transparent"
                    class="text-sidebar-accent"
                />
                <!-- Progress Active Ring -->
                <circle
                    cx="40"
                    cy="40"
                    :r="radius"
                    stroke="currentColor"
                    stroke-width="6"
                    fill="transparent"
                    :stroke-dasharray="circumference"
                    :stroke-dashoffset="strokeDashoffset"
                    class="text-primary transition-all duration-500 ease-out"
                />
            </svg>
            <span class="absolute text-xs font-bold text-foreground">
                {{ completedCount }}/{{ totalCount }}
            </span>
        </div>
    </div>
</template>
