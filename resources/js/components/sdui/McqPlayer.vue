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

// View Mode: 'page' (scrollable 10, 25, 50, 100 per page) vs 'focus' (1 at a time)
const viewMode = ref('page');
const pageSize = ref(10);
const currentPage = ref(1);
const isNavigatorOpen = ref(false);

const currentQuestionIndex = ref(0);
const selectedAnswers = ref({});
const showExplanations = ref({});
const isSubmitted = ref(false);
const isSubmitting = ref(false);
const serverSummary = ref(null);
const serverResults = ref({});

// Reveal Answer & Forfeit State
const revealedAnswers = ref({});
const isRevealing = ref({});
const forfeitedQuestions = ref({});
const masteryState = ref({});

const isWeakMode = computed(() => !!props.module?.content_meta?.is_weak_mode);

const remainingTimeSeconds = ref(
    (props.module?.content_meta?.duration_minutes || 20) * 60,
);
let timerInterval = null;

const formattedTime = computed(() => {
    const mins = Math.floor(remainingTimeSeconds.value / 60);
    const secs = remainingTimeSeconds.value % 60;

    return `${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
});

const isTimeLow = computed(
    () => remainingTimeSeconds.value <= 120 && remainingTimeSeconds.value > 0,
);

const answeredCount = computed(() => Object.keys(selectedAnswers.value).length);

const totalPages = computed(() => {
    if (!props.questions.length) return 1;
    return Math.ceil(props.questions.length / pageSize.value);
});

const paginatedQuestions = computed(() => {
    if (viewMode.value === 'focus') {
        const q = props.questions[currentQuestionIndex.value];
        return q ? [{ ...q, _globalIndex: currentQuestionIndex.value }] : [];
    }

    const start = (currentPage.value - 1) * pageSize.value;
    return props.questions.slice(start, start + pageSize.value).map((q, i) => ({
        ...q,
        _globalIndex: start + i,
    }));
});

const currentFocusQuestion = computed(
    () => props.questions[currentQuestionIndex.value] || null,
);

const emitQuestionChange = () => {
    const q = currentFocusQuestion.value;
    if (!q) {
        emit('question-change', null);
        return;
    }

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
        was_revealed: !!revealedAnswers.value[q.id],
    });
};

const selectOption = async (question, optionIndex) => {
    if (isSubmitted.value || !question) {
        return;
    }

    selectedAnswers.value[question.id] = optionIndex;
    emitQuestionChange();

    // In practice or weak mode, record attempt on the server for Threshold Mastery
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        const response = await fetch(`/api/questions/${question.id}/record-attempt`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({
                selected_index: optionIndex,
                domain_id: props.module?.domain_id || null,
                was_revealed: !!revealedAnswers.value[question.id],
            }),
        });

        if (response.ok) {
            const data = await response.json();
            if (data.status === 'success') {
                masteryState.value[question.id] = {
                    consecutive_correct: data.consecutive_correct,
                    is_mastered: data.is_mastered,
                    is_correct: data.is_correct,
                };
            }
        }
    } catch (err) {
        console.warn('Could not record question attempt:', err);
    }
};

const revealAnswer = async (question) => {
    if (!question || revealedAnswers.value[question.id] || isRevealing.value[question.id]) {
        return;
    }

    isRevealing.value[question.id] = true;
    forfeitedQuestions.value[question.id] = true;

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        const response = await fetch(`/api/questions/${question.id}/reveal`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({
                domain_id: props.module?.domain_id || null,
            }),
        });

        if (response.ok) {
            const data = await response.json();
            if (data.status === 'success') {
                revealedAnswers.value[question.id] = data;
                showExplanations.value[question.id] = true;
                if (data.weak_status) {
                    masteryState.value[question.id] = data.weak_status;
                }
                emitQuestionChange();
            }
        }
    } catch (err) {
        console.error('Error revealing answer:', err);
    } finally {
        isRevealing.value[question.id] = false;
    }
};

const toggleExplanation = (questionId) => {
    showExplanations.value[questionId] = !showExplanations.value[questionId];
};

const setPage = (page) => {
    if (page >= 1 && page <= totalPages.value) {
        currentPage.value = page;
        document.querySelector('#mcq-player-container')?.scrollIntoView({ behavior: 'smooth' });
    }
};

const jumpInputQuery = ref('');
const paletteFilter = ref('all'); // 'all', 'answered', 'unanswered', 'revealed'

const filteredPaletteQuestions = computed(() => {
    return props.questions.map((q, idx) => {
        const isAnswered = selectedAnswers.value[q.id] !== undefined;
        const isRevealed = !!revealedAnswers.value[q.id];
        return {
            ...q,
            _index: idx,
            _number: idx + 1,
            isAnswered,
            isRevealed,
        };
    }).filter((item) => {
        if (paletteFilter.value === 'answered') return item.isAnswered;
        if (paletteFilter.value === 'unanswered') return !item.isAnswered;
        if (paletteFilter.value === 'revealed') return item.isRevealed;
        return true;
    });
});

const jumpToQuestionNumber = () => {
    const num = parseInt(jumpInputQuery.value, 10);
    if (!isNaN(num) && num >= 1 && num <= props.questions.length) {
        goToQuestion(num - 1);
        jumpInputQuery.value = '';
    } else {
        alert(`Please enter a valid question number between 1 and ${props.questions.length}`);
    }
};

const goToQuestion = (index) => {
    if (index >= 0 && index < props.questions.length) {
        currentQuestionIndex.value = index;
        currentPage.value = Math.floor(index / pageSize.value) + 1;
        isNavigatorOpen.value = false;
        emitQuestionChange();

        if (viewMode.value === 'page') {
            setTimeout(() => {
                const targetElement = document.querySelector(`#question-card-${index}`);
                if (targetElement) {
                    targetElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    targetElement.classList.add('ring-2', 'ring-primary');
                    setTimeout(() => {
                        targetElement.classList.remove('ring-2', 'ring-primary');
                    }, 1500);
                }
            }, 100);
        }
    }
};

