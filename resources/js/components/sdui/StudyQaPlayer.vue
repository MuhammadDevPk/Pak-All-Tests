<script setup>
import { ref, computed, watch, onMounted } from 'vue';

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
});

const emit = defineEmits(['question-change', 'page-context-change']);

const searchQuery = ref('');
const expandedAnswers = ref({});
const activeQuestionId = ref(null);

const initExpandedAnswers = (questionsList) => {
    if (!questionsList || questionsList.length === 0) {
        return;
    }

    if (
        Object.keys(expandedAnswers.value).length === 0 &&
        questionsList.length <= 15
    ) {
        const initial = {};
        questionsList.forEach((q) => {
            initial[q.id] = true;
        });
        expandedAnswers.value = initial;
    }

    if (!activeQuestionId.value && questionsList.length > 0) {
        selectQuestion(questionsList[0]);
    }
};

onMounted(() => {
    initExpandedAnswers(props.questions);
});

const filteredQuestions = computed(() => {
    if (!props.questions || props.questions.length === 0) {
        return [];
    }

    const query = searchQuery.value.trim().toLowerCase();

    if (!query) {
        return props.questions;
    }

    return props.questions.filter((q) => {
        const text = (q.question_text || '').toLowerCase();
        const answer = (
            q.payload?.model_answer ||
            q.explanation ||
            ''
        ).toLowerCase();
        const code = (q.code || '').toLowerCase();

        return (
            text.includes(query) ||
            answer.includes(query) ||
            code.includes(query)
        );
    });
});

const isAllExpanded = computed(() => {
    if (filteredQuestions.value.length === 0) {
        return false;
    }

    return filteredQuestions.value.every((q) => !!expandedAnswers.value[q.id]);
});

const toggleExpandAll = () => {
    const nextState = !isAllExpanded.value;
    const updated = { ...expandedAnswers.value };
    filteredQuestions.value.forEach((q) => {
        updated[q.id] = nextState;
    });
    expandedAnswers.value = updated;
};

const toggleAnswer = (question) => {
    expandedAnswers.value[question.id] = !expandedAnswers.value[question.id];
    selectQuestion(question);
};

const selectQuestion = (question) => {
    if (!question) {
        return;
    }

    activeQuestionId.value = question.id;

    const answerText =
        question.payload?.model_answer || question.explanation || '';

    const pageContext = {
        view_mode: 'study_qa',
        total_questions: props.questions.length,
        active_question_number:
            props.questions.findIndex((q) => q.id === question.id) + 1,
        question_text: question.question_text,
        model_answer: answerText,
    };

    emit('question-change', {
        ...question,
        page_context: pageContext,
    });
    emit('page-context-change', pageContext);
};

watch(
    () => props.questions,
    (newQuestions) => {
        if (newQuestions && newQuestions.length > 0) {
            initExpandedAnswers(newQuestions);
        }
    },
    { immediate: true },
);
</script>

