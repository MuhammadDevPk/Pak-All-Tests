<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { home } from '@/routes';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div
        class="relative flex min-h-svh flex-col items-center justify-center gap-6 bg-brand-canvas p-6 md:p-10 text-brand-body overflow-hidden"
    >
        <!-- Ambient SenseLab Lighting Mesh -->
        <div class="pointer-events-none absolute inset-0 -z-10 overflow-hidden">
            <div class="absolute -top-32 -left-32 mesh-aura-cyan"></div>
            <div class="absolute -bottom-32 -right-32 mesh-aura-warm"></div>
        </div>

        <div class="flex w-full max-w-md flex-col gap-6">
            <Link
                :href="home()"
                class="flex items-center gap-2 self-center font-medium group"
            >
                <div class="flex h-10 w-10 items-center justify-center transition-transform group-hover:scale-105">
                    <AppLogoIcon class="h-9 w-9" />
                </div>
                <span class="text-base font-bold text-brand-heading">Pak-All-Tests</span>
            </Link>

            <div class="flex flex-col gap-6">
                <Card class="rounded-3xl border border-zinc-200/80 bg-white/90 shadow-xl backdrop-blur-xl dark:border-zinc-800 dark:bg-zinc-900/90">
                    <CardHeader class="px-8 pt-8 pb-0 text-center">
                        <CardTitle class="text-xl font-bold tracking-tight text-brand-heading">{{ title }}</CardTitle>
                        <CardDescription class="text-xs text-brand-muted mt-1.5">
                            {{ description }}
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="px-8 py-8">
                        <slot />
                    </CardContent>
                </Card>
            </div>
        </div>
    </div>
</template>