const nextQuestion = () => {
    if (currentQuestionIndex.value < props.questions.length - 1) {
        currentQuestionIndex.value++;
        currentPage.value = Math.floor(currentQuestionIndex.value / pageSize.value) + 1;
        emitQuestionChange();
    }
};

const prevQuestion = () => {
    if (currentQuestionIndex.value > 0) {
        currentQuestionIndex.value--;
        currentPage.value = Math.floor(currentQuestionIndex.value / pageSize.value) + 1;
        emitQuestionChange();
    }
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
            revealedAnswers.value[q.id]?.correct_option_index ??
            q.payload?.correct_option_index;

        const isForfeited = forfeitedQuestions.value[q.id];

        if (
            !isForfeited &&
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
        isPassed: percentage >= (props.module?.content_meta?.passing_score || 60),
    };
});

const submitTest = async () => {
    isSubmitted.value = true;

    if (timerInterval) {
        clearInterval(timerInterval);
    }

    if (!props.module?.id) return;

    isSubmitting.value = true;
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        const response = await fetch(`/api/modules/${props.module.id}/submit`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({
                answers: selectedAnswers.value,
                revealed: Object.keys(forfeitedQuestions.value),
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
    if (props.mode === 'test' && !isWeakMode.value) {
        timerInterval = setInterval(() => {
            if (remainingTimeSeconds.value > 0 && !isSubmitted.value) {
                remainingTimeSeconds.value--;
            } else if (remainingTimeSeconds.value === 0 && !isSubmitted.value) {
                submitTest();
            }
        }, 1000);
    }

    // Initialize mastery state from weak_meta if present
    props.questions.forEach((q) => {
        if (q.weak_meta) {
            masteryState.value[q.id] = q.weak_meta;
        }
    });

    emitQuestionChange();
});

onUnmounted(() => {
    if (timerInterval) {
        clearInterval(timerInterval);
    }
});

watch(currentQuestionIndex, () => {
    emitQuestionChange();
});
</script>

<template>
    <div id="mcq-player-container" class="flex flex-col gap-6">
        <!-- Modern Header Controls Bar -->
        <div
            class="flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-sidebar-border/70 bg-card p-4 shadow-xs dark:bg-card/50"
        >
            <div class="flex items-center gap-3">
                <div
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary font-bold"
                >
                    <span v-if="isWeakMode">🎯</span>
                    <span v-else>📝</span>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="text-base font-bold text-foreground">
                            {{ module.title || 'MCQ Test Player' }}
                        </h2>
                        <span
                            v-if="isWeakMode"
                            class="rounded-full bg-amber-500/10 px-2.5 py-0.5 text-[11px] font-semibold text-amber-600 dark:text-amber-400"
                        >
                            Weak MCQs Revision
                        </span>
                    </div>
                    <p class="text-xs text-muted-foreground">
                        <span v-if="viewMode === 'page'">
                            Showing {{ (currentPage - 1) * pageSize + 1 }}–{{ Math.min(currentPage * pageSize, questions.length) }} of {{ questions.length }} Questions
                        </span>
                        <span v-else>
                            Question {{ currentQuestionIndex + 1 }} of {{ questions.length }}
                        </span>
                        • <span class="font-medium text-foreground">{{ answeredCount }} Answered</span>
                    </p>
                </div>
            </div>

            <!-- Toolbar Controls: View Mode, Page Size, Timer, Navigator -->
            <div class="flex flex-wrap items-center gap-2 sm:gap-3">
                <!-- Dual View Mode Switcher -->
                <div class="flex items-center rounded-lg border border-sidebar-border bg-sidebar/50 p-0.5 text-xs">
                    <button
                        type="button"
                        @click="viewMode = 'page'"
                        :class="[
                            'flex items-center gap-1.5 rounded-md px-2.5 py-1.5 font-medium transition-all',
                            viewMode === 'page'
                                ? 'bg-background text-foreground shadow-xs'
                                : 'text-muted-foreground hover:text-foreground',
                        ]"
                        title="Scroll through multiple questions"
                    >
                        <span>📑</span> Page View
                    </button>
                    <button
                        type="button"
                        @click="viewMode = 'focus'"
                        :class="[
                            'flex items-center gap-1.5 rounded-md px-2.5 py-1.5 font-medium transition-all',
                            viewMode === 'focus'
                                ? 'bg-background text-foreground shadow-xs'
                                : 'text-muted-foreground hover:text-foreground',
                        ]"
                        title="Focus on one question at a time"
                    >
                        <span>🎯</span> Focus Mode
                    </button>
                </div>

                <!-- Page Size Selector (only in Page View) -->
                <div v-if="viewMode === 'page'" class="flex items-center gap-1.5 text-xs text-muted-foreground">
                    <span class="hidden sm:inline">Show:</span>
                    <select
                        v-model.number="pageSize"
                        @change="currentPage = 1"
                        class="rounded-lg border border-sidebar-border bg-background px-2 py-1.5 text-xs font-semibold text-foreground focus:border-primary focus:outline-hidden"
                    >
                        <option :value="10">10 / page</option>
                        <option :value="25">25 / page</option>
                        <option :value="50">50 / page</option>
                        <option :value="100">100 / page</option>
                    </select>
                </div>

                <!-- Clear, User-Friendly Question Palette Trigger & Navigator -->
                <div class="relative">
                    <button
                        type="button"
                        @click="isNavigatorOpen = !isNavigatorOpen"
                        class="flex items-center gap-2 rounded-xl border border-sidebar-border bg-background px-3 py-1.5 text-xs font-semibold text-foreground shadow-xs hover:border-primary/50 hover:bg-sidebar-accent transition-all"
                        title="Open full question palette to view answered status or jump to any question"
                    >
                        <span class="flex h-5 w-5 items-center justify-center rounded-md bg-primary/10 text-[11px] font-bold text-primary">
                            #
                        </span>
                        <span class="font-bold">Question Palette</span>
                        <span class="rounded-full bg-sidebar px-2 py-0.5 text-[10px] font-semibold text-muted-foreground">
                            {{ answeredCount }}/{{ questions.length }}
                        </span>
                        <span class="text-[10px] text-muted-foreground transition-transform duration-200" :class="{ 'rotate-180': isNavigatorOpen }">▼</span>
                    </button>

                    <!-- Backdrop to dismiss on outside click -->
                    <div
                        v-if="isNavigatorOpen"
                        class="fixed inset-0 z-40"
                        @click="isNavigatorOpen = false"
                    />

                    <!-- Collapsible Question Palette Modal / Popover -->
                    <div
                        v-if="isNavigatorOpen"
                        class="absolute right-0 top-full z-50 mt-2 w-80 sm:w-96 rounded-2xl border border-sidebar-border/90 bg-popover p-4 shadow-2xl backdrop-blur-md dark:bg-card"
                    >
                        <!-- Header with Title & Close -->
                        <div class="flex items-center justify-between border-b border-sidebar-border/50 pb-2.5 mb-3">
                            <div>
                                <div class="flex items-center gap-1.5 font-bold text-sm text-foreground">
                                    <span>📋</span>
                                    <span>Question Palette & Navigator</span>
                                </div>
                                <p class="text-[11px] text-muted-foreground">
                                    Click any number to view or jump directly to it
                                </p>
                            </div>
                            <button
                                type="button"
                                @click="isNavigatorOpen = false"
                                class="rounded-lg p-1 text-xs text-muted-foreground hover:bg-sidebar-accent hover:text-foreground transition-colors"
                            >
                                ✕
                            </button>
                        </div>

                        <!-- Direct Jump by Number Input -->
                        <form @submit.prevent="jumpToQuestionNumber" class="flex items-center gap-2 mb-3">
                            <div class="relative flex-1">
                                <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-xs text-muted-foreground font-semibold">#</span>
                                <input
                                    v-model="jumpInputQuery"
                                    type="number"
                                    min="1"
                                    :max="questions.length"
                                    :placeholder="'Jump to question # (1–' + questions.length + ')'"
                                    class="w-full rounded-lg border border-sidebar-border bg-background py-1.5 pl-6 pr-2.5 text-xs text-foreground placeholder:text-muted-foreground focus:border-primary focus:outline-hidden"
                                />
                            </div>
                            <button
                                type="submit"
                                class="rounded-lg bg-primary px-3 py-1.5 text-xs font-bold text-primary-foreground hover:bg-primary/90 transition-colors"
                            >
                                Jump ➔
                            </button>
                        </form>

                        <!-- Filter Tabs -->
                        <div class="flex flex-wrap items-center gap-1 mb-2.5 text-[11px] border-b border-sidebar-border/40 pb-2">
                            <button
                                type="button"
                                @click="paletteFilter = 'all'"
                                :class="[
                                    'rounded-md px-2 py-0.5 font-semibold transition-colors',
                                    paletteFilter === 'all'
                                        ? 'bg-primary/10 text-primary font-bold'
                                        : 'text-muted-foreground hover:text-foreground',
                                ]"
                            >
                                All ({{ questions.length }})
                            </button>
                            <button
                                type="button"
                                @click="paletteFilter = 'answered'"
                                :class="[
                                    'rounded-md px-2 py-0.5 font-semibold transition-colors',
                                    paletteFilter === 'answered'
                                        ? 'bg-emerald-500/10 text-emerald-600 font-bold'
                                        : 'text-muted-foreground hover:text-foreground',
                                ]"
                            >
                                ✓ Answered ({{ answeredCount }})
                            </button>
                            <button
                                type="button"
                                @click="paletteFilter = 'unanswered'"
                                :class="[
                                    'rounded-md px-2 py-0.5 font-semibold transition-colors',
                                    paletteFilter === 'unanswered'
                                        ? 'bg-sidebar text-foreground font-bold'
                                        : 'text-muted-foreground hover:text-foreground',
                                ]"
                            >
                                Unattempted ({{ questions.length - answeredCount }})
                            </button>
                            <button
                                v-if="Object.keys(revealedAnswers).length > 0"
                                type="button"
                                @click="paletteFilter = 'revealed'"
                                :class="[
                                    'rounded-md px-2 py-0.5 font-semibold transition-colors',
                                    paletteFilter === 'revealed'
                                        ? 'bg-amber-500/10 text-amber-600 font-bold'
                                        : 'text-muted-foreground hover:text-foreground',
                                ]"
                            >
                                👁️ Revealed ({{ Object.keys(revealedAnswers).length }})
                            </button>
                        </div>

                        <!-- Status Legend -->
                        <div class="flex flex-wrap items-center gap-3 text-[10px] text-muted-foreground mb-3">
                            <div class="flex items-center gap-1">
                                <span class="h-2 w-2 rounded-full bg-primary" /> Current
                            </div>
                            <div class="flex items-center gap-1">
                                <span class="h-2 w-2 rounded-full bg-emerald-500" /> Answered
                            </div>
                            <div class="flex items-center gap-1">
                                <span class="h-2 w-2 rounded-full bg-amber-500" /> Revealed
                            </div>
                            <div class="flex items-center gap-1">
                                <span class="h-2 w-2 rounded-full bg-muted-foreground/30" /> Unattempted
                            </div>
                        </div>

                        <!-- Question Numbers Grid -->
                        <div class="max-h-60 overflow-y-auto pr-1">
                            <div class="grid grid-cols-6 sm:grid-cols-7 gap-1.5">
                                <button
                                    v-for="item in filteredPaletteQuestions"
                                    :key="item.id || item._index"
                                    type="button"
                                    @click="goToQuestion(item._index)"
                                    :class="[
                                        'flex h-8 items-center justify-center rounded-lg text-xs font-semibold transition-all',
                                        currentQuestionIndex === item._index
                                            ? 'bg-primary font-bold text-primary-foreground ring-2 ring-primary ring-offset-1'
                                            : item.isAnswered
                                              ? 'border border-emerald-500/30 bg-emerald-500/15 text-emerald-700 dark:text-emerald-400 font-bold hover:bg-emerald-500/25'
                                              : item.isRevealed
                                                ? 'border border-amber-500/30 bg-amber-500/15 text-amber-700 dark:text-amber-400 font-bold hover:bg-amber-500/25'
                                                : 'bg-sidebar-accent/50 text-muted-foreground hover:bg-sidebar-accent hover:text-foreground',
                                    ]"
                                    :title="'Question #' + item._number + (item.isAnswered ? ' (Answered)' : '') + (item.isRevealed ? ' (Revealed)' : '')"
                                >
                                    {{ item._number }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Timer Badge (if in timed test mode) -->
                <div
                    v-if="mode === 'test' && !isWeakMode"
                    :class="[
                        'flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-bold transition-colors',
                        isTimeLow
                            ? 'bg-destructive/10 text-destructive animate-pulse'
                            : 'bg-sidebar text-foreground border border-sidebar-border',
                    ]"
                >
                    <span>⏱️</span>
                    <span>{{ formattedTime }}</span>
                </div>

                <!-- Submission Status Badge -->
                <div
                    v-if="isSubmitted"
                    class="rounded-lg bg-emerald-500/10 px-3 py-1.5 text-xs font-bold text-emerald-600 dark:text-emerald-400"
                >
                    ✓ Completed
                </div>
            </div>
        </div>

        <!-- Top Page Navigation Bar (Page View Only — Directly under Subject Banner) -->
        <div
            v-if="viewMode === 'page'"
            class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-sidebar-border/80 bg-sidebar/40 p-3.5 shadow-xs dark:bg-card/40 backdrop-blur-xs transition-all"
        >
            <!-- Left: Previous Page & Next Page Primary Navigation -->
            <div class="flex items-center gap-2">
                <button
                    type="button"
                    @click="setPage(currentPage - 1)"
                    :disabled="currentPage === 1"
                    class="inline-flex items-center gap-1.5 rounded-xl border border-sidebar-border bg-background px-4 py-2 text-xs font-bold text-foreground transition-all hover:bg-sidebar-accent disabled:opacity-40 disabled:cursor-not-allowed shadow-xs active:scale-95"
                >
                    ← Previous Page
                </button>
                <button
                    type="button"
                    @click="setPage(currentPage + 1)"
                    :disabled="currentPage >= totalPages"
                    class="inline-flex items-center gap-1.5 rounded-xl border border-sidebar-border bg-background px-4 py-2 text-xs font-bold text-foreground transition-all hover:bg-sidebar-accent disabled:opacity-40 disabled:cursor-not-allowed shadow-xs active:scale-95"
                >
                    Next Page →
                </button>
            </div>

            <!-- Center: Page Indicator & Question Range Details -->
            <div class="flex items-center gap-3 text-xs text-muted-foreground font-medium">
                <div class="flex items-center gap-1.5">
                    <span>Page</span>
                    <span class="rounded-lg bg-primary/10 px-2.5 py-0.5 font-bold text-primary text-sm">
                        {{ currentPage }}
                    </span>
                    <span>of</span>
                    <span class="font-bold text-foreground text-sm">{{ totalPages }}</span>
                </div>
                <span class="hidden sm:inline text-sidebar-border">|</span>
                <span class="hidden sm:inline text-xs font-medium text-foreground">
                    Showing Questions {{ (currentPage - 1) * pageSize + 1 }}–{{ Math.min(currentPage * pageSize, questions.length) }} of {{ questions.length }}
                </span>
            </div>

            <!-- Right: Quick Jump by Page Selector -->
            <div class="flex items-center gap-2 text-xs">
                <span class="text-muted-foreground hidden md:inline">Jump to page:</span>
                <select
                    :value="currentPage"
                    @change="setPage(Number($event.target.value))"
                    class="rounded-xl border border-sidebar-border bg-background px-3 py-1.5 text-xs font-semibold text-foreground focus:border-primary focus:outline-hidden shadow-xs"
                >
                    <option v-for="p in totalPages" :key="p" :value="p">
                        Page {{ p }} (Q{{ (p - 1) * pageSize + 1 }}–{{ Math.min(p * pageSize, questions.length) }})
                    </option>
                </select>
            </div>
        </div>

        <!-- Result Summary Card (After Submission) -->
        <div
            v-if="isSubmitted && scoreSummary"
            class="flex flex-col gap-3 rounded-2xl border border-sidebar-border/70 bg-sidebar/30 p-5"
        >
            <div class="flex items-center justify-between border-b border-sidebar-border/50 pb-3">
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
            <div class="mt-2 grid grid-cols-2 gap-3 text-center sm:grid-cols-4">
                <div class="rounded-xl border border-sidebar-border/50 bg-card p-3">
                    <span class="block text-xs text-muted-foreground">Score</span>
                    <span class="text-xl font-bold text-foreground">{{ scoreSummary.percentage }}%</span>
                </div>
                <div class="rounded-xl border border-sidebar-border/50 bg-card p-3">
                    <span class="block text-xs text-muted-foreground">Correct</span>
                    <span class="text-xl font-bold text-emerald-600 dark:text-emerald-400">
                        {{ scoreSummary.correct }} / {{ scoreSummary.total }}
                    </span>
                </div>
                <div class="rounded-xl border border-sidebar-border/50 bg-card p-3">
                    <span class="block text-xs text-muted-foreground">Answered</span>
                    <span class="text-xl font-bold text-foreground">{{ scoreSummary.answered }}</span>
                </div>
                <div class="rounded-xl border border-sidebar-border/50 bg-card p-3">
                    <span class="block text-xs text-muted-foreground">Target Pass</span>
                    <span class="text-xl font-bold text-foreground">{{ module.content_meta?.passing_score || 60 }}%</span>
                </div>
            </div>
        </div>

        <!-- Questions Feed (Single Item in Focus Mode, Scrollable Stack in Page Mode) -->
        <div class="flex flex-col gap-6">
            <div
                v-for="q in paginatedQuestions"
                :key="q.id || q._globalIndex"
                :id="'question-card-' + q._globalIndex"
                class="flex flex-col gap-5 rounded-2xl border border-sidebar-border/70 bg-card p-6 shadow-xs dark:bg-card/50 transition-all hover:border-sidebar-border"
            >
                <!-- Question Header: Number + Text on Left, Reveal Button & Mastery on Right -->
                <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
                    <div class="flex items-start gap-3 flex-1">
                        <span
                            class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-xs font-bold text-primary"
                        >
                            {{ q._globalIndex + 1 }}
                        </span>
                        <h3 class="text-base font-semibold leading-relaxed text-foreground">
                            {{ q.question_text }}
                        </h3>
                    </div>

                    <!-- Right Side Actions: Threshold Mastery Pip + Reveal Answer Button -->
                    <div class="flex items-center gap-2 self-start shrink-0">
                        <!-- Mastery Badge (if in Weak MCQs mode or previously tracked) -->
                        <div
                            v-if="masteryState[q.id]"
                            class="flex items-center gap-1.5 rounded-lg border border-sidebar-border/60 bg-sidebar/50 px-2.5 py-1 text-xs"
                            :title="masteryState[q.id].is_mastered ? 'Mastered!' : 'Answer correctly 2 consecutive times to master'"
                        >
                            <span class="text-[11px] text-muted-foreground font-medium">Mastery:</span>
                            <div class="flex items-center gap-1">
                                <span
                                    :class="[
                                        'h-2 w-2 rounded-full',
                                        masteryState[q.id].consecutive_correct >= 1
                                            ? 'bg-emerald-500'
                                            : 'bg-sidebar-border',
                                    ]"
                                />
                                <span
                                    :class="[
                                        'h-2 w-2 rounded-full',
                                        masteryState[q.id].consecutive_correct >= 2
                                            ? 'bg-emerald-500'
                                            : 'bg-sidebar-border',
                                    ]"
                                />
                            </div>
                            <span
                                v-if="masteryState[q.id].is_mastered"
                                class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400"
                            >
                                ✓ Mastered
                            </span>
                            <span v-else class="text-[10px] text-muted-foreground">
                                {{ masteryState[q.id].consecutive_correct }}/2
                            </span>
                        </div>

                        <!-- Reveal Answer Action Button -->
                        <button
                            type="button"
                            @click="revealAnswer(q)"
                            :disabled="!!revealedAnswers[q.id] || isSubmitted"
                            :class="[
                                'inline-flex items-center gap-1.5 rounded-xl px-3 py-1.5 text-xs font-semibold transition-all',
                                revealedAnswers[q.id]
                                    ? 'border border-emerald-500/30 bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 cursor-default'
                                    : 'border border-amber-500/30 bg-amber-500/10 text-amber-700 dark:text-amber-400 hover:bg-amber-500/20 active:scale-95 shadow-xs',
                            ]"
                        >
                            <span v-if="isRevealing[q.id]" class="inline-block animate-spin">⏳</span>
                            <span v-else-if="revealedAnswers[q.id]">✓ Answer Revealed</span>
                            <span v-else>👁️ Reveal Answer</span>
                        </button>
                    </div>
                </div>

                <!-- Answer Revealed Notice Banner (Explaining mark forfeiture) -->
                <div
                    v-if="forfeitedQuestions[q.id]"
                    class="flex items-center gap-2 rounded-xl border border-amber-500/20 bg-amber-500/5 px-3 py-2 text-xs text-amber-700 dark:text-amber-400"
                >
                    <span>⚠️</span>
                    <span>
                        <strong>Answer revealed:</strong> This question is marked as forfeited (0 points) and added to your <strong>Weak MCQs list</strong> for targeted revision.
                    </span>
                </div>

                <!-- Options List -->
                <div class="flex flex-col gap-2.5">
                    <button
                        v-for="(option, idx) in q.payload?.options || []"
                        :key="idx"
                        type="button"
                        @click="selectOption(q, idx)"
                        :disabled="isSubmitted"
                        :class="[
                            'group flex items-center justify-between rounded-xl border p-4 text-left text-sm transition-all',
                            // 1. Revealed state styling
                            revealedAnswers[q.id] && revealedAnswers[q.id].correct_option_index === idx
                                ? 'border-emerald-500 bg-emerald-500/10 font-semibold text-emerald-700 dark:text-emerald-300 ring-2 ring-emerald-500/40'
                                : revealedAnswers[q.id] && selectedAnswers[q.id] === idx && revealedAnswers[q.id].correct_option_index !== idx
                                  ? 'border-destructive bg-destructive/10 font-semibold text-destructive'
                                  // 2. Post-submission grading state
                                  : isSubmitted && (serverResults[q.id]?.correct_index === idx || (!serverResults[q.id] && q.payload?.correct_option_index === idx))
                                    ? 'border-emerald-500 bg-emerald-500/10 font-semibold text-emerald-700 dark:text-emerald-400'
                                    : selectedAnswers[q.id] === idx
                                      ? isSubmitted && serverResults[q.id] && !serverResults[q.id]?.correct
                                        ? 'border-destructive bg-destructive/10 font-semibold text-destructive'
                                        : 'border-primary bg-primary/10 font-semibold text-primary shadow-xs'
                                      : 'border-sidebar-border/70 bg-sidebar/20 text-foreground hover:border-sidebar-border hover:bg-sidebar/40',
                        ]"
                    >
                        <div class="flex items-center gap-3">
                            <span
                                :class="[
                                    'flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-xs font-bold transition-colors',
                                    revealedAnswers[q.id] && revealedAnswers[q.id].correct_option_index === idx
                                        ? 'bg-emerald-500 text-white'
                                        : selectedAnswers[q.id] === idx
                                          ? 'bg-primary text-primary-foreground'
                                          : 'bg-sidebar-accent text-muted-foreground group-hover:text-foreground',
                                ]"
                            >
                                {{ String.fromCharCode(65 + idx) }}
                            </span>
                            <span>{{ typeof option === 'object' ? option.text || '' : option }}</span>
                        </div>

                        <!-- Right badge: Correct vs Selected Indicator -->
                        <div class="flex items-center gap-2">
                            <span
                                v-if="revealedAnswers[q.id] && revealedAnswers[q.id].correct_option_index === idx"
                                class="rounded-md bg-emerald-500/20 px-2 py-0.5 text-[11px] font-bold text-emerald-700 dark:text-emerald-300"
                            >
                                ✓ Correct Answer
                            </span>
                            <span
                                v-else-if="revealedAnswers[q.id] && selectedAnswers[q.id] === idx"
                                class="rounded-md bg-destructive/20 px-2 py-0.5 text-[11px] font-bold text-destructive"
                            >
                                ✗ Your Choice
                            </span>
                            <div
                                v-else-if="selectedAnswers[q.id] === idx"
                                class="h-3 w-3 rounded-full bg-primary"
                            />
                        </div>
                    </button>
                </div>

                <!-- Solution Explanation Toggle & Card -->
                <div
                    v-if="
                        mode === 'practice' ||
                        isSubmitted ||
                        revealedAnswers[q.id] ||
                        serverResults[q.id]?.explanation ||
                        q.explanation
                    "
                    class="mt-1"
                >
                    <button
                        type="button"
                        @click="toggleExplanation(q.id)"
                        class="inline-flex items-center gap-1.5 text-xs font-semibold text-primary hover:underline"
                    >
                        <span>💡</span>
                        {{ showExplanations[q.id] ? 'Hide Solution Explanation' : 'View Solution Explanation' }}
                    </button>

                    <div
                        v-if="showExplanations[q.id]"
                        class="mt-2.5 rounded-xl border border-primary/20 bg-primary/5 p-4 text-xs leading-relaxed text-foreground shadow-xs"
                    >
                        <div class="flex items-center gap-2 mb-1 text-primary font-bold">
                            <span>📘</span>
                            <span>Solution & Key Insights:</span>
                        </div>
                        <p class="whitespace-pre-line text-muted-foreground">
                            {{
                                revealedAnswers[q.id]?.explanation ||
                                serverResults[q.id]?.explanation ||
                                q.explanation ||
                                q.payload?.explanation ||
                                'The correct option has been confirmed by our academic syllabus.'
                            }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bottom Action Bar / Pagination Footer -->
        <div
            class="sticky bottom-4 z-40 mt-4 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-sidebar-border/80 bg-background/95 p-4 shadow-lg backdrop-blur-md dark:bg-card/95"
        >
            <!-- Page View Bottom Bar: Page summary and Submit CTA (Top bar controls pagination) -->
            <template v-if="viewMode === 'page'">
                <div class="flex items-center gap-3 text-xs text-muted-foreground font-medium">
                    <div class="flex items-center gap-1.5">
                        <span>Page</span>
                        <span class="rounded-md bg-sidebar px-2 py-0.5 font-bold text-foreground">
                            {{ currentPage }}
                        </span>
                        <span>of {{ totalPages }}</span>
                    </div>
                    <span>•</span>
                    <span>{{ answeredCount }} of {{ questions.length }} Answered</span>
                    <span v-if="currentPage < totalPages" class="hidden sm:inline text-xs text-primary cursor-pointer hover:underline" @click="setPage(currentPage + 1)">
                        (Next Page Available ↑)
                    </span>
                </div>
            </template>

            <!-- Focus Mode Navigation (Previous Question / Next Question) -->
            <template v-else>
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        @click="prevQuestion"
                        :disabled="currentQuestionIndex === 0"
                        class="rounded-xl border border-sidebar-border bg-sidebar px-4 py-2 text-xs font-semibold text-foreground transition-all hover:bg-sidebar-accent disabled:opacity-40"
                    >
                        ← Previous Question
                    </button>
                    <button
                        v-if="currentQuestionIndex < questions.length - 1"
                        type="button"
                        @click="nextQuestion"
                        class="rounded-xl bg-primary px-4 py-2 text-xs font-semibold text-primary-foreground shadow-xs transition-all hover:bg-primary/90"
                    >
                        Next Question →
                    </button>
                </div>

                <div class="text-xs text-muted-foreground font-medium">
                    Question <span class="font-bold text-foreground">{{ currentQuestionIndex + 1 }}</span> of <span class="font-bold text-foreground">{{ questions.length }}</span>
                </div>
            </template>

            <!-- Final Submit Test CTA -->
            <button
                v-if="!isSubmitted"
                type="button"
                @click="submitTest"
                :disabled="isSubmitting"
                class="rounded-xl bg-emerald-600 px-5 py-2.5 text-xs font-bold text-white shadow-sm transition-all hover:bg-emerald-700 active:scale-95 disabled:opacity-50"
            >
                {{ isSubmitting ? 'Evaluating...' : 'Submit Test ✓' }}
            </button>
        </div>
    </div>
</template>