<template>
    <div class="flex flex-col gap-5">
        <!-- Top Toolbar: Module Overview, Search Input & Expand/Collapse Toggle -->
        <div
            class="shadow-senselab-card flex flex-col justify-between gap-4 rounded-3xl border border-zinc-200/90 bg-white/95 p-5 backdrop-blur-md sm:flex-row sm:items-center dark:border-zinc-800 dark:bg-zinc-950/90"
        >
            <div class="flex items-center gap-3">
                <span
                    class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-emerald-500/10 text-xl font-bold text-emerald-600 dark:bg-emerald-950/40 dark:text-emerald-400"
                >
                    📖
                </span>
                <div>
                    <h2
                        class="text-senselab-heading text-lg font-bold tracking-tight"
                    >
                        {{ module.title }}
                    </h2>
                    <p class="text-xs text-brand-muted">
                        Official criteria guidelines & verified reference cards
                        •
                        {{ questions.length }} items
                    </p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2.5 sm:justify-end">
                <!-- Live Search / Keyword Filter Input -->
                <div class="relative w-full sm:w-64">
                    <span
                        class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-xs text-zinc-400"
                    >
                        🔍
                    </span>
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Filter criteria or question..."
                        class="w-full rounded-full border border-zinc-200/90 bg-zinc-50/80 py-2 pr-3.5 pl-8 text-xs text-zinc-800 transition-all placeholder:text-zinc-400 focus:border-sky-500 focus:bg-white focus:ring-2 focus:ring-sky-500/20 focus:outline-hidden dark:border-zinc-800 dark:bg-zinc-900/80 dark:text-zinc-200 dark:focus:bg-zinc-900"
                    />
                    <button
                        v-if="searchQuery"
                        @click="searchQuery = ''"
                        type="button"
                        class="absolute inset-y-0 right-0 flex items-center pr-3 text-xs text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200"
                    >
                        ✕
                    </button>
                </div>

                <!-- Expand All / Collapse All Toggle Button -->
                <button
                    type="button"
                    @click="toggleExpandAll"
                    class="dark:hover:bg-zinc-850 inline-flex cursor-pointer items-center gap-1.5 rounded-full border border-zinc-200/90 bg-white px-3.5 py-2 text-xs font-semibold text-zinc-700 shadow-2xs transition-all hover:border-zinc-300 hover:bg-zinc-50 active:scale-95 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-300"
                >
                    <span>{{ isAllExpanded ? '📂' : '📖' }}</span>
                    <span>{{
                        isAllExpanded ? 'Collapse All' : 'Expand All'
                    }}</span>
                </button>
            </div>
        </div>

        <!-- Empty Filter Result State -->
        <div
            v-if="filteredQuestions.length === 0"
            class="shadow-senselab-card flex flex-col items-center justify-center gap-3 rounded-3xl border border-zinc-200/80 bg-white/80 p-12 text-center backdrop-blur-md dark:border-zinc-800 dark:bg-zinc-950/80"
        >
            <span class="text-3xl">🔍</span>
            <h3 class="text-senselab-heading text-base font-bold">
                {{
                    searchQuery
                        ? 'No matching guidelines or questions'
                        : 'No study content found'
                }}
            </h3>
            <p class="max-w-md text-xs text-brand-muted">
                {{
                    searchQuery
                        ? `No items match your search term "${searchQuery}". Clear the filter to view all study items.`
                        : 'No study guidelines or questions have been attached to this module yet.'
                }}
            </p>
            <button
                v-if="searchQuery"
                type="button"
                @click="searchQuery = ''"
                class="mt-2 rounded-full border border-zinc-200/90 bg-zinc-50 px-4 py-1.5 text-xs font-semibold text-zinc-700 hover:bg-zinc-100 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300"
            >
                Clear Search
            </button>
        </div>

        <!-- Question Cards Deck -->
        <div v-else class="flex flex-col gap-4">
            <div
                v-for="(q, index) in filteredQuestions"
                :key="q.id"
                @click="selectQuestion(q)"
                :class="[
                    'group shadow-senselab-card rounded-3xl border bg-white/95 p-5 backdrop-blur-md transition-all duration-200 sm:p-6 dark:bg-zinc-950/90',
                    activeQuestionId === q.id
                        ? 'border-sky-500/80 shadow-md ring-2 ring-sky-500/20 dark:border-sky-400/80'
                        : 'border-zinc-200/90 hover:border-zinc-300 dark:border-zinc-800 dark:hover:border-zinc-700',
                ]"
            >
                <!-- Card Header: Index, Question / Topic Title, and Show/Hide Toggle Button -->
                <div
                    class="flex flex-col justify-between gap-3 sm:flex-row sm:items-start"
                >
                    <div class="flex flex-1 items-start gap-3.5">
                        <span
                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-sky-500/10 text-xs font-extrabold text-sky-600 dark:text-sky-400"
                        >
                            {{ index + 1 }}
                        </span>
                        <div class="flex-1">
                            <h3
                                class="text-senselab-heading text-base leading-relaxed font-bold"
                            >
                                {{ q.question_text }}
                            </h3>
                            <div class="mt-1 flex items-center gap-2">
                                <span
                                    v-if="q.code"
                                    class="text-[10px] font-semibold text-brand-muted uppercase"
                                >
                                    {{ q.code }}
                                </span>
                                <span
                                    class="inline-flex items-center rounded-md bg-zinc-100 px-2 py-0.5 text-[10px] font-medium text-zinc-600 dark:bg-zinc-800/80 dark:text-zinc-400"
                                >
                                    Official Syllabus Criteria
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Right Side Show / Hide Toggle Button -->
                    <button
                        type="button"
                        @click.stop="toggleAnswer(q)"
                        :class="[
                            'inline-flex cursor-pointer items-center gap-1.5 self-start rounded-full px-3.5 py-1.5 text-xs font-semibold shadow-2xs transition-all active:scale-95',
                            expandedAnswers[q.id]
                                ? 'border border-emerald-500/30 bg-emerald-500/10 text-emerald-700 dark:text-emerald-400'
                                : 'border border-zinc-200/90 bg-white text-zinc-700 hover:border-zinc-300 hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200',
                        ]"
                    >
                        <span>{{ expandedAnswers[q.id] ? '✓' : '👁️' }}</span>
                        <span>{{
                            expandedAnswers[q.id]
                                ? 'Hide Answer'
                                : 'Show Answer'
                        }}</span>
                    </button>
                </div>

                <!-- Revealed Model Answer Content Area -->
                <transition
                    enter-active-class="transition duration-200 ease-out"
                    enter-from-class="transform opacity-0 -translate-y-1"
                    enter-to-class="transform opacity-100 translate-y-0"
                    leave-active-class="transition duration-150 ease-in"
                    leave-from-class="transform opacity-100 translate-y-0"
                    leave-to-class="transform opacity-0 -translate-y-1"
                >
                    <div
                        v-if="expandedAnswers[q.id]"
                        class="mt-4 flex flex-col gap-3 rounded-2xl border border-emerald-500/20 bg-linear-to-br from-emerald-50/60 via-white to-sky-50/30 p-4 sm:p-5 dark:border-emerald-900/40 dark:from-emerald-950/20 dark:via-zinc-900/60 dark:to-sky-950/20"
                    >
                        <div class="flex items-center gap-2">
                            <span
                                class="text-xs text-emerald-600 dark:text-emerald-400"
                                >✓</span
                            >
                            <span
                                class="text-[11px] font-bold tracking-wide text-emerald-800 uppercase dark:text-emerald-300"
                            >
                                Official Standard / Verified Answer
                            </span>
                        </div>

                        <!-- Multi-line answer preserving whitespace and paragraphs -->
                        <div
                            class="text-senselab-heading text-sm leading-relaxed whitespace-pre-line text-zinc-800 dark:text-zinc-100"
                        >
                            {{
                                q.payload?.model_answer ||
                                q.explanation ||
                                'No specific answer text is required for this guideline.'
                            }}
                        </div>

                        <!-- Additional Explanation / Context Note (if present and distinct from answer) -->
                        <div
                            v-if="
                                q.explanation &&
                                q.payload?.model_answer &&
                                q.explanation !== q.payload?.model_answer
                            "
                            class="mt-1 border-t border-emerald-500/10 pt-2 text-xs text-brand-muted"
                        >
                            <strong
                                class="font-semibold text-zinc-700 dark:text-zinc-300"
                                >Preparation Context:</strong
                            >
                            {{ q.explanation }}
                        </div>
                    </div>
                </transition>
            </div>
        </div>
    </div>
</template>
