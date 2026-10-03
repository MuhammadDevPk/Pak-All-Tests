<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import {
    AlertCircle,
    ArrowLeft,
    CheckCircle2,
    Eye,
    Filter,
    Flame,
    GraduationCap,
    Play,
    Sparkles,
    Target,
    XCircle,
} from '@lucide/vue';
import { computed, defineAsyncComponent, ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';

interface WeakQuestionItem {
    id: number;
    question_id: number;
    domain_id: number | null;
    domain_name: string;
    question_text: string;
    code: string | null;
    consecutive_correct: number;
    revealed_count: number;
    failed_count: number;
    last_practiced_at: string | null;
    status: 'active' | 'mastered';
}

interface WeakSummary {
    total_active: number;
    total_mastered: number;
    total_tracked: number;
    by_subject: Record<string, number>;
}

const props = defineProps<{
    summary: WeakSummary;
    items: WeakQuestionItem[];
    selectedDomainId?: number | null;
}>();

const McqPlayer = defineAsyncComponent(
    () => import('@/components/sdui/McqPlayer.vue'),
);

// State for active practice session
const isPracticing = ref(false);
const isLoadingPractice = ref(false);
const practiceModule = ref<any>(null);
const practiceQuestions = ref<any[]>([]);
const practiceFilterDomain = ref<number | null>(props.selectedDomainId || null);

// Search & filter state
const searchQuery = ref('');
const selectedSubjectFilter = ref<string>('all');

const currentSummary = ref<WeakSummary>(props.summary);
const currentItems = ref<WeakQuestionItem[]>(props.items);

const filteredItems = computed(() => {
    return currentItems.value.filter((item) => {
        const matchesSubject =
            selectedSubjectFilter.value === 'all' ||
            item.domain_name.toLowerCase() ===
                selectedSubjectFilter.value.toLowerCase();

        const matchesSearch =
            !searchQuery.value.trim() ||
            item.question_text
                .toLowerCase()
                .includes(searchQuery.value.toLowerCase()) ||
            (item.code &&
                item.code
                    .toLowerCase()
                    .includes(searchQuery.value.toLowerCase()));

        return matchesSubject && matchesSearch;
    });
});

const masteryRate = computed(() => {
    const total = currentSummary.value.total_tracked;

    if (!total) {
        return 0;
    }

    return Math.round((currentSummary.value.total_mastered / total) * 100);
});

const refreshData = async () => {
    try {
        const res = await fetch('/api/weak-questions', {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (res.ok) {
            const data = await res.json();

            if (data.status === 'success') {
                currentSummary.value = data.summary;
                currentItems.value = data.items;
            }
        }
    } catch (e) {
        console.warn('Could not refresh weak questions:', e);
    }
};

const startPractice = async (domainId?: number | null) => {
    isLoadingPractice.value = true;
    practiceFilterDomain.value = domainId || null;

    try {
        const url = domainId
            ? `/api/weak-questions/practice?domain_id=${domainId}`
            : '/api/weak-questions/practice';

        const res = await fetch(url, {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        const data = await res.json();

        if (res.ok && data.status === 'success') {
            practiceModule.value = data.module;
            practiceQuestions.value = data.questions || [];
            isPracticing.value = true;
        } else {
            alert(
                data.message ||
                    'No active weak questions found for this selection.',
            );
        }
    } catch (e) {
        console.error('Error starting weak questions practice:', e);
        alert('Network error launching practice session. Please try again.');
    } finally {
        isLoadingPractice.value = false;
    }
};

const exitPractice = async () => {
    isPracticing.value = false;
    practiceModule.value = null;
    practiceQuestions.value = [];
    await refreshData();
};
</script>

<template>
    <AppLayout
        :breadcrumbs="[
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Revision & Weak MCQs', href: '/revision-weak-questions' },
        ]"
    >
        <Head title="Revision & Weak MCQs - 2-Strike Threshold Mastery" />

        <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-8">
            <!-- Active Practice Mode Player View -->
            <div
                v-if="isPracticing && practiceModule"
                class="flex flex-col gap-4"
            >
                <div
                    class="flex items-center justify-between border-b border-sidebar-border/70 pb-3"
                >
                    <button
                        type="button"
                        @click="exitPractice"
                        class="inline-flex items-center gap-2 rounded-xl border border-sidebar-border bg-sidebar px-3.5 py-2 text-xs font-semibold text-foreground transition-all hover:bg-sidebar-accent"
                    >
                        <ArrowLeft class="h-4 w-4" />
                        Back to Weak MCQs Overview
                    </button>

                    <div
                        class="flex items-center gap-2 text-xs text-muted-foreground"
                    >
                        <span
                            class="inline-block h-2 w-2 animate-pulse rounded-full bg-emerald-500"
                        />
                        Spaced Revision Active Session
                    </div>
                </div>

                <McqPlayer
                    :module="practiceModule"
                    :questions="practiceQuestions"
                    mode="practice"
                />
            </div>

            <!-- Weak MCQs Dashboard & Revision Center -->
            <template v-else>
                <!-- Hero Header with Ambient SenseLab Aura -->
                <div
                    class="shadow-senselab-card relative flex flex-col justify-between gap-5 rounded-3xl border border-zinc-200/80 bg-white/80 p-6 backdrop-blur-md sm:flex-row sm:items-center sm:p-8 dark:border-zinc-800/80 dark:bg-zinc-950/80"
                >
                    <div>
                        <div class="flex flex-wrap items-center gap-2.5">
                            <h1
                                class="text-senselab-heading text-2xl font-extrabold tracking-tight sm:text-3xl"
                            >
                                Revision & Weak MCQs
                            </h1>
                            <span
                                class="inline-flex items-center gap-1.5 rounded-full border border-sky-200/90 bg-sky-50/80 px-3 py-1 text-xs font-semibold text-sky-800 shadow-2xs dark:border-sky-900/60 dark:bg-sky-950/40 dark:text-sky-300"
                            >
                                <Sparkles class="h-3.5 w-3.5 text-sky-500" />
                                2-Strike Threshold Mastery
                            </span>
                        </div>
                        <p
                            class="mt-2 max-w-2xl text-xs leading-relaxed text-brand-muted sm:text-sm"
                        >
                            Targeted spaced repetition. Every question where an
                            answer was revealed or incorrectly chosen is
                            isolated here until you answer correctly 2
                            consecutive times without assistance.
                        </p>
                    </div>

                    <!-- Global Practice Launch Button -->
                    <div class="flex shrink-0 items-center gap-3">
                        <button
                            type="button"
                            @click="startPractice(null)"
                            :disabled="
                                isLoadingPractice ||
                                currentSummary.total_active === 0
                            "
                            class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-full px-6 py-3 text-xs font-bold text-white shadow-sm transition-all duration-200 hover:scale-[1.02] hover:shadow-md active:scale-95 disabled:cursor-not-allowed disabled:opacity-50 sm:text-sm"
                            style="background: var(--brand-gradient)"
                        >
                            <Play
                                v-if="!isLoadingPractice"
                                class="h-4 w-4 fill-current"
                            />
                            <span
                                v-else
                                class="inline-block h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"
                            />
                            <span
                                >Practice All Weak MCQs ({{
                                    currentSummary.total_active
                                }})</span
                            >
                            <span
                                class="transition-transform duration-200 group-hover:translate-x-0.5"
                                >➔</span
                            >
                        </button>
                    </div>
                </div>

                <!-- 2-Strike Threshold Mastery Protocol Explainer -->
                <div
                    class="text-senselab-heading shadow-senselab-card flex flex-col items-start justify-between gap-4 rounded-3xl border border-zinc-200/90 bg-white/90 p-5 text-xs leading-relaxed backdrop-blur-md sm:flex-row sm:items-center dark:border-zinc-800 dark:bg-zinc-950/80"
                >
                    <div class="flex items-start gap-3.5">
                        <div
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-sky-500/10 text-sky-600 dark:text-sky-400"
                        >
                            <GraduationCap class="h-5 w-5" />
                        </div>
                        <div>
                            <div class="text-senselab-heading font-bold">
                                Military & Academic Rigor: How Threshold Mastery
                                Works
                            </div>
                            <div class="mt-0.5 text-brand-muted">
                                If you click <strong>Reveal Answer</strong> or
                                submit an incorrect option, the question enters
                                this revision bank. To graduate to
                                <strong>Mastered</strong>, you must answer it
                                correctly
                                <strong>2 consecutive times</strong> in
                                practice.
                            </div>
                        </div>
                    </div>
                    <div
                        class="text-senselab-heading flex shrink-0 items-center gap-2.5 rounded-full border border-zinc-200/80 bg-zinc-50 px-3.5 py-1.5 text-xs font-semibold dark:border-zinc-800 dark:bg-zinc-900"
                    >
                        <span class="text-amber-600 dark:text-amber-400"
                            >● ○ 1/2 Progress</span
                        >
                        <span class="text-zinc-400">➔</span>
                        <span class="text-emerald-600 dark:text-emerald-400"
                            >● ● 2/2 Mastered</span
                        >
                    </div>
                </div>

                <!-- SenseLab Metric Cards Grid (Matching Welcome.vue Aesthetic) -->
                <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                    <!-- 1. Active Weak Questions (Amber Accent) -->
                    <div
                        class="shadow-senselab-card flex flex-col justify-between rounded-3xl border border-amber-100 bg-amber-50/50 p-5 dark:border-amber-900/40 dark:bg-amber-950/20"
                    >
                        <div
                            class="flex items-center justify-between text-xs font-bold tracking-wider text-amber-700 uppercase dark:text-amber-400"
                        >
                            <span>Active Weak MCQs</span>
                            <div
                                class="flex h-7 w-7 items-center justify-center rounded-xl bg-amber-100 dark:bg-amber-900/60"
                            >
                                <AlertCircle
                                    class="h-4 w-4 text-amber-600 dark:text-amber-400"
                                />
                            </div>
                        </div>
                        <div class="mt-4 flex items-baseline gap-2">
                            <span
                                class="text-senselab-heading text-3xl font-extrabold tracking-tight sm:text-4xl"
                            >
                                {{ currentSummary.total_active }}
                            </span>
                            <span
                                class="text-xs font-bold text-amber-600 dark:text-amber-400"
                            >
                                Requires Revision
                            </span>
                        </div>
                        <div
                            class="mt-1 text-[11px] font-medium text-brand-muted"
                        >
                            pending graduation
                        </div>
                    </div>

                    <!-- 2. Mastered Questions (Emerald Accent) -->
                    <div
                        class="shadow-senselab-card flex flex-col justify-between rounded-3xl border border-emerald-100 bg-emerald-50/50 p-5 dark:border-emerald-900/40 dark:bg-emerald-950/20"
                    >
                        <div
                            class="flex items-center justify-between text-xs font-bold tracking-wider text-emerald-700 uppercase dark:text-emerald-400"
                        >
                            <span>Mastered Questions</span>
                            <div
                                class="flex h-7 w-7 items-center justify-center rounded-xl bg-emerald-100 dark:bg-emerald-900/60"
                            >
                                <CheckCircle2
                                    class="h-4 w-4 text-emerald-600 dark:text-emerald-400"
                                />
                            </div>
                        </div>
                        <div class="mt-4 flex items-baseline gap-2">
                            <span
                                class="text-3xl font-extrabold tracking-tight text-emerald-600 sm:text-4xl dark:text-emerald-400"
                            >
                                {{ currentSummary.total_mastered }}
                            </span>
                            <span
                                class="text-xs font-bold text-emerald-600 dark:text-emerald-400"
                            >
                                Graduated
                            </span>
                        </div>
                        <div
                            class="mt-1 text-[11px] font-medium text-brand-muted"
                        >
                            2 consecutive correct answers
                        </div>
                    </div>

                    <!-- 3. Total Identified Weaknesses (Sky Accent) -->
                    <div
                        class="shadow-senselab-card flex flex-col justify-between rounded-3xl border border-sky-100 bg-sky-50/50 p-5 dark:border-sky-900/40 dark:bg-sky-950/20"
                    >
                        <div
                            class="flex items-center justify-between text-xs font-bold tracking-wider text-sky-700 uppercase dark:text-sky-400"
                        >
                            <span>Total Identified</span>
                            <div
                                class="flex h-7 w-7 items-center justify-center rounded-xl bg-sky-100 dark:bg-sky-900/60"
                            >
                                <Target
                                    class="h-4 w-4 text-sky-600 dark:text-sky-400"
                                />
                            </div>
                        </div>
                        <div class="mt-4 flex items-baseline gap-2">
                            <span
                                class="text-senselab-heading text-3xl font-extrabold tracking-tight sm:text-4xl"
                            >
                                {{ currentSummary.total_tracked }}
                            </span>
                            <span class="text-xs font-medium text-brand-muted">
                                Tracked in History
                            </span>
                        </div>
                        <div
                            class="mt-1 text-[11px] font-medium text-brand-muted"
                        >
                            historical failure events
                        </div>
                    </div>

                    <!-- 4. Retention & Mastery Rate (Purple Accent) -->
                    <div
                        class="shadow-senselab-card flex flex-col justify-between rounded-3xl border border-purple-100 bg-purple-50/50 p-5 dark:border-purple-900/40 dark:bg-purple-950/20"
                    >
                        <div
                            class="flex items-center justify-between text-xs font-bold tracking-wider text-purple-700 uppercase dark:text-purple-400"
                        >
                            <span>Mastery Rate</span>
                            <div
                                class="flex h-7 w-7 items-center justify-center rounded-xl bg-purple-100 dark:bg-purple-900/60"
                            >
                                <Flame
                                    class="h-4 w-4 text-purple-600 dark:text-purple-400"
                                />
                            </div>
                        </div>
                        <div class="mt-4 flex items-baseline gap-2">
                            <span
                                class="text-senselab-heading text-3xl font-extrabold tracking-tight sm:text-4xl"
                            >
                                {{ masteryRate }}%
                            </span>
                            <span
                                class="text-xs font-semibold text-purple-600 dark:text-purple-400"
                            >
                                Conversion
                            </span>
                        </div>
                        <div
                            class="mt-1 text-[11px] font-medium text-brand-muted"
                        >
                            graduated from revision bank
                        </div>
                    </div>
                </div>

                <!-- Subject Breakdown Pills & Quick Subject Practice -->
                <div
                    v-if="
                        Object.keys(currentSummary.by_subject || {}).length > 0
                    "
                    class="flex flex-col gap-3"
                >
                    <div class="flex items-center justify-between">
                        <h3
                            class="text-xs font-bold tracking-wider text-brand-muted uppercase"
                        >
                            Weak Questions by Subject
                        </h3>
                    </div>
                    <div
                        class="grid grid-cols-1 gap-3.5 sm:grid-cols-2 lg:grid-cols-3"
                    >
                        <div
                            v-for="(
                                count, subject
                            ) in currentSummary.by_subject"
                            :key="subject"
                            class="shadow-senselab-card flex items-center justify-between rounded-2xl border border-zinc-200/90 bg-white/95 p-4.5 transition-all hover:border-sky-500/40 dark:border-zinc-800 dark:bg-zinc-950/90"
                        >
                            <div>
                                <h4
                                    class="text-senselab-heading text-sm font-bold"
                                >
                                    {{ subject }}
                                </h4>
                                <p class="mt-0.5 text-xs text-brand-muted">
                                    {{ count }}
                                    {{ count === 1 ? 'Question' : 'Questions' }}
                                    need practice
                                </p>
                            </div>
                            <button
                                type="button"
                                @click="selectedSubjectFilter = String(subject)"
                                class="rounded-full border border-zinc-200/90 bg-white px-3.5 py-1.5 text-xs font-semibold text-zinc-800 shadow-2xs hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200"
                            >
                                Filter Subject
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Active Weak Questions Directory -->
                <div
                    class="shadow-senselab-card flex flex-col gap-4 rounded-3xl border border-zinc-200/90 bg-white/95 p-6 dark:border-zinc-800 dark:bg-zinc-950/90"
                >
                    <!-- Table Toolbar -->
                    <div
                        class="flex flex-col justify-between gap-3 border-b border-zinc-100 pb-4 sm:flex-row sm:items-center dark:border-zinc-800/80"
                    >
                        <div class="flex items-center gap-2.5">
                            <h3
                                class="text-senselab-heading text-base font-bold"
                            >
                                Question Bank Drilldown
                            </h3>
                            <span
                                class="rounded-full bg-sky-500/10 px-2.5 py-0.5 text-xs font-bold text-sky-600 dark:text-sky-400"
                            >
                                {{ filteredItems.length }}
                            </span>
                        </div>

                        <!-- Filter & Search Controls -->
                        <div class="flex flex-wrap items-center gap-2.5">
                            <!-- Subject Filter -->
                            <div class="flex items-center gap-1.5 text-xs">
                                <Filter class="h-3.5 w-3.5 text-brand-muted" />
                                <select
                                    v-model="selectedSubjectFilter"
                                    class="rounded-xl border border-zinc-200/90 bg-white px-3 py-1.5 text-xs font-semibold text-zinc-800 shadow-2xs focus:border-sky-500 focus:outline-hidden dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-200"
                                >
                                    <option value="all">All Subjects</option>
                                    <option
                                        v-for="(
                                            count, subj
                                        ) in currentSummary.by_subject"
                                        :key="subj"
                                        :value="subj"
                                    >
                                        {{ subj }} ({{ count }})
                                    </option>
                                </select>
                            </div>

                            <!-- Search Input -->
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Search question text or code..."
                                class="text-senselab-heading w-48 rounded-xl border border-zinc-200/90 bg-white px-3.5 py-1.5 text-xs font-medium shadow-2xs placeholder:text-zinc-400 focus:border-sky-500 focus:outline-hidden sm:w-64 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-200"
                            />
                        </div>
                    </div>

                    <!-- Questions Stack as Distinct Elevated Tiles -->
                    <div
                        v-if="filteredItems.length > 0"
                        class="flex flex-col gap-3"
                    >
                        <div
                            v-for="(item, idx) in filteredItems"
                            :key="item.id"
                            class="hover:shadow-senselab-card flex flex-col justify-between gap-4 rounded-2xl border border-zinc-200/70 bg-zinc-50/40 p-4.5 shadow-2xs transition-all hover:border-zinc-300 hover:bg-white sm:flex-row sm:items-center dark:border-zinc-800/80 dark:bg-zinc-900/30 dark:hover:bg-zinc-900"
                        >
                            <!-- Question Info -->
                            <div class="flex flex-1 items-start gap-3.5">
                                <span
                                    class="flex h-7 w-7 shrink-0 items-center justify-center rounded-xl bg-sky-500/10 text-xs font-extrabold text-sky-600 dark:text-sky-400"
                                >
                                    {{ idx + 1 }}
                                </span>
                                <div class="flex flex-col gap-1.5">
                                    <p
                                        class="text-senselab-heading text-sm leading-snug font-semibold"
                                    >
                                        {{ item.question_text }}
                                    </p>
                                    <div
                                        class="flex flex-wrap items-center gap-2 text-[11px] text-brand-muted"
                                    >
                                        <span
                                            class="rounded-md border border-zinc-200/80 bg-white px-2 py-0.5 font-medium text-zinc-700 dark:border-zinc-800 dark:bg-zinc-800 dark:text-zinc-300"
                                        >
                                            {{ item.domain_name }}
                                        </span>
                                        <span
                                            v-if="item.code"
                                            class="font-mono text-brand-muted"
                                        >
                                            #{{ item.code }}
                                        </span>
                                        <span v-if="item.last_practiced_at">
                                            • Last practiced
                                            {{ item.last_practiced_at }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Mastery Progress & Telemetry Badges -->
                            <div
                                class="flex shrink-0 items-center gap-3 self-end sm:self-center"
                            >
                                <!-- Telemetry (Revealed & Failed counts) -->
                                <div
                                    class="flex items-center gap-1.5 text-xs text-brand-muted"
                                >
                                    <span
                                        v-if="item.revealed_count > 0"
                                        class="inline-flex items-center gap-1 rounded-full bg-amber-500/10 px-2.5 py-0.5 text-[11px] font-semibold text-amber-700 dark:text-amber-400"
                                        title="Times answer was revealed"
                                    >
                                        <Eye class="h-3 w-3" />
                                        {{ item.revealed_count }}
                                    </span>
                                    <span
                                        v-if="item.failed_count > 0"
                                        class="inline-flex items-center gap-1 rounded-full bg-destructive/10 px-2.5 py-0.5 text-[11px] font-semibold text-destructive"
                                        title="Times incorrectly answered"
                                    >
                                        <XCircle class="h-3 w-3" />
                                        {{ item.failed_count }}
                                    </span>
                                </div>

                                <!-- Mastery Pips -->
                                <div
                                    class="flex items-center gap-1.5 rounded-full border border-zinc-200/80 bg-white px-3 py-1 text-xs dark:border-zinc-800 dark:bg-zinc-900"
                                >
                                    <span
                                        class="text-[11px] font-medium text-brand-muted"
                                        >Mastery:</span
                                    >
                                    <div class="flex items-center gap-1">
                                        <span
                                            :class="[
                                                'h-2 w-2 rounded-full',
                                                item.consecutive_correct >= 1
                                                    ? 'bg-emerald-500'
                                                    : 'bg-zinc-300 dark:bg-zinc-700',
                                            ]"
                                        />
                                        <span
                                            :class="[
                                                'h-2 w-2 rounded-full',
                                                item.consecutive_correct >= 2
                                                    ? 'bg-emerald-500'
                                                    : 'bg-zinc-300 dark:bg-zinc-700',
                                            ]"
                                        />
                                    </div>
                                    <span
                                        class="text-senselab-heading text-[10px] font-bold"
                                    >
                                        {{ item.consecutive_correct }}/2
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Empty State -->
                    <div
                        v-else
                        class="flex flex-col items-center justify-center py-12 text-center text-muted-foreground"
                    >
                        <div
                            class="mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400"
                        >
                            <CheckCircle2 class="h-6 w-6" />
                        </div>
                        <h4 class="text-base font-bold text-foreground">
                            Zero Active Weak MCQs Found
                        </h4>
                        <p class="mt-1 max-w-sm text-xs leading-relaxed">
                            {{
                                searchQuery || selectedSubjectFilter !== 'all'
                                    ? 'No weak questions match your current filter. Try resetting search filters.'
                                    : 'Outstanding performance! You have mastered all identified questions or haven’t flagged any yet. Attempt a mock exam to challenge yourself.'
                            }}
                        </p>
                        <button
                            v-if="
                                searchQuery || selectedSubjectFilter !== 'all'
                            "
                            type="button"
                            @click="
                                searchQuery = '';
                                selectedSubjectFilter = 'all';
                            "
                            class="mt-4 rounded-xl border border-sidebar-border bg-sidebar px-3 py-1.5 text-xs font-semibold text-foreground hover:bg-sidebar-accent"
                        >
                            Reset Filters
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </AppLayout>
</template>
