<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
const props = defineProps({
    module: {
        type: Object,
        required: true,
        default: () => ({}),
    },
    questions: {
        type: Array,
        default: () => [],
    },
    mode: {
        type: String,
        default: 'test', // 'test' or 'practice'
    },
});

const emit = defineEmits(['question-change']);

const currentQuestionIndex = ref(0);
const selectedAnswers = ref({});
const showExplanations = ref({});
const isSubmitted = ref(false);
const isSubmitting = ref(false);
const serverSummary = ref(null);
const serverResults = ref({});
const remainingTimeSeconds = ref(
    (props.module?.content_meta?.duration_minutes || 20) * 60,
);
let timerInterval = null;
const currentQuestion = computed(
    () => props.questions[currentQuestionIndex.value] || null,
);
const formattedTime = computed(() => {
    const mins = Math.floor(remainingTimeSeconds.value / 60);
    const secs = remainingTimeSeconds.value % 60;

    return `${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
});
const isTimeLow = computed(
    () => remainingTimeSeconds.value <= 120 && remainingTimeSeconds.value > 0,
);
const answeredCount = computed(() => Object.keys(selectedAnswers.value).length);

const emitQuestionChange = () => {
    if (!currentQuestion.value) {
        emit('question-change', null);
        return;
    }

    const q = currentQuestion.value;
    const selectedIdx = selectedAnswers.value[q.id];
    let userSelectedText = null;

    if (selectedIdx !== undefined && selectedIdx !== null) {
        const letter = String.fromCharCode(65 + Number(selectedIdx));
        const opts = q.payload?.options || [];
        const rawOpt = opts[selectedIdx];
        const text =
            typeof rawOpt === 'object' ? rawOpt?.text || '' : String(rawOpt ?? '');
        userSelectedText = `${letter}) ${text}`;
    }

    emit('question-change', {
        ...q,
        user_selected: userSelectedText,
        user_selected_index: selectedIdx ?? null,
        attempts_made: selectedIdx !== undefined && selectedIdx !== null ? 1 : 0,
    });
};

const scoreSummary = computed(() => {
    if (!isSubmitted.value) {
        return null;
    }

    if (serverSummary.value) {
        return serverSummary.value;
    }

    let correctCount = 0;
    props.questions.forEach((q) => {
        const correctIndex =
            serverResults.value[q.id]?.correct_index ??
            q.payload?.correct_option_index;

        if (
            correctIndex !== undefined &&
            selectedAnswers.value[q.id] === correctIndex
        ) {
            correctCount++;
        }
    });
    const total = props.questions.length || 1;
    const percentage = Math.round((correctCount / total) * 100);

    return {
        total: props.questions.length,
        answered: answeredCount.value,
        correct: correctCount,
        percentage,
        isPassed:
            percentage >= (props.module?.content_meta?.passing_score || 60),
    };
});

const selectOption = (optionIndex) => {
    if (isSubmitted.value || !currentQuestion.value) {
        return;
    }

    selectedAnswers.value[currentQuestion.value.id] = optionIndex;
    emitQuestionChange();
};

const goToQuestion = (index) => {
    if (index >= 0 && index < props.questions.length) {
        currentQuestionIndex.value = index;
    }
};

const nextQuestion = () => {
    if (currentQuestionIndex.value < props.questions.length - 1) {
        currentQuestionIndex.value++;
    }
};

const prevQuestion = () => {
    if (currentQuestionIndex.value > 0) {
        currentQuestionIndex.value--;
    }
};

const toggleExplanation = (questionId) => {
    showExplanations.value[questionId] = !showExplanations.value[questionId];
};

const submitTest = async () => {
    isSubmitted.value = true;

    if (timerInterval) {
        clearInterval(timerInterval);
    }

    if (!props.module?.id) return;

    isSubmitting.value = true;
    try {
        const response = await fetch(`/api/modules/${props.module.id}/submit`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN':
                    document
                        .querySelector('meta[name="csrf-token"]')
                        ?.getAttribute('content') || '',
            },
            body: JSON.stringify({
                answers: selectedAnswers.value,
            }),
        });

        if (response.ok) {
            const data = await response.json();
            if (data.status === 'success') {
                serverSummary.value = {
                    total: data.summary.total_questions,
                    answered: data.summary.answered_count,
                    correct: data.summary.correct_count,
                    percentage: data.summary.percentage,
                    isPassed: data.summary.is_passed,
                };
                serverResults.value = data.questions || {};
            }
        }
    } catch (err) {
        console.error('Error submitting test responses:', err);
    } finally {
        isSubmitting.value = false;
    }
};

onMounted(() => {
    if (props.mode === 'test') {
        timerInterval = setInterval(() => {
            if (remainingTimeSeconds.value > 0 && !isSubmitted.value) {
                remainingTimeSeconds.value--;
            } else if (remainingTimeSeconds.value === 0 && !isSubmitted.value) {
                submitTest();
            }
        }, 1000);
    }
});

watch(
    currentQuestion,
    () => {
        emitQuestionChange();
    },
    { immediate: true },
);

onUnmounted(() => {
    if (timerInterval) {
        clearInterval(timerInterval);
    }
    emit('question-change', null);
});
</script>

<template>
    <div
        class="flex flex-col gap-6 rounded-xl border border-sidebar-border/70 bg-card p-6 shadow-xs dark:bg-card/50"
    >
        <!-- Test Header Bar -->
        <div
            class="flex flex-wrap items-center justify-between gap-4 border-b border-sidebar-border/50 pb-4"
        >
            <div>
                <h2 class="text-lg font-bold text-foreground">
                    {{ module.title || 'MCQ Test Player' }}
                </h2>
                <p class="text-xs text-muted-foreground">
                    Question {{ currentQuestionIndex + 1 }} of
                    {{ questions.length }} • {{ answeredCount }} Answered
                </p>
            </div>

            <!-- Time Ticket (Test Mode) -->
            <div
                v-if="mode === 'test' && !isSubmitted"
                :class="[
                    'flex items-center gap-2 rounded-lg px-3 py-1.5 font-mono text-sm font-semibold transition-colors',
                    isTimeLow
                        ? 'animate-pulse bg-destructive/10 text-destructive'
                        : 'bg-primary/10 text-primary',
                ]"
            >
                <span>⏱️ Time Remaining:</span>
                <span>{{ formattedTime }}</span>
            </div>

            <div
                v-if="isSubmitted"
                class="rounded-lg bg-emerald-500/10 px-3 py-1.5 text-xs font-semibold text-emerald-600 dark:text-emerald-400"
            >
                Completed
            </div>
        </div>

        <!-- Question Navigator Pill Bar -->
        <div
            v-if="questions.length > 1"
            class="flex flex-wrap gap-1.5 border-b border-sidebar-border/30 pb-3"
        >
            <button
                v-for="(q, idx) in questions"
                :key="q.id || idx"
                type="button"
                @click="goToQuestion(idx)"
                :class="[
                    'flex h-7 w-7 items-center justify-center rounded-md text-xs font-medium transition-colors',
                    currentQuestionIndex === idx
                        ? 'bg-primary font-bold text-primary-foreground ring-2 ring-primary ring-offset-1'
                        : selectedAnswers[q.id] !== undefined
                          ? 'bg-primary/20 text-primary'
                          : 'bg-sidebar-accent text-muted-foreground hover:text-foreground',
                ]"
            >
                {{ idx + 1 }}
            </button>
        </div>

        <!-- Result Summary Card (After Submission) -->
        <div
            v-if="isSubmitted && scoreSummary"
            class="flex flex-col gap-3 rounded-lg border border-sidebar-border/70 bg-sidebar/30 p-4"
        >
            <div
                class="flex items-center justify-between border-b border-sidebar-border/50 pb-2"
            >
                <h3 class="text-base font-bold text-foreground">
                    Test Results Summary
                </h3>
                <span
                    :class="[
                        'rounded-full px-3 py-1 text-xs font-bold',
                        scoreSummary.isPassed
                            ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400'
                            : 'bg-destructive/10 text-destructive',
                    ]"
                >
                    {{ scoreSummary.isPassed ? 'PASSED' : 'NEEDS PRACTICE' }}
                </span>
            </div>
            <div class="mt-2 grid grid-cols-2 gap-4 text-center sm:grid-cols-4">
                <div
                    class="rounded-md border border-sidebar-border/50 bg-card p-2"
                >
                    <span class="block text-xs text-muted-foreground"
                        >Score</span
                    >
                    <span class="text-lg font-bold text-foreground"
                        >{{ scoreSummary.percentage }}%</span
                    >
                </div>
                <div
                    class="rounded-md border border-sidebar-border/50 bg-card p-2"
                >
                    <span class="block text-xs text-muted-foreground"
                        >Correct</span
                    >
                    <span
                        class="text-lg font-bold text-emerald-600 dark:text-emerald-400"
                        >{{ scoreSummary.correct }} /
                        {{ scoreSummary.total }}</span
                    >
                </div>
                <div
                    class="rounded-md border border-sidebar-border/50 bg-card p-2"
                >
                    <span class="block text-xs text-muted-foreground"
                        >Answered</span
                    >
                    <span class="text-lg font-bold text-foreground">{{
                        scoreSummary.answered
                    }}</span>
                </div>
                <div
                    class="rounded-md border border-sidebar-border/50 bg-card p-2"
                >
                    <span class="block text-xs text-muted-foreground"
                        >Target Pass</span
                    >
                    <span class="text-lg font-bold text-foreground"
                        >{{ module.content_meta?.passing_score || 60 }}%</span
                    >
                </div>
            </div>
        </div>

        <!-- Active Question Display -->
        <div v-if="currentQuestion" class="flex flex-col gap-4">
            <h3 class="text-base leading-relaxed font-semibold text-foreground">
                {{ currentQuestion.question_text }}
            </h3>

            <!-- Options Grid -->
            <div class="flex flex-col gap-2.5">
                <button
                    v-for="(option, idx) in currentQuestion.payload?.options ||
                    []"
                    :key="idx"
                    type="button"
                    @click="selectOption(idx)"
                    :disabled="isSubmitted"
                    :class="[
                        'flex items-center justify-between rounded-xl border p-4 text-left text-sm transition-all',
                        isSubmitted &&
                        (serverResults[currentQuestion.id]?.correct_index === idx ||
                            (!serverResults[currentQuestion.id] &&
                                currentQuestion.payload?.correct_option_index === idx))
                            ? 'border-emerald-500 bg-emerald-500/10 font-semibold text-emerald-700 dark:text-emerald-400'
                            : selectedAnswers[currentQuestion.id] === idx
                              ? isSubmitted &&
                                serverResults[currentQuestion.id] &&
                                !serverResults[currentQuestion.id]?.correct
                                  ? 'border-destructive bg-destructive/10 font-semibold text-destructive'
                                  : 'border-primary bg-primary/10 font-semibold text-primary shadow-xs'
                              : 'border-sidebar-border/70 bg-sidebar/20 text-foreground hover:border-sidebar-border',
                    ]"
                >
                    <div class="flex items-center gap-3">
                        <span
                            class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-sidebar-accent text-xs font-bold text-muted-foreground"
                        >
                            {{ String.fromCharCode(65 + idx) }}
                        </span>
                        <span>{{ option }}</span>
                    </div>
                    <div
                        v-if="selectedAnswers[currentQuestion.id] === idx"
                        class="h-3 w-3 rounded-full bg-primary"
                    />
                </button>
            </div>

            <!-- Practice Mode Explanation Toggle or Post-Submission Review -->
            <div
                v-if="
                    mode === 'practice' ||
                    isSubmitted ||
                    serverResults[currentQuestion.id]?.explanation
                "
                class="mt-2"
            >
                <button
                    type="button"
                    @click="toggleExplanation(currentQuestion.id)"
                    class="flex items-center gap-1 text-xs font-medium text-primary hover:underline"
                >
                    💡
                    {{
                        showExplanations[currentQuestion.id]
                            ? 'Hide Solution Explanation'
                            : 'View Solution Explanation'
                    }}
                </button>
                <div
                    v-if="
                        showExplanations[currentQuestion.id] &&
                        (serverResults[currentQuestion.id]?.explanation ||
                            currentQuestion.explanation)
                    "
                    class="mt-2 rounded-lg border border-primary/20 bg-primary/5 p-3 text-xs leading-relaxed text-foreground"
                >
                    <strong class="text-primary">Explanation:</strong>
                    {{
                        serverResults[currentQuestion.id]?.explanation ||
                        currentQuestion.explanation
                    }}
                </div>
            </div>
        </div>

        <!-- Navigation Action Bar -->
        <div
            class="flex items-center justify-between border-t border-sidebar-border/50 pt-4"
        >
            <button
                type="button"
                @click="prevQuestion"
                :disabled="currentQuestionIndex === 0"
                class="rounded-lg border border-sidebar-border/70 bg-sidebar px-4 py-2 text-xs font-medium text-foreground transition-colors hover:bg-sidebar-accent disabled:opacity-40"
            >
                ← Previous
            </button>
            <div class="flex gap-2">
                <button
                    v-if="currentQuestionIndex < questions.length - 1"
                    type="button"
                    @click="nextQuestion"
                    class="rounded-lg bg-primary px-4 py-2 text-xs font-semibold text-primary-foreground transition-colors hover:bg-primary/90"
                >
                    Next Question →
                </button>
                <button
                    v-if="!isSubmitted"
                    type="button"
                    @click="submitTest"
                    class="rounded-lg bg-emerald-600 px-4 py-2 text-xs font-semibold text-white transition-colors hover:bg-emerald-700"
                >
                    Submit Test ✓
                </button>
            </div>
        </div>
    </div>
</template>
