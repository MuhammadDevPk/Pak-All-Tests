<script setup>
import { ref, computed } from 'vue';
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
const activePhaseContent = computed(() => {
    const matchedPhase = props.phases.find(
        (phase) => Number(phase.number) === activePhaseNumber.value,
    );

    return matchedPhase?.content || null;
});
const selectPhase = (number) => {
    activePhaseNumber.value = Number(number);
};
</script>

<template>
    <div class="flex flex-col gap-6">
        <!-- Horizontal Multi-Step Stepper Pipeline Header -->
        <div
            class="grid grid-cols-1 gap-3.5 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4"
        >
            <button
                v-for="phase in phases"
                :key="phase.number"
                type="button"
                @click="selectPhase(phase.number)"
                :class="[
                    'group relative flex flex-col justify-between rounded-2xl border p-4 text-left transition-all duration-200 sm:p-5',
                    Number(phase.number) === activePhaseNumber
                        ? 'shadow-senselab-card border-sky-500/80 bg-linear-to-br from-sky-50/70 via-white to-orange-50/30 dark:border-sky-400/70 dark:from-sky-950/30 dark:via-zinc-900/70 dark:to-orange-950/20'
                        : 'hover:shadow-senselab-card border-zinc-200/80 bg-white/70 hover:border-zinc-300 hover:bg-white dark:border-zinc-800 dark:bg-zinc-900/50 dark:hover:border-zinc-700 dark:hover:bg-zinc-900',
                ]"
            >
                <div>
                    <div class="flex items-center justify-between">
                        <span
                            :class="[
                                'flex h-8 w-8 items-center justify-center rounded-xl text-xs font-bold transition-transform duration-200 group-hover:scale-105',
                                Number(phase.number) === activePhaseNumber
                                    ? 'bg-senselab-gradient text-white shadow-xs'
                                    : 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400',
                            ]"
                        >
                            {{ phase.number }}
                        </span>

                        <span
                            v-if="Number(phase.number) === activePhaseNumber"
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

                    <h4
                        class="text-senselab-heading mt-3 text-sm font-bold sm:text-base"
                    >
                        {{ phase.title }}
                    </h4>
                    <p
                        v-if="phase.subtitle"
                        class="mt-0.5 line-clamp-1 text-xs text-brand-muted"
                    >
                        {{ phase.subtitle }}
                    </p>
                </div>

                <!-- Bottom Active Indicator Bar -->
                <div
                    class="mt-3.5 flex items-center justify-between border-t border-zinc-100 pt-2.5 text-[11px] dark:border-zinc-800/80"
                >
                    <span
                        :class="
                            Number(phase.number) === activePhaseNumber
                                ? 'font-semibold text-sky-600 dark:text-sky-400'
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
                        class="text-zinc-400 transition-transform duration-200 group-hover:translate-x-0.5"
                        >➔</span
                    >
                </div>
            </button>
        </div>

        <!-- Render Selected Phase's SDUI Node -->
        <div class="mt-1">
            <SduiRenderer
                v-if="activePhaseContent"
                :node="activePhaseContent"
            />
        </div>
    </div>
</template>
