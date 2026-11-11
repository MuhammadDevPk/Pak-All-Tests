<script setup>
import { nextTick, ref } from 'vue';
import SduiRenderer from './SduiRenderer.vue';

const props = defineProps({
    currentPhase: {
        type: [Number, String],
        default: 1,
    },
    phases: {
        type: Array,
        required: true,
        default: () => [],
    },
});

const activePhaseNumber = ref(Number(props.currentPhase) || 1);

const togglePhase = (number) => {
    const num = Number(number);

    if (activePhaseNumber.value === num) {
        activePhaseNumber.value = null;

        return;
    }

    activePhaseNumber.value = num;

    nextTick(() => {
        const el = document.getElementById(`phase-card-${num}`);

        if (el) {
            el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    });
};
</script>

<template>
    <div class="flex flex-col gap-4">
        <div
            v-for="phase in phases"
            :key="phase.number"
            :id="`phase-card-${phase.number}`"
            :class="[
                'shadow-senselab-card overflow-hidden rounded-3xl border transition-all duration-200',
                Number(phase.number) === activePhaseNumber
                    ? 'border-sky-500/80 bg-white ring-2 ring-sky-500/20 dark:border-sky-400/70 dark:bg-zinc-950/95 dark:ring-sky-500/30'
                    : 'border-zinc-200/80 bg-white/70 hover:border-zinc-300 hover:bg-white dark:border-zinc-800 dark:bg-zinc-900/50 dark:hover:border-zinc-700 dark:hover:bg-zinc-900',
            ]"
        >
            <!-- Clickable Phase Header Card -->
            <button
                type="button"
                @click="togglePhase(phase.number)"
                class="flex w-full cursor-pointer items-center justify-between gap-4 p-4 text-left transition-colors sm:p-5"
            >
                <div class="flex min-w-0 items-center gap-3 sm:gap-4">
                    <span
                        :class="[
                            'flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl text-sm font-bold shadow-xs transition-transform duration-200',
                            Number(phase.number) === activePhaseNumber
                                ? 'bg-senselab-gradient scale-105 text-white'
                                : 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400',
                        ]"
                    >
                        {{ phase.number }}
                    </span>

                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h4
                                class="text-senselab-heading truncate text-sm font-bold sm:text-base"
                            >
                                {{ phase.title }}
                            </h4>

                            <span
                                v-if="
                                    Number(phase.number) === activePhaseNumber
                                "
                                class="inline-flex items-center gap-1.5 rounded-full bg-emerald-500/10 px-2.5 py-0.5 text-[10px] font-bold text-emerald-600 dark:text-emerald-400"
                            >
                                <span
                                    class="h-1.5 w-1.5 animate-pulse rounded-full bg-emerald-500"
                                ></span>
                                ACTIVE PHASE
                            </span>
                            <span
                                v-else
                                class="text-[10px] font-semibold tracking-wider text-zinc-400 uppercase dark:text-zinc-500"
                            >
                                Phase {{ phase.number }}
                            </span>
                        </div>

                        <p
                            v-if="phase.subtitle"
                            class="mt-0.5 line-clamp-1 text-xs text-brand-muted"
                        >
                            {{ phase.subtitle }}
                        </p>
                    </div>
                </div>

                <!-- Expand / Collapse Indicator Button & Pill -->
                <div class="flex shrink-0 items-center gap-2">
                    <span
                        class="hidden text-xs font-semibold sm:inline-block"
                        :class="
                            Number(phase.number) === activePhaseNumber
                                ? 'text-sky-600 dark:text-sky-400'
                                : 'text-zinc-400 dark:text-zinc-500'
                        "
                    >
                        {{
                            Number(phase.number) === activePhaseNumber
                                ? 'In Progress'
                                : 'Click to View'
                        }}
                    </span>

                    <span
                        :class="[
                            'flex h-8 w-8 items-center justify-center rounded-xl bg-zinc-100/80 text-xs text-zinc-500 transition-transform duration-300 dark:bg-zinc-800 dark:text-zinc-400',
                            Number(phase.number) === activePhaseNumber
                                ? 'rotate-180 bg-sky-50 text-sky-600 dark:bg-sky-950/60 dark:text-sky-400'
                                : '',
                        ]"
                    >
                        ▼
                    </span>
                </div>
            </button>

            <!-- Phase Content Opened Right Bottom of Card as a Child -->
            <div
                v-if="
                    Number(phase.number) === activePhaseNumber && phase.content
                "
                class="border-t border-zinc-100 bg-zinc-50/40 p-4 sm:p-6 dark:border-zinc-800/80 dark:bg-zinc-900/30"
            >
                <SduiRenderer :node="phase.content" />
            </div>
        </div>
    </div>
</template>
