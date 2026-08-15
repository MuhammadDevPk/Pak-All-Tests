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
        (phase) => Number(phase.number) === activePhaseNumber.value
    );
    return matchedPhase?.content || null;
});
const selectPhase = (number) => {
    activePhaseNumber.value = Number(number);
};
</script>

<template>
    <div class="flex flex-col gap-6">
        <!-- Horizontal Multi-Step Stepper Header -->
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 md:grid-cols-4">
            <button v-for="phase in phases" :key="phase.number" type="button" @click="selectPhase(phase.number)"
                :class="[
                    'flex flex-col gap-1 rounded-xl border p-4 text-left transition-all',
                    Number(phase.number) === activePhaseNumber
                        ? 'border-primary bg-primary/5 shadow-sm dark:bg-primary/10'
                        : 'border-sidebar-border/70 hover:border-sidebar-border bg-sidebar/20'
                ]">
                <div class="flex items-center justify-between">
                    <span
                        :class="[
                            'flex h-7 w-7 items-center justify-center rounded-full text-xs font-bold',
                            Number(phase.number) === activePhaseNumber
                                ? 'bg-primary text-primary-foreground'
                                : 'bg-sidebar-accent text-muted-foreground'
                        ]">
                        {{ phase.number }}
                    </span>
                    <span v-if="Number(phase.number) === activePhaseNumber"
                        class="text-[10px] font-semibold uppercase tracking-wider text-primary">
                        Active
                    </span>
                </div>
                <h4 class="mt-2 text-sm font-semibold text-foreground">
                    {{ phase.title }}
                </h4>
                <p v-if="phase.subtitle" class="text-xs text-muted-foreground line-clamp-1">
                    {{ phase.subtitle }}
                </p>
            </button>
        </div>
        <!-- Render Selected Phase's SDUI Node -->
        <div class="mt-2">
            <SduiRenderer v-if="activePhaseContent" :node="activePhaseContent" />
        </div>
    </div>
</template>