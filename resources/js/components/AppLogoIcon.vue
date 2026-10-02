<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

type Props = {
    className?: HTMLAttributes['class'];
};

defineProps<Props>();
</script>

<template>
    <svg
        viewBox="0 0 40 40"
        fill="none"
        :class="className"
        v-bind="$attrs"
    >
        <defs>
            <linearGradient id="appRibbonGrad1" x1="0%" y1="0%" x2="100%" y2="100%">
                <stop offset="0%" stop-color="#0284c7" />
                <stop offset="50%" stop-color="#38bdf8" />
                <stop offset="100%" stop-color="#ea580c" />
            </linearGradient>
            <linearGradient id="appRibbonGrad2" x1="100%" y1="0%" x2="0%" y2="100%">
                <stop offset="0%" stop-color="#f97316" />
                <stop offset="100%" stop-color="#8b5cf6" />
            </linearGradient>
        </defs>
        <path d="M12 28C8.686 28 6 25.314 6 22C6 17.5 10 14 15 14L25 14C29.418 14 33 17.582 33 22C33 26.418 29.418 30 25 30L15 30" stroke="url(#appRibbonGrad1)" stroke-width="4.5" stroke-linecap="round" />
        <path d="M28 12C31.314 12 34 14.686 34 18C34 22.5 30 26 25 26L15 26C10.582 26 7 22.418 7 18C7 13.582 10.582 10 15 10L25 10" stroke="url(#appRibbonGrad2)" stroke-width="4.5" stroke-linecap="round" />
    </svg>
</template>
