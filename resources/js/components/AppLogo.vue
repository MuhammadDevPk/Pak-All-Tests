<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';

const page = usePage();
const platform = computed(() => (page.props as any).platform || {});
const appName = computed(
    () => platform.value.name || page.props.name || 'Pak-All-Tests',
);
const subtitle = computed(() => platform.value.subtitle || 'Exam Prep Kernel');
</script>

<template>
    <div class="relative flex size-8 shrink-0 items-center justify-center">
        <AppLogoIcon
            class="size-7 transition-transform duration-200 hover:scale-105"
        />
    </div>
    <div class="ml-1.5 grid flex-1 text-left text-sm leading-tight">
        <span class="truncate font-bold tracking-tight text-foreground">
            {{ appName === 'Laravel' ? 'Pak-All-Tests' : appName }}
        </span>
        <span
            class="text-[10px] font-medium tracking-wide text-muted-foreground"
        >
            {{ subtitle }}
        </span>
    </div>
</template>
