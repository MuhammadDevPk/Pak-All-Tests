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

const emit = defineEmits(['question-change', 'page-context-change']);

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
    if (!props.questions.length) {
        return 1;
    }

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

const computedPageContext = computed(() => {
    const total = props.questions.length;

    if (viewMode.value === 'focus') {
        const idx = currentQuestionIndex.value;
        const q = props.questions[idx] || null;
        let userSelectedText = null;

        if (q) {
            const selectedIdx = selectedAnswers.value[q.id];

            if (selectedIdx !== undefined && selectedIdx !== null) {
                const letter = String.fromCharCode(65 + Number(selectedIdx));
                const opts = q.payload?.options || [];
                const rawOpt = opts[selectedIdx];
                const text =
                    typeof rawOpt === 'object'
                        ? rawOpt?.text || ''
                        : String(rawOpt ?? '');
                userSelectedText = `${letter}) ${text.replace(/<[^>]*>?/gm, '').trim()}`;
            }
        }

        return {
            view_mode: 'focus',
            current_page: 1,
            total_pages: total,
            page_size: 1,
            showing_from: total > 0 ? idx + 1 : 0,
            showing_to: total > 0 ? idx + 1 : 0,
            total_questions: total,
            active_question_number: total > 0 ? idx + 1 : null,
            visible_questions: q
                ? [
                      {
                          number: idx + 1,
                          id: q.id,
                          statement: (q.question_text || '')
                              .replace(/<[^>]*>?/gm, '')
                              .trim(),
                          options: (q.payload?.options || []).map((opt, i) => {
                              const letter = String.fromCharCode(65 + i);
                              const rawText =
                                  typeof opt === 'object'
                                      ? opt?.text || ''
                                      : String(opt ?? '');

                              return `${letter}) ${rawText.replace(/<[^>]*>?/gm, '').trim()}`;
                          }),
                          user_selected: userSelectedText,
                          is_answered: userSelectedText !== null,
                      },
                  ]
                : [],
        };
    }

    const start = (currentPage.value - 1) * pageSize.value;
    const end = Math.min(start + pageSize.value, total);
    const visible = props.questions.slice(start, end).map((q, i) => {
        const globalIdx = start + i;
        const selectedIdx = selectedAnswers.value[q.id];
        let userSelectedText = null;

        if (selectedIdx !== undefined && selectedIdx !== null) {
            const letter = String.fromCharCode(65 + Number(selectedIdx));
            const opts = q.payload?.options || [];
            const rawOpt = opts[selectedIdx];
            const text =
                typeof rawOpt === 'object'
                    ? rawOpt?.text || ''
                    : String(rawOpt ?? '');
            userSelectedText = `${letter}) ${text.replace(/<[^>]*>?/gm, '').trim()}`;
        }

        return {
            number: globalIdx + 1,
            id: q.id,
            statement: (q.question_text || '').replace(/<[^>]*>?/gm, '').trim(),
            options: (q.payload?.options || []).map((opt, optIdx) => {
                const letter = String.fromCharCode(65 + optIdx);
                const rawText =
                    typeof opt === 'object'
                        ? opt?.text || ''
                        : String(opt ?? '');

                return `${letter}) ${rawText.replace(/<[^>]*>?/gm, '').trim()}`;
            }),
            user_selected: userSelectedText,
            is_answered: userSelectedText !== null,
        };
    });

    return {
        view_mode: 'page',
        current_page: currentPage.value,
        total_pages: totalPages.value,
        page_size: pageSize.value,
        showing_from: total > 0 ? start + 1 : 0,
        showing_to: end,
        total_questions: total,
        active_question_number: currentQuestionIndex.value + 1,
        visible_questions: visible,
    };
});

const emitQuestionChange = () => {
    const q = currentFocusQuestion.value;
    const pageCtx = computedPageContext.value;

    emit('page-context-change', pageCtx);

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
            typeof rawOpt === 'object'
                ? rawOpt?.text || ''
                : String(rawOpt ?? '');
        userSelectedText = `${letter}) ${text}`;
    }

    emit('question-change', {
        ...q,
        _globalIndex: currentQuestionIndex.value,
        number: currentQuestionIndex.value + 1,
        user_selected: userSelectedText,
        user_selected_index: selectedIdx ?? null,
        attempts_made:
            selectedIdx !== undefined && selectedIdx !== null ? 1 : 0,
        was_revealed: !!revealedAnswers.value[q.id],
        page_context: pageCtx,
    });
};

const setFocusedQuestion = (index) => {
    if (
        index >= 0 &&
        index < props.questions.length &&
        currentQuestionIndex.value !== index
    ) {
        currentQuestionIndex.value = index;
        emitQuestionChange();
    }
};

const selectOption = async (question, optionIndex) => {
    if (isSubmitted.value || !question) {
        return;
    }

    const qIdx = props.questions.findIndex((item) => item.id === question.id);

    if (qIdx !== -1) {
        currentQuestionIndex.value = qIdx;
    }

    selectedAnswers.value[question.id] = optionIndex;
    emitQuestionChange();

    // In practice or weak mode, record attempt on the server for Threshold Mastery
    try {
        const csrfToken =
            document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute('content') || '';
        const response = await fetch(
            `/api/questions/${question.id}/record-attempt`,
            {
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
            },
        );

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
    if (
        !question ||
        revealedAnswers.value[question.id] ||
        isRevealing.value[question.id]
    ) {
        return;
    }

    isRevealing.value[question.id] = true;
    forfeitedQuestions.value[question.id] = true;

    try {
        const csrfToken =
            document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute('content') || '';
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
        const startIdx = (page - 1) * pageSize.value;

        if (startIdx >= 0 && startIdx < props.questions.length) {
            currentQuestionIndex.value = startIdx;
        }

        emitQuestionChange();
        document
            .querySelector('#mcq-player-container')
            ?.scrollIntoView({ behavior: 'smooth' });
    }
};

const jumpInputQuery = ref('');
const paletteFilter = ref('all'); // 'all', 'answered', 'unanswered', 'revealed'

const filteredPaletteQuestions = computed(() => {
    return props.questions
        .map((q, idx) => {
            const isAnswered = selectedAnswers.value[q.id] !== undefined;
            const isRevealed = !!revealedAnswers.value[q.id];

            return {
                ...q,
                _index: idx,
                _number: idx + 1,
                isAnswered,
                isRevealed,
            };
        })
        .filter((item) => {
            if (paletteFilter.value === 'answered') {
                return item.isAnswered;
            }

            if (paletteFilter.value === 'unanswered') {
                return !item.isAnswered;
            }

            if (paletteFilter.value === 'revealed') {
                return item.isRevealed;
            }

            return true;
        });
});

const jumpToQuestionNumber = () => {
    const num = parseInt(jumpInputQuery.value, 10);

    if (!isNaN(num) && num >= 1 && num <= props.questions.length) {
        goToQuestion(num - 1);
        jumpInputQuery.value = '';
    } else {
        alert(
            `Please enter a valid question number between 1 and ${props.questions.length}`,
        );
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
                const targetElement = document.querySelector(
                    `#question-card-${index}`,
                );

                if (targetElement) {
                    targetElement.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center',
                    });
                    targetElement.classList.add('ring-2', 'ring-primary');
                    setTimeout(() => {
                        targetElement.classList.remove(
                            'ring-2',
                            'ring-primary',
                        );
                    }, 1500);
                }
            }, 100);
        }
    }
};

