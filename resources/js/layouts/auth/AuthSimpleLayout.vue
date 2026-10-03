<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
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

        <div
            class="w-full max-w-sm rounded-3xl border border-zinc-200/80 bg-white/90 p-8 shadow-xl backdrop-blur-xl dark:border-zinc-800 dark:bg-zinc-900/90"
        >
            <div class="flex flex-col gap-6">
                <div class="flex flex-col items-center gap-4">
                    <Link
                        :href="home()"
                        class="group flex flex-col items-center gap-2 font-medium"
                    >
                        <!-- SenseLab Ribbon Logo Glyph -->
                        <div
                            class="flex h-10 w-10 items-center justify-center transition-transform group-hover:scale-105"
                        >
                            <AppLogoIcon class="h-9 w-9" />
                        </div>
                        <span class="text-sm font-bold text-brand-heading"
                            >Pak-All-Tests</span
                        >
                    </Link>
                    <div class="space-y-1.5 text-center">
                        <h1
                            class="text-xl font-bold tracking-tight text-brand-heading"
                        >
                            {{ title }}
                        </h1>
                        <p class="text-center text-xs text-brand-muted">
                            {{ description }}
                        </p>
                    </div>
                </div>
                <slot />
            </div>
        </div>
    </div>
</template>
