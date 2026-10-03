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
        class="relative flex min-h-svh flex-col items-center justify-center gap-6 overflow-hidden bg-brand-canvas p-6 text-brand-body md:p-10"
    >
        <!-- Ambient SenseLab Lighting Mesh -->
        <div class="pointer-events-none absolute inset-0 -z-10 overflow-hidden">
            <div class="mesh-aura-cyan absolute -top-32 -left-32"></div>
            <div class="mesh-aura-warm absolute -right-32 -bottom-32"></div>
        </div>

        <div class="flex w-full max-w-md flex-col gap-6">
            <Link
                :href="home()"
                class="group flex items-center gap-2 self-center font-medium"
            >
                <div
                    class="flex h-10 w-10 items-center justify-center transition-transform group-hover:scale-105"
                >
                    <AppLogoIcon class="h-9 w-9" />
                </div>
                <span class="text-base font-bold text-brand-heading"
                    >Pak-All-Tests</span
                >
            </Link>

            <div class="flex flex-col gap-6">
                <Card
                    class="rounded-3xl border border-zinc-200/80 bg-white/90 shadow-xl backdrop-blur-xl dark:border-zinc-800 dark:bg-zinc-900/90"
                >
                    <CardHeader class="px-8 pt-8 pb-0 text-center">
                        <CardTitle
                            class="text-xl font-bold tracking-tight text-brand-heading"
                            >{{ title }}</CardTitle
                        >
                        <CardDescription
                            class="mt-1.5 text-xs text-brand-muted"
                        >
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