const nextQuestion = () => {
    if (currentQuestionIndex.value < props.questions.length - 1) {
        currentQuestionIndex.value++;
        currentPage.value =
            Math.floor(currentQuestionIndex.value / pageSize.value) + 1;
        emitQuestionChange();
    }
};

const prevQuestion = () => {
    if (currentQuestionIndex.value > 0) {
        currentQuestionIndex.value--;
        currentPage.value =
            Math.floor(currentQuestionIndex.value / pageSize.value) + 1;
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
        const isForfeited = forfeitedQuestions.value[q.id];

        // 1. Weak mode or practice attempt recorded on server
        if (!isForfeited && masteryState.value[q.id]?.is_correct) {
            correctCount++;

            return;
        }

        const correctIndex =
            serverResults.value[q.id]?.correct_index ??
            revealedAnswers.value[q.id]?.correct_option_index ??
            q.payload?.correct_option_index;

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
        isPassed:
            percentage >= (props.module?.content_meta?.passing_score || 60),
    };
});

const submitTest = async () => {
    isSubmitted.value = true;

    if (timerInterval) {
        clearInterval(timerInterval);
    }

    // Automatically expand solution explanations across all questions upon submission
    props.questions.forEach((q) => {
        showExplanations.value[q.id] = true;
    });

    const submitUrl = isWeakMode.value
        ? '/api/weak-questions/submit'
        : props.module?.id
          ? `/api/modules/${props.module.id}/submit`
          : null;

    if (!submitUrl) {
        return;
    }

    isSubmitting.value = true;

    try {
        const csrfToken =
            document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute('content') || '';
        const response = await fetch(submitUrl, {
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

const getOptionClasses = (q, idx) => {
    const isSelected = selectedAnswers.value[q.id] === idx;
    const isRevealedOption =
        revealedAnswers.value[q.id] &&
        revealedAnswers.value[q.id].correct_option_index === idx;
    const isRevealedMismatch =
        revealedAnswers.value[q.id] &&
        isSelected &&
        revealedAnswers.value[q.id].correct_option_index !== idx;

    // 1. Revealed Answer State
    if (isRevealedOption) {
        return 'border-emerald-500 bg-emerald-50/70 font-semibold text-emerald-950 dark:bg-emerald-950/40 dark:text-emerald-100 ring-2 ring-emerald-500/40';
    }

    if (isRevealedMismatch) {
        return 'border-destructive bg-destructive/10 font-semibold text-destructive';
    }

    // 2. Post-Submission State
    if (isSubmitted.value) {
        const correctIndex =
            serverResults.value[q.id]?.correct_index ??
            q.payload?.correct_option_index;

        if (correctIndex !== undefined && correctIndex === idx) {
            return 'border-emerald-500 bg-emerald-50/70 font-semibold text-emerald-950 dark:bg-emerald-950/40 dark:text-emerald-100 ring-2 ring-emerald-500/40';
        }

        if (isSelected) {
            const isUserCorrect = serverResults.value[q.id]
                ? serverResults.value[q.id].correct
                : masteryState.value[q.id]?.is_correct;

            return isUserCorrect
                ? 'border-emerald-500 bg-emerald-50/70 font-semibold text-emerald-950 dark:bg-emerald-950/40 dark:text-emerald-100'
                : 'border-destructive bg-destructive/10 font-semibold text-destructive';
        }

        return 'border-zinc-200/90 bg-zinc-50/40 text-senselab-heading hover:border-zinc-300 hover:bg-white dark:border-zinc-800 dark:bg-zinc-900/40 dark:hover:bg-zinc-900';
    }

    // 3. Immediate Live Practice / Weak Mode Feedback
    if (
        (isWeakMode.value || props.mode === 'practice') &&
        isSelected &&
        masteryState.value[q.id]
    ) {
        return masteryState.value[q.id].is_correct
            ? 'border-emerald-500 bg-emerald-50/70 font-semibold text-emerald-950 dark:bg-emerald-950/40 dark:text-emerald-100'
            : 'border-destructive bg-destructive/10 font-semibold text-destructive';
    }

    // 4. Normal Active Selection during active test session
    if (isSelected) {
        return 'border-sky-500 bg-sky-50/70 font-semibold text-sky-950 dark:bg-sky-950/40 dark:text-sky-100 shadow-2xs';
    }

    // 5. Default Unselected
    return 'border-zinc-200/90 bg-zinc-50/40 text-senselab-heading hover:border-zinc-300 hover:bg-white dark:border-zinc-800 dark:bg-zinc-900/40 dark:hover:bg-zinc-900';
};

const getOptionCircleClasses = (q, idx) => {
    const isSelected = selectedAnswers.value[q.id] === idx;
    const isRevealedOption =
        revealedAnswers.value[q.id] &&
        revealedAnswers.value[q.id].correct_option_index === idx;

    if (isRevealedOption) {
        return 'bg-emerald-500 text-white';
    }

    if (isSubmitted.value) {
        const correctIndex =
            serverResults.value[q.id]?.correct_index ??
            q.payload?.correct_option_index;

        if (correctIndex !== undefined && correctIndex === idx) {
            return 'bg-emerald-500 text-white';
        }

        if (isSelected) {
            const isUserCorrect = serverResults.value[q.id]
                ? serverResults.value[q.id].correct
                : masteryState.value[q.id]?.is_correct;

            return isUserCorrect
                ? 'bg-emerald-500 text-white'
                : 'bg-destructive text-white';
        }

        return 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400 group-hover:bg-zinc-200 dark:group-hover:bg-zinc-700';
    }

    if (
        (isWeakMode.value || props.mode === 'practice') &&
        isSelected &&
        masteryState.value[q.id]
    ) {
        return masteryState.value[q.id].is_correct
            ? 'bg-emerald-500 text-white'
            : 'bg-destructive text-white';
    }

    if (isSelected) {
        return 'bg-sky-600 text-white';
    }

    return 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400 group-hover:bg-zinc-200 dark:group-hover:bg-zinc-700';
};

const getOptionBadge = (q, idx) => {
    const isSelected = selectedAnswers.value[q.id] === idx;
    const isRevealedOption =
        revealedAnswers.value[q.id] &&
        revealedAnswers.value[q.id].correct_option_index === idx;

    if (isRevealedOption) {
        return { type: 'success', label: '✓ Correct Answer' };
    }

    if (revealedAnswers.value[q.id] && isSelected) {
        return { type: 'danger', label: '✗ Your Choice' };
    }

    if (isSubmitted.value) {
        const correctIndex =
            serverResults.value[q.id]?.correct_index ??
            q.payload?.correct_option_index;

        if (correctIndex !== undefined && correctIndex === idx) {
            return isSelected
                ? { type: 'success', label: '✓ Correct' }
                : { type: 'success', label: '✓ Correct Answer' };
        }

        if (isSelected) {
            const isUserCorrect = serverResults.value[q.id]
                ? serverResults.value[q.id].correct
                : masteryState.value[q.id]?.is_correct;

            return isUserCorrect
                ? { type: 'success', label: '✓ Correct' }
                : { type: 'danger', label: '✗ Your Choice' };
        }

        return null;
    }

    if (
        (isWeakMode.value || props.mode === 'practice') &&
        isSelected &&
        masteryState.value[q.id]
    ) {
        return masteryState.value[q.id].is_correct
            ? { type: 'success', label: '✓ Correct' }
            : { type: 'danger', label: '✗ Needs Revision' };
    }

    if (isSelected) {
        return { type: 'dot' };
    }

    return null;
};

const canViewExplanation = (questionId) => {
    return (
        isSubmitted.value ||
        !!revealedAnswers.value[questionId] ||
        !!serverResults.value[questionId]?.explanation ||
        (selectedAnswers.value[questionId] !== undefined &&
            (isWeakMode.value || props.mode === 'practice'))
    );
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

watch(currentPage, (newPage) => {
    if (viewMode.value === 'page') {
        const startIdx = (newPage - 1) * pageSize.value;

        if (startIdx >= 0 && startIdx < props.questions.length) {
            if (
                currentQuestionIndex.value < startIdx ||
                currentQuestionIndex.value >= startIdx + pageSize.value
            ) {
                currentQuestionIndex.value = startIdx;
            }
        }
    }

    emitQuestionChange();
});

watch(pageSize, () => {
    currentPage.value = 1;
    currentQuestionIndex.value = 0;
    emitQuestionChange();
});

watch(viewMode, (newMode) => {
    if (newMode === 'page') {
        currentPage.value =
            Math.floor(currentQuestionIndex.value / pageSize.value) + 1;
    }

    emitQuestionChange();
});

watch(currentQuestionIndex, () => {
    emitQuestionChange();
});

watch(
    () => props.questions,
    () => {
        emitQuestionChange();
    },
);
</script>

<template>
    <div id="mcq-player-container" class="flex flex-col gap-6">
        <!-- Modern SenseLab Header Controls Bar -->
        <div
            class="shadow-senselab-card relative flex flex-wrap items-center justify-between gap-4 rounded-3xl border border-zinc-200/90 bg-white/95 p-4 backdrop-blur-md transition-all duration-150 sm:p-5 dark:border-zinc-800 dark:bg-zinc-950/90"
            :class="isNavigatorOpen ? 'z-50' : 'z-30'"
        >
            <div class="flex items-center gap-3.5">
                <div
                    class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl border border-sky-500/20 bg-sky-500/10 text-lg shadow-2xs dark:border-sky-500/30 dark:bg-sky-950/40"
                >
                    <span v-if="isWeakMode">🎯</span>
                    <span v-else>📝</span>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h2
                            class="text-senselab-heading text-base font-bold tracking-tight sm:text-lg"
                        >
                            {{ module.title || 'MCQ Test Player' }}
                        </h2>
                        <span
                            v-if="isWeakMode"
                            class="inline-flex items-center gap-1 rounded-full border border-amber-500/30 bg-amber-500/10 px-2.5 py-0.5 text-[11px] font-semibold text-amber-700 dark:text-amber-400"
                        >
                            <span
                                class="h-1.5 w-1.5 animate-pulse rounded-full bg-amber-500"
                            ></span>
                            Weak MCQs Revision
                        </span>
                    </div>
                    <p class="mt-0.5 text-xs text-brand-muted">
                        <span v-if="viewMode === 'page'">
                            Showing Questions
                            {{ (currentPage - 1) * pageSize + 1 }}–{{
                                Math.min(
                                    currentPage * pageSize,
                                    questions.length,
                                )
                            }}
                            of {{ questions.length }}
                        </span>
                        <span v-else>
                            Question {{ currentQuestionIndex + 1 }} of
                            {{ questions.length }}
                        </span>
                        •
                        <span class="text-senselab-heading font-semibold"
                            >{{ answeredCount }} Answered</span
                        >
                    </p>
                </div>
            </div>

            <!-- Toolbar Controls: View Mode, Page Size, Timer, Navigator -->
            <div class="flex flex-wrap items-center gap-2 sm:gap-3">
                <!-- Dual View Mode Switcher -->
                <div
                    class="flex items-center rounded-lg border border-sidebar-border bg-sidebar/50 p-0.5 text-xs"
                >
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
                <div
                    v-if="viewMode === 'page'"
                    class="flex items-center gap-1.5 text-xs text-muted-foreground"
                >
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
                <div class="relative z-50">
                    <button
                        type="button"
                        @click="isNavigatorOpen = !isNavigatorOpen"
                        class="flex cursor-pointer items-center gap-2 rounded-xl border border-sidebar-border bg-background px-3 py-1.5 text-xs font-semibold text-foreground shadow-xs transition-all hover:border-primary/50 hover:bg-sidebar-accent"
                        title="Open full question palette to view answered status or jump to any question"
                    >
                        <span
                            class="flex h-5 w-5 items-center justify-center rounded-md bg-primary/10 text-[11px] font-bold text-primary"
                        >
                            #
                        </span>
                        <span class="font-bold">Question Palette</span>
                        <span
                            class="rounded-full bg-sidebar px-2 py-0.5 text-[10px] font-semibold text-muted-foreground"
                        >
                            {{ answeredCount }}/{{ questions.length }}
                        </span>
                        <span
                            class="text-[10px] text-muted-foreground transition-transform duration-200"
                            :class="{ 'rotate-180': isNavigatorOpen }"
                            >▼</span
                        >
                    </button>

                    <!-- Backdrop to dismiss on outside click -->
                    <div
                        v-if="isNavigatorOpen"
                        class="fixed inset-0 z-40 bg-black/10 backdrop-blur-xs"
                        @click="isNavigatorOpen = false"
                    />

                    <!-- Collapsible Question Palette Modal / Popover -->
                    <div
                        v-if="isNavigatorOpen"
                        class="absolute top-full right-0 z-50 mt-2 w-80 max-w-[calc(100vw-2rem)] rounded-2xl border border-zinc-200/90 bg-white/98 p-4 shadow-2xl backdrop-blur-xl sm:w-96 dark:border-zinc-800 dark:bg-zinc-900/98"
                    >
                        <!-- Header with Title & Close -->
                        <div
                            class="mb-3 flex items-center justify-between border-b border-sidebar-border/50 pb-2.5"
                        >
                            <div>
                                <div
                                    class="flex items-center gap-1.5 text-sm font-bold text-foreground"
                                >
                                    <span>📋</span>
                                    <span>Question Palette & Navigator</span>
                                </div>
                                <p class="text-[11px] text-muted-foreground">
                                    Click any number to view or jump directly to
                                    it
                                </p>
                            </div>
                            <button
                                type="button"
                                @click="isNavigatorOpen = false"
                                class="rounded-lg p-1 text-xs text-muted-foreground transition-colors hover:bg-sidebar-accent hover:text-foreground"
                            >
                                ✕
                            </button>
                        </div>

                        <!-- Direct Jump by Number Input -->
                        <form
                            @submit.prevent="jumpToQuestionNumber"
                            class="mb-3 flex items-center gap-2"
                        >
                            <div class="relative flex-1">
                                <span
                                    class="absolute top-1/2 left-2.5 -translate-y-1/2 text-xs font-semibold text-muted-foreground"
                                    >#</span
                                >
                                <input
                                    v-model="jumpInputQuery"
                                    type="number"
                                    min="1"
                                    :max="questions.length"
                                    :placeholder="
                                        'Jump to question # (1–' +
                                        questions.length +
                                        ')'
                                    "
                                    class="w-full rounded-lg border border-sidebar-border bg-background py-1.5 pr-2.5 pl-6 text-xs text-foreground placeholder:text-muted-foreground focus:border-primary focus:outline-hidden"
                                />
                            </div>
                            <button
                                type="submit"
                                class="rounded-lg bg-primary px-3 py-1.5 text-xs font-bold text-primary-foreground transition-colors hover:bg-primary/90"
                            >
                                Jump ➔
                            </button>
                        </form>

                        <!-- Filter Tabs -->
                        <div
                            class="mb-2.5 flex flex-wrap items-center gap-1 border-b border-sidebar-border/40 pb-2 text-[11px]"
                        >
                            <button
                                type="button"
                                @click="paletteFilter = 'all'"
                                :class="[
                                    'rounded-md px-2 py-0.5 font-semibold transition-colors',
                                    paletteFilter === 'all'
                                        ? 'bg-primary/10 font-bold text-primary'
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
                                        ? 'bg-emerald-500/10 font-bold text-emerald-600'
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
                                        ? 'bg-sidebar font-bold text-foreground'
                                        : 'text-muted-foreground hover:text-foreground',
                                ]"
                            >
                                Unattempted ({{
                                    questions.length - answeredCount
                                }})
                            </button>
                            <button
                                v-if="Object.keys(revealedAnswers).length > 0"
                                type="button"
                                @click="paletteFilter = 'revealed'"
                                :class="[
                                    'rounded-md px-2 py-0.5 font-semibold transition-colors',
                                    paletteFilter === 'revealed'
                                        ? 'bg-amber-500/10 font-bold text-amber-600'
                                        : 'text-muted-foreground hover:text-foreground',
                                ]"
                            >
                                👁️ Revealed ({{
                                    Object.keys(revealedAnswers).length
                                }})
                            </button>
                        </div>

                        <!-- Status Legend -->
                        <div
                            class="mb-3 flex flex-wrap items-center gap-3 text-[10px] text-muted-foreground"
                        >
                            <div class="flex items-center gap-1">
                                <span class="h-2 w-2 rounded-full bg-primary" />
                                Current
                            </div>
                            <div class="flex items-center gap-1">
                                <span
                                    class="h-2 w-2 rounded-full bg-emerald-500"
                                />
                                Answered
                            </div>
                            <div class="flex items-center gap-1">
                                <span
                                    class="h-2 w-2 rounded-full bg-amber-500"
                                />
                                Revealed
                            </div>
                            <div class="flex items-center gap-1">
                                <span
                                    class="h-2 w-2 rounded-full bg-muted-foreground/30"
                                />
                                Unattempted
                            </div>
                        </div>

                        <!-- Question Numbers Grid -->
                        <div class="max-h-60 overflow-y-auto pr-1">
                            <div
                                class="grid grid-cols-6 gap-1.5 sm:grid-cols-7"
                            >
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
                                              ? 'border border-emerald-500/30 bg-emerald-500/15 font-bold text-emerald-700 hover:bg-emerald-500/25 dark:text-emerald-400'
                                              : item.isRevealed
                                                ? 'border border-amber-500/30 bg-amber-500/15 font-bold text-amber-700 hover:bg-amber-500/25 dark:text-amber-400'
                                                : 'bg-sidebar-accent/50 text-muted-foreground hover:bg-sidebar-accent hover:text-foreground',
                                    ]"
                                    :title="
                                        'Question #' +
                                        item._number +
                                        (item.isAnswered ? ' (Answered)' : '') +
                                        (item.isRevealed ? ' (Revealed)' : '')
                                    "
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
                            ? 'animate-pulse bg-destructive/10 text-destructive'
                            : 'border border-sidebar-border bg-sidebar text-foreground',
                    ]"
                >
                    <span>⏱️</span>
                    <span>{{ formattedTime }}</span>
                </div>

                <!-- Focus Mode Submit Button on Top -->
                <button
                    v-if="viewMode === 'focus' && !isSubmitted"
                    type="button"
                    @click="submitTest"
                    :disabled="isSubmitting"
                    class="inline-flex cursor-pointer items-center gap-1.5 rounded-full px-4 py-1.5 text-xs font-bold text-white shadow-xs transition-all duration-200 hover:scale-[1.02] hover:shadow-md active:scale-95 disabled:opacity-50"
                    style="background: var(--brand-gradient)"
                >
                    <span>{{
                        isSubmitting ? 'Evaluating...' : 'Submit Test ✓'
                    }}</span>
                </button>

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
            class="shadow-senselab-card relative z-20 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-zinc-200/80 bg-white/80 p-3.5 backdrop-blur-md transition-all dark:border-zinc-800 dark:bg-zinc-900/60"
        >
            <!-- Left: Previous Page & Next Page Primary Navigation (Tactile SenseLab Buttons) -->
            <div class="flex items-center gap-2">
                <button
                    type="button"
                    @click="setPage(currentPage - 1)"
                    :disabled="currentPage === 1"
                    class="inline-flex cursor-pointer items-center gap-1.5 rounded-full border border-zinc-200/90 bg-white px-4 py-2 text-xs font-bold text-zinc-800 shadow-xs transition-all hover:border-zinc-300 hover:bg-zinc-50 active:scale-95 disabled:cursor-not-allowed disabled:opacity-40 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200"
                >
                    <span>←</span>
                    <span>Previous Page</span>
                </button>
                <button
                    type="button"
                    @click="setPage(currentPage + 1)"
                    :disabled="currentPage >= totalPages"
                    class="inline-flex cursor-pointer items-center gap-1.5 rounded-full border border-zinc-200/90 bg-white px-4 py-2 text-xs font-bold text-zinc-800 shadow-xs transition-all hover:border-zinc-300 hover:bg-zinc-50 active:scale-95 disabled:cursor-not-allowed disabled:opacity-40 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200"
                >
                    <span>Next Page</span>
                    <span>→</span>
                </button>
            </div>

            <!-- Center: Page Indicator & Question Range Details -->
            <div
                class="flex items-center gap-3 text-xs font-medium text-brand-muted"
            >
                <div class="flex items-center gap-1.5">
                    <span>Page</span>
                    <span
                        class="rounded-lg bg-sky-500/10 px-2.5 py-0.5 text-sm font-bold text-sky-600 dark:text-sky-400"
                    >
                        {{ currentPage }}
                    </span>
                    <span>of</span>
                    <span class="text-senselab-heading text-sm font-bold">{{
                        totalPages
                    }}</span>
                </div>
                <span class="hidden text-zinc-300 sm:inline dark:text-zinc-700"
                    >|</span
                >
                <span
                    class="text-senselab-heading hidden text-xs font-medium sm:inline"
                >
                    Showing Questions {{ (currentPage - 1) * pageSize + 1 }}–{{
                        Math.min(currentPage * pageSize, questions.length)
                    }}
                    of {{ questions.length }}
                </span>
            </div>

            <!-- Right: Quick Jump by Page Selector + TOP SUBMIT TEST BUTTON -->
            <div class="flex items-center gap-2.5 text-xs">
                <div class="flex items-center gap-1.5">
                    <span class="hidden text-brand-muted md:inline"
                        >Jump to page:</span
                    >
                    <select
                        :value="currentPage"
                        @change="setPage(Number($event.target.value))"
                        class="cursor-pointer rounded-xl border border-zinc-200/90 bg-white px-3 py-1.5 text-xs font-semibold text-zinc-800 shadow-xs focus:border-sky-500 focus:outline-hidden dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-200"
                    >
                        <option v-for="p in totalPages" :key="p" :value="p">
                            Page {{ p }} (Q{{ (p - 1) * pageSize + 1 }}–{{
                                Math.min(p * pageSize, questions.length)
                            }})
                        </option>
                    </select>
                </div>

                <!-- Submit Test Button on Top (Requested by User) -->
                <button
                    v-if="!isSubmitted"
                    type="button"
                    @click="submitTest"
                    :disabled="isSubmitting"
                    class="inline-flex shrink-0 cursor-pointer items-center gap-1.5 rounded-full px-4 py-2 text-xs font-bold text-white shadow-xs transition-all duration-200 hover:scale-[1.02] hover:shadow-md active:scale-95 disabled:opacity-50 sm:px-5"
                    style="background: var(--brand-gradient)"
                >
                    <span>{{
                        isSubmitting ? 'Evaluating...' : 'Submit Test ✓'
                    }}</span>
                </button>
                <span
                    v-else
                    class="inline-flex items-center gap-1 rounded-full bg-emerald-500/10 px-3 py-1.5 text-xs font-bold text-emerald-600 dark:text-emerald-400"
                >
                    ✓ Submitted
                </span>
            </div>
        </div>

        <!-- Result Summary Card (After Submission) — SenseLab Session Performance Report -->
        <div
            v-if="isSubmitted && scoreSummary"
            class="shadow-senselab-elevated relative z-10 overflow-hidden rounded-3xl border border-zinc-200/90 bg-white/95 p-6 backdrop-blur-md sm:p-8 dark:border-zinc-800 dark:bg-zinc-950/95"
        >
            <div
                class="flex flex-col justify-between gap-4 border-b border-zinc-100 pb-5 sm:flex-row sm:items-center dark:border-zinc-800/80"
            >
                <div class="flex items-center gap-3">
                    <div
                        class="flex h-11 w-11 items-center justify-center rounded-2xl bg-sky-500/10 text-xl"
                    >
                        🎯
                    </div>
                    <div>
                        <h3 class="text-senselab-heading text-lg font-bold">
                            Test Results Summary
                        </h3>
                        <p class="text-xs text-brand-muted">
                            Verified session metrics evaluated against
                            institutional passing standards.
                        </p>
                    </div>
                </div>

                <span
                    :class="[
                        'inline-flex items-center gap-1.5 rounded-full px-4 py-1.5 text-xs font-bold tracking-wide shadow-2xs',
                        scoreSummary.isPassed
                            ? 'border border-emerald-500/30 bg-emerald-500/10 text-emerald-600 dark:text-emerald-400'
                            : 'border border-amber-500/30 bg-amber-500/10 text-amber-700 dark:text-amber-400',
                    ]"
                >
                    <span
                        :class="[
                            'h-2 w-2 rounded-full',
                            scoreSummary.isPassed
                                ? 'bg-emerald-500'
                                : 'animate-pulse bg-amber-500',
                        ]"
                    />
                    {{
                        scoreSummary.isPassed
                            ? 'PASSED TARGET BENCHMARK'
                            : 'NEEDS PRACTICE — WEAK MCQS ISOLATED'
                    }}
                </span>
            </div>

            <!-- Metric Tiles Grid -->
            <div class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-4">
                <!-- Score Metric Card (SenseLab Gradient Accent) -->
                <div
                    class="rounded-2xl border border-sky-100 bg-sky-50/50 p-4 text-center dark:border-sky-900/40 dark:bg-sky-950/20"
                >
                    <span
                        class="block text-xs font-bold tracking-wider text-sky-700 uppercase dark:text-sky-400"
                        >Score</span
                    >
                    <span
                        class="text-senselab-heading mt-1 block text-3xl font-extrabold tracking-tight"
                    >
                        {{ scoreSummary.percentage }}%
                    </span>
                    <span class="text-[11px] font-medium text-brand-muted"
                        >accuracy rate</span
                    >
                </div>

                <!-- Correct Metric Card (Emerald Accent) -->
                <div
                    class="rounded-2xl border border-emerald-100 bg-emerald-50/50 p-4 text-center dark:border-emerald-900/40 dark:bg-emerald-950/20"
                >
                    <span
                        class="block text-xs font-bold tracking-wider text-emerald-700 uppercase dark:text-emerald-400"
                        >Correct</span
                    >
                    <span
                        class="mt-1 block text-3xl font-extrabold tracking-tight text-emerald-600 dark:text-emerald-400"
                    >
                        {{ scoreSummary.correct }} / {{ scoreSummary.total }}
                    </span>
                    <span class="text-[11px] font-medium text-brand-muted"
                        >correct answers</span
                    >
                </div>

                <!-- Answered Metric Card (Purple Accent) -->
                <div
                    class="rounded-2xl border border-purple-100 bg-purple-50/50 p-4 text-center dark:border-purple-900/40 dark:bg-purple-950/20"
                >
                    <span
                        class="block text-xs font-bold tracking-wider text-purple-700 uppercase dark:text-purple-400"
                        >Answered</span
                    >
                    <span
                        class="text-senselab-heading mt-1 block text-3xl font-extrabold tracking-tight"
                    >
                        {{ scoreSummary.answered }}
                    </span>
                    <span class="text-[11px] font-medium text-brand-muted"
                        >of {{ scoreSummary.total }} completed</span
                    >
                </div>

                <!-- Target Pass Metric Card (Amber Accent) -->
                <div
                    class="rounded-2xl border border-amber-100 bg-amber-50/50 p-4 text-center dark:border-amber-900/40 dark:bg-amber-950/20"
                >
                    <span
                        class="block text-xs font-bold tracking-wider text-amber-700 uppercase dark:text-amber-400"
                        >Target Pass</span
                    >
                    <span
                        class="text-senselab-heading mt-1 block text-3xl font-extrabold tracking-tight"
                    >
                        {{ module.content_meta?.passing_score || 60 }}%
                    </span>
                    <span class="text-[11px] font-medium text-brand-muted"
                        >benchmark threshold</span
                    >
                </div>
            </div>

            <!-- Weak Question Spaced Repetition Note -->
            <div
                class="mt-6 flex flex-col items-start justify-between gap-3 rounded-2xl border border-zinc-200/80 bg-zinc-50/60 p-4 text-xs text-brand-muted sm:flex-row sm:items-center dark:border-zinc-800 dark:bg-zinc-900/40"
            >
                <div class="flex items-center gap-2.5">
                    <span class="text-base">💡</span>
                    <span>
                        Questions missed or revealed during this attempt are
                        actively isolated in your
                        <strong>Revision & Weak MCQs</strong> workspace for
                        spaced repetition.
                    </span>
                </div>
                <a
                    href="/revision-weak-questions"
                    class="inline-flex shrink-0 items-center gap-1.5 font-bold text-sky-600 hover:text-sky-700 hover:underline dark:text-sky-400"
                >
                    <span>View Revision Bank</span>
                    <span>→</span>
                </a>
            </div>
        </div>

        <!-- Questions Feed (Single Item in Focus Mode, Scrollable Stack in Page Mode) -->
        <div class="flex flex-col gap-6">
            <div
                v-for="q in paginatedQuestions"
                :key="q.id || q._globalIndex"
                :id="'question-card-' + q._globalIndex"
                @click="setFocusedQuestion(q._globalIndex)"
                class="shadow-senselab-card flex flex-col gap-5 rounded-3xl border bg-white/95 p-6 transition-all sm:p-7 dark:bg-zinc-950/90"
                :class="[
                    currentQuestionIndex === q._globalIndex &&
                    viewMode === 'page'
                        ? 'border-sky-500/80 shadow-md ring-2 ring-sky-500/20 dark:border-sky-400/80'
                        : 'border-zinc-200/90 hover:border-zinc-300 dark:border-zinc-800 dark:hover:border-zinc-700',
                ]"
            >
                <!-- Question Header: Number + Text on Left, Reveal Button & Mastery on Right -->
                <div
                    class="flex flex-col justify-between gap-4 sm:flex-row sm:items-start"
                >
                    <div class="flex flex-1 items-start gap-3.5">
                        <span
                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-sky-500/10 text-xs font-extrabold text-sky-600 dark:text-sky-400"
                        >
                            {{ q._globalIndex + 1 }}
                        </span>
                        <h3
                            class="text-senselab-heading text-base leading-relaxed font-bold"
                        >
                            {{ q.question_text }}
                        </h3>
                    </div>

                    <!-- Right Side Actions: Threshold Mastery Pip + Reveal Answer Button -->
                    <div class="flex shrink-0 items-center gap-2 self-start">
                        <!-- Short Answer Toggle Button -->
                        <button
                            v-if="q.question_type === 'short_answer'"
                            type="button"
                            @click="toggleExplanation(q.id)"
                            class="inline-flex cursor-pointer items-center gap-1.5 rounded-full border border-emerald-500/30 bg-emerald-500/10 px-3.5 py-1.5 text-xs font-semibold text-emerald-700 transition-all hover:bg-emerald-500/20 active:scale-95 dark:text-emerald-400"
                        >
                            <span>{{
                                showExplanations[q.id]
                                    ? '✓ Hide Answer'
                                    : '👁️ Show Answer'
                            }}</span>
                        </button>

                        <template v-else>
                            <!-- Mastery Badge (if in Weak MCQs mode or previously tracked) -->
                            <div
                                v-if="masteryState[q.id]"
                                class="flex items-center gap-1.5 rounded-full border border-zinc-200/80 bg-zinc-50 px-3 py-1 text-xs dark:border-zinc-800 dark:bg-zinc-900/60"
                                :title="
                                    masteryState[q.id].is_mastered
                                        ? 'Mastered!'
                                        : 'Answer correctly 2 consecutive times to master'
                                "
                            >
                                <span
                                    class="text-[11px] font-medium text-brand-muted"
                                    >Mastery:</span
                                >
                                <div class="flex items-center gap-1">
                                    <span
                                        :class="[
                                            'h-2 w-2 rounded-full',
                                            masteryState[q.id]
                                                .consecutive_correct >= 1
                                                ? 'bg-emerald-500'
                                                : 'bg-zinc-300 dark:bg-zinc-700',
                                        ]"
                                    />
                                    <span
                                        :class="[
                                            'h-2 w-2 rounded-full',
                                            masteryState[q.id]
                                                .consecutive_correct >= 2
                                                ? 'bg-emerald-500'
                                                : 'bg-zinc-300 dark:bg-zinc-700',
                                        ]"
                                    />
                                </div>
                                <span
                                    v-if="masteryState[q.id].is_mastered"
                                    class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400"
                                >
                                    ✓ Mastered
                                </span>
                                <span
                                    v-else
                                    class="text-[10px] font-medium text-brand-muted"
                                >
                                    {{
                                        masteryState[q.id].consecutive_correct
                                    }}/2
                                </span>
                            </div>

                            <!-- Reveal Answer Action Button -->
                            <button
                                type="button"
                                @click="revealAnswer(q)"
                                :disabled="
                                    !!revealedAnswers[q.id] || isSubmitted
                                "
                                :class="[
                                    'inline-flex items-center gap-1.5 rounded-full px-3.5 py-1.5 text-xs font-semibold transition-all',
                                    revealedAnswers[q.id]
                                        ? 'cursor-default border border-emerald-500/30 bg-emerald-500/10 text-emerald-600 dark:text-emerald-400'
                                        : 'border border-amber-500/30 bg-amber-500/10 text-amber-700 shadow-2xs hover:bg-amber-500/20 active:scale-95 dark:text-amber-400',
                                ]"
                            >
                                <span
                                    v-if="isRevealing[q.id]"
                                    class="inline-block animate-spin"
                                    >⏳</span
                                >
                                <span v-else-if="revealedAnswers[q.id]"
                                    >✓ Answer Revealed</span
                                >
                                <span v-else>👁️ Reveal Answer</span>
                            </button>
                        </template>
                    </div>
                </div>

                <!-- Answer Revealed Notice Banner (Explaining mark forfeiture) -->
                <div
                    v-if="
                        forfeitedQuestions[q.id] &&
                        q.question_type !== 'short_answer'
                    "
                    class="flex items-center gap-2 rounded-2xl border border-amber-500/20 bg-amber-500/5 px-4 py-2.5 text-xs text-amber-700 dark:text-amber-400"
                >
                    <span>⚠️</span>
                    <span>
                        <strong>Answer revealed:</strong> This question is
                        marked as forfeited (0 points) and added to your
                        <strong>Revision & Weak MCQs</strong> workspace for
                        2-strike spaced mastery.
                    </span>
                </div>

                <!-- Short Answer Question Card -->
                <div
                    v-if="q.question_type === 'short_answer'"
                    class="flex flex-col gap-3"
                >
                    <div
                        v-if="showExplanations[q.id]"
                        class="rounded-2xl border border-emerald-500/20 bg-linear-to-br from-emerald-50/60 via-white to-sky-50/30 p-4 sm:p-5 dark:border-emerald-900/40 dark:from-emerald-950/20 dark:via-zinc-900/60 dark:to-sky-950/20"
                    >
                        <div
                            class="flex items-center gap-2 text-xs font-bold tracking-wide text-emerald-800 uppercase dark:text-emerald-300"
                        >
                            <span>✓</span>
                            <span>Model Answer / Criteria</span>
                        </div>
                        <p
                            class="mt-2 text-sm leading-relaxed whitespace-pre-line text-zinc-800 dark:text-zinc-100"
                        >
                            {{
                                q.payload?.model_answer ||
                                'No answer text provided.'
                            }}
                        </p>
                    </div>
                </div>

                <!-- MCQ Options List -->
                <div v-else class="flex flex-col gap-2.5">
                    <button
                        v-for="(option, idx) in q.payload?.options || []"
                        :key="idx"
                        type="button"
                        @click="selectOption(q, idx)"
                        :disabled="isSubmitted"
                        :class="[
                            'group flex items-center justify-between rounded-2xl border p-4 text-left text-sm transition-all duration-150',
                            getOptionClasses(q, idx),
                        ]"
                    >
                        <div class="flex items-center gap-3">
                            <span
                                :class="[
                                    'flex h-7 w-7 shrink-0 items-center justify-center rounded-xl text-xs font-bold transition-colors',
                                    getOptionCircleClasses(q, idx),
                                ]"
                            >
                                {{ String.fromCharCode(65 + idx) }}
                            </span>
                            <span>{{
                                typeof option === 'object'
                                    ? option.text || ''
                                    : option
                            }}</span>
                        </div>

                        <!-- Right badge: Correct vs Selected Indicator -->
                        <div
                            v-if="getOptionBadge(q, idx)"
                            class="flex items-center gap-2"
                        >
                            <span
                                v-if="getOptionBadge(q, idx).type === 'success'"
                                class="rounded-full bg-emerald-500/20 px-2.5 py-0.5 text-[11px] font-bold text-emerald-700 dark:text-emerald-300"
                            >
                                {{ getOptionBadge(q, idx).label }}
                            </span>
                            <span
                                v-else-if="
                                    getOptionBadge(q, idx).type === 'danger'
                                "
                                class="rounded-full bg-destructive/20 px-2.5 py-0.5 text-[11px] font-bold text-destructive"
                            >
                                {{ getOptionBadge(q, idx).label }}
                            </span>
                            <div
                                v-else-if="
                                    getOptionBadge(q, idx).type === 'dot'
                                "
                                class="h-3 w-3 rounded-full bg-sky-500"
                            />
                        </div>
                    </button>
                </div>

                <!-- Solution Explanation Toggle & Card -->
                <div v-if="canViewExplanation(q.id)" class="mt-1">
                    <button
                        type="button"
                        @click="toggleExplanation(q.id)"
                        class="inline-flex cursor-pointer items-center gap-1.5 text-xs font-semibold text-primary hover:underline"
                    >
                        <span>💡</span>
                        {{
                            showExplanations[q.id]
                                ? 'Hide Solution Explanation'
                                : 'View Solution Explanation'
                        }}
                    </button>

                    <div
                        v-if="showExplanations[q.id]"
                        class="text-senselab-heading shadow-senselab-card mt-3 rounded-2xl border border-sky-200/80 bg-sky-50/60 p-5 text-xs leading-relaxed dark:border-sky-900/50 dark:bg-sky-950/30"
                    >
                        <div
                            class="mb-1.5 flex items-center gap-2 font-bold text-sky-700 dark:text-sky-300"
                        >
                            <span>📘</span>
                            <span>Solution & Academic Insights:</span>
                        </div>
                        <div
                            v-if="revealedAnswers[q.id]?.correct_option_text"
                            class="mb-2 inline-flex items-center gap-2 rounded-lg bg-emerald-500/10 px-3 py-1 font-semibold text-emerald-700 dark:text-emerald-400"
                        >
                            <span>Correct Choice:</span>
                            <strong
                                >Option
                                {{
                                    revealedAnswers[q.id].correct_option_letter
                                }})
                                {{
                                    revealedAnswers[q.id].correct_option_text
                                }}</strong
                            >
                        </div>

                        <p
                            class="leading-relaxed whitespace-pre-line text-brand-body"
                        >
                            {{
                                revealedAnswers[q.id]?.explanation ||
                                serverResults[q.id]?.explanation ||
                                q.explanation ||
                                q.payload?.explanation ||
                                'The correct option has been verified against the official syllabus curriculum.'
                            }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bottom Action Bar / Sticky Pagination Footer -->
        <div
            class="shadow-senselab-elevated sticky bottom-4 z-30 mt-4 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-zinc-200/90 bg-white/95 p-4 backdrop-blur-md dark:border-zinc-800 dark:bg-zinc-950/95"
        >
            <!-- Page View Bottom Bar: Both Previous / Next Navigation AND Submit CTA -->
            <template v-if="viewMode === 'page'">
                <!-- Left: Previous Page & Next Page Primary Navigation (Tactile Buttons) -->
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        @click="setPage(currentPage - 1)"
                        :disabled="currentPage === 1"
                        class="inline-flex cursor-pointer items-center gap-1.5 rounded-full border border-zinc-200/90 bg-white px-4 py-2 text-xs font-bold text-zinc-800 shadow-xs transition-all hover:border-zinc-300 hover:bg-zinc-50 active:scale-95 disabled:cursor-not-allowed disabled:opacity-40 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200"
                    >
                        <span>←</span>
                        <span>Previous Page</span>
                    </button>
                    <button
                        type="button"
                        @click="setPage(currentPage + 1)"
                        :disabled="currentPage >= totalPages"
                        class="inline-flex cursor-pointer items-center gap-1.5 rounded-full border border-zinc-200/90 bg-white px-4 py-2 text-xs font-bold text-zinc-800 shadow-xs transition-all hover:border-zinc-300 hover:bg-zinc-50 active:scale-95 disabled:cursor-not-allowed disabled:opacity-40 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200"
                    >
                        <span>Next Page</span>
                        <span>→</span>
                    </button>
                </div>

                <!-- Center: Page Status & Answered Count -->
                <div
                    class="flex items-center gap-3 text-xs font-medium text-brand-muted"
                >
                    <div class="flex items-center gap-1.5">
                        <span>Page</span>
                        <span
                            class="rounded-lg bg-sky-500/10 px-2.5 py-0.5 font-bold text-sky-600 dark:text-sky-400"
                        >
                            {{ currentPage }}
                        </span>
                        <span>of {{ totalPages }}</span>
                    </div>
                    <span>•</span>
                    <span
                        ><strong class="text-senselab-heading">{{
                            answeredCount
                        }}</strong>
                        of {{ questions.length }} Answered</span
                    >
                    <span
                        v-if="currentPage < totalPages"
                        class="hidden cursor-pointer text-xs font-semibold text-sky-600 hover:underline sm:inline dark:text-sky-400"
                        @click="setPage(currentPage + 1)"
                    >
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
                        class="inline-flex cursor-pointer items-center gap-1.5 rounded-full border border-zinc-200/90 bg-white px-4 py-2 text-xs font-bold text-zinc-800 shadow-xs transition-all hover:border-zinc-300 hover:bg-zinc-50 active:scale-95 disabled:cursor-not-allowed disabled:opacity-40 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200"
                    >
                        <span>←</span>
                        <span>Previous Question</span>
                    </button>
                    <button
                        v-if="currentQuestionIndex < questions.length - 1"
                        type="button"
                        @click="nextQuestion"
                        class="inline-flex cursor-pointer items-center gap-1.5 rounded-full px-4 py-2 text-xs font-bold text-white shadow-xs transition-all duration-200 hover:scale-[1.02] hover:shadow-md active:scale-95"
                        style="background: var(--brand-gradient)"
                    >
                        <span>Next Question</span>
                        <span>→</span>
                    </button>
                </div>

                <div class="text-xs font-medium text-brand-muted">
                    Question
                    <span class="text-senselab-heading font-bold">{{
                        currentQuestionIndex + 1
                    }}</span>
                    of
                    <span class="text-senselab-heading font-bold">{{
                        questions.length
                    }}</span>
                    •
                    <span class="text-senselab-heading font-semibold">{{
                        answeredCount
                    }}</span>
                    Answered
                </div>
            </template>

            <!-- Final Submit Test CTA (With generous right spacing to clear floating elements) -->
            <div class="flex items-center gap-2 sm:mr-3">
                <button
                    v-if="!isSubmitted"
                    type="button"
                    @click="submitTest"
                    :disabled="isSubmitting"
                    class="inline-flex cursor-pointer items-center gap-2 rounded-full px-6 py-2.5 text-xs font-bold text-white shadow-sm transition-all duration-200 hover:scale-[1.02] hover:shadow-md active:scale-95 disabled:opacity-50"
                    style="background: var(--brand-gradient)"
                >
                    <span>{{
                        isSubmitting ? 'Evaluating...' : 'Submit Test Session ✓'
                    }}</span>
                </button>
                <div
                    v-else
                    class="inline-flex items-center gap-1.5 rounded-full bg-emerald-500/10 px-4 py-2 text-xs font-bold text-emerald-600 dark:text-emerald-400"
                >
                    <span>✓ Session Complete</span>
                </div>
            </div>
        </div>
    </div>
</template>
