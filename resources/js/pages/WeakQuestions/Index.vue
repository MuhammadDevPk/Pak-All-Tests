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
    RotateCcw,
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
            item.domain_name.toLowerCase() === selectedSubjectFilter.value.toLowerCase();

        const matchesSearch =
            !searchQuery.value.trim() ||
            item.question_text.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            (item.code && item.code.toLowerCase().includes(searchQuery.value.toLowerCase()));

        return matchesSubject && matchesSearch;
    });
});

const masteryRate = computed(() => {
    const total = currentSummary.value.total_tracked;
    if (!total) return 0;
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
            alert(data.message || 'No active weak questions found for this selection.');
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
            <div v-if="isPracticing && practiceModule" class="flex flex-col gap-4">
                <div class="flex items-center justify-between border-b border-sidebar-border/70 pb-3">
                    <button
                        type="button"
                        @click="exitPractice"
                        class="inline-flex items-center gap-2 rounded-xl border border-sidebar-border bg-sidebar px-3.5 py-2 text-xs font-semibold text-foreground transition-all hover:bg-sidebar-accent"
                    >
                        <ArrowLeft class="h-4 w-4" />
                        Back to Weak MCQs Overview
                    </button>

                    <div class="flex items-center gap-2 text-xs text-muted-foreground">
                        <span class="inline-block h-2 w-2 rounded-full bg-emerald-500 animate-pulse" />
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
                <!-- Hero Header -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-sidebar-border/70 pb-6">
                    <div>
                        <div class="flex items-center gap-2.5">
                            <h1 class="text-2xl font-bold tracking-tight text-foreground">
                                Revision & Weak MCQs
                            </h1>
                            <span class="inline-flex items-center gap-1 rounded-full border border-primary/20 bg-primary/10 px-2.5 py-0.5 text-xs font-bold text-primary">
                                <Sparkles class="h-3 w-3" />
                                2-Strike Threshold Mastery
                            </span>
                        </div>
                        <p class="mt-1 text-xs sm:text-sm text-muted-foreground">
                            Targeted spaced repetition. Every question where an answer was revealed or incorrectly chosen is isolated here until you answer correctly 2 consecutive times.
                        </p>
                    </div>

                    <!-- Global Practice Launch Button -->
                    <div class="flex items-center gap-3">
                        <button
                            type="button"
                            @click="startPractice(null)"
                            :disabled="isLoadingPractice || currentSummary.total_active === 0"
                            class="inline-flex items-center justify-center gap-2 rounded-xl bg-primary px-5 py-2.5 text-xs sm:text-sm font-bold text-primary-foreground shadow-sm transition-all hover:bg-primary/90 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <Play v-if="!isLoadingPractice" class="h-4 w-4 fill-current" />
                            <span v-else class="inline-block h-4 w-4 animate-spin rounded-full border-2 border-primary-foreground border-t-transparent" />
                            <span>Practice All Weak MCQs ({{ currentSummary.total_active }})</span>
                        </button>
                    </div>
                </div>

                <!-- 2-Strike Threshold Mastery Protocol Explainer -->
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 rounded-2xl border border-primary/20 bg-primary/5 p-4.5 text-xs leading-relaxed text-foreground">
                    <div class="flex items-start gap-3">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary">
                            <GraduationCap class="h-5 w-5" />
                        </div>
                        <div>
                            <div class="font-bold text-foreground">Military & Academic Rigor: How Threshold Mastery Works</div>
                            <div class="text-muted-foreground mt-0.5">
                                If you click <strong>Reveal Answer</strong> or submit an incorrect option, the question enters this revision bank. To graduate to <strong>Mastered</strong>, you must answer it correctly <strong>2 consecutive times</strong> without revealing the solution.
                            </div>
                        </div>
                    </div>
                    <div class="flex shrink-0 items-center gap-2 text-xs font-semibold text-primary">
                        <span>● ○ 1/2 Progress</span>
                        <span>➔</span>
                        <span>● ● 2/2 Mastered</span>
                    </div>
                </div>

                <!-- Metric Cards Grid -->
                <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                    <!-- Active Weak Questions -->
                    <div class="flex flex-col justify-between rounded-2xl border border-sidebar-border/70 bg-card p-5 shadow-xs">
                        <div class="flex items-center justify-between text-muted-foreground">
                            <span class="text-xs font-semibold uppercase tracking-wider">Active Weak MCQs</span>
                            <AlertCircle class="h-4 w-4 text-amber-500" />
                        </div>
                        <div class="mt-3 flex items-baseline gap-2">
                            <span class="text-3xl font-bold tracking-tight text-foreground">
                                {{ currentSummary.total_active }}
                            </span>
                            <span class="text-xs font-semibold text-amber-600 dark:text-amber-400">
                                Requires Revision
                            </span>
                        </div>
                    </div>

                    <!-- Mastered Questions -->
                    <div class="flex flex-col justify-between rounded-2xl border border-sidebar-border/70 bg-card p-5 shadow-xs">
                        <div class="flex items-center justify-between text-muted-foreground">
                            <span class="text-xs font-semibold uppercase tracking-wider">Mastered Questions</span>
                            <CheckCircle2 class="h-4 w-4 text-emerald-500" />
                        </div>
                        <div class="mt-3 flex items-baseline gap-2">
                            <span class="text-3xl font-bold tracking-tight text-foreground">
                                {{ currentSummary.total_mastered }}
                            </span>
                            <span class="text-xs font-semibold text-emerald-600 dark:text-emerald-400">
                                Graduated
                            </span>
                        </div>
                    </div>

                    <!-- Total Identified Weaknesses -->
                    <div class="flex flex-col justify-between rounded-2xl border border-sidebar-border/70 bg-card p-5 shadow-xs">
                        <div class="flex items-center justify-between text-muted-foreground">
                            <span class="text-xs font-semibold uppercase tracking-wider">Total Identified</span>
                            <Target class="h-4 w-4 text-primary" />
                        </div>
                        <div class="mt-3 flex items-baseline gap-2">
                            <span class="text-3xl font-bold tracking-tight text-foreground">
                                {{ currentSummary.total_tracked }}
                            </span>
                            <span class="text-xs text-muted-foreground">
                                Tracked in History
                            </span>
                        </div>
                    </div>

                    <!-- Retention & Mastery Rate -->
                    <div class="flex flex-col justify-between rounded-2xl border border-sidebar-border/70 bg-card p-5 shadow-xs">
                        <div class="flex items-center justify-between text-muted-foreground">
                            <span class="text-xs font-semibold uppercase tracking-wider">Mastery Rate</span>
                            <Flame class="h-4 w-4 text-orange-500" />
                        </div>
                        <div class="mt-3 flex items-baseline gap-2">
                            <span class="text-3xl font-bold tracking-tight text-foreground">
                                {{ masteryRate }}%
                            </span>
                            <span class="text-xs text-muted-foreground">
                                Conversion
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Subject Breakdown Pills & Quick Subject Practice -->
                <div v-if="Object.keys(currentSummary.by_subject || {}).length > 0" class="flex flex-col gap-3">
                    <div class="flex items-center justify-between">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-muted-foreground">
                            Weak Questions by Subject
                        </h3>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                        <div
                            v-for="(count, subject) in currentSummary.by_subject"
                            :key="subject"
                            class="flex items-center justify-between rounded-xl border border-sidebar-border/70 bg-card p-4 hover:border-primary/50 transition-colors"
                        >
                            <div>
                                <h4 class="text-sm font-bold text-foreground">{{ subject }}</h4>
                                <p class="text-xs text-muted-foreground mt-0.5">
                                    {{ count }} {{ count === 1 ? 'Question' : 'Questions' }} need practice
                                </p>
                            </div>
                            <button
                                type="button"
                                @click="selectedSubjectFilter = String(subject)"
                                class="rounded-lg border border-sidebar-border bg-sidebar px-3 py-1.5 text-xs font-semibold text-foreground hover:bg-sidebar-accent transition-colors"
                            >
                                Filter Subject
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Active Weak Questions Directory -->
                <div class="flex flex-col gap-4 rounded-2xl border border-sidebar-border/70 bg-card p-5 shadow-xs">
                    <!-- Table Toolbar -->
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-sidebar-border/50 pb-4">
                        <div class="flex items-center gap-2">
                            <h3 class="text-base font-bold text-foreground">
                                Question Bank Drilldown
                            </h3>
                            <span class="rounded-full bg-sidebar px-2.5 py-0.5 text-xs font-semibold text-muted-foreground">
                                {{ filteredItems.length }}
                            </span>
                        </div>

                        <!-- Filter & Search Controls -->
                        <div class="flex flex-wrap items-center gap-2.5">
                            <!-- Subject Filter -->
                            <div class="flex items-center gap-1.5 text-xs">
                                <Filter class="h-3.5 w-3.5 text-muted-foreground" />
                                <select
                                    v-model="selectedSubjectFilter"
                                    class="rounded-lg border border-sidebar-border bg-background px-2.5 py-1.5 text-xs font-medium text-foreground focus:border-primary focus:outline-hidden"
                                >
                                    <option value="all">All Subjects</option>
                                    <option
                                        v-for="(count, subj) in currentSummary.by_subject"
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
                                class="w-48 sm:w-64 rounded-lg border border-sidebar-border bg-background px-3 py-1.5 text-xs text-foreground placeholder:text-muted-foreground focus:border-primary focus:outline-hidden"
                            />
                        </div>
                    </div>

                    <!-- Questions Stack -->
                    <div v-if="filteredItems.length > 0" class="flex flex-col divide-y divide-sidebar-border/50">
                        <div
                            v-for="(item, idx) in filteredItems"
                            :key="item.id"
                            class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 py-4 first:pt-1 last:pb-1"
                        >
                            <!-- Question Info -->
                            <div class="flex items-start gap-3 flex-1">
                                <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-md bg-sidebar text-[11px] font-bold text-muted-foreground">
                                    {{ idx + 1 }}
                                </span>
                                <div class="flex flex-col gap-1">
                                    <p class="text-sm font-medium leading-snug text-foreground">
                                        {{ item.question_text }}
                                    </p>
                                    <div class="flex flex-wrap items-center gap-2 text-[11px] text-muted-foreground">
                                        <span class="rounded-md border border-sidebar-border/70 bg-sidebar/50 px-2 py-0.5 font-medium text-foreground">
                                            {{ item.domain_name }}
                                        </span>
                                        <span v-if="item.code" class="font-mono text-muted-foreground">
                                            #{{ item.code }}
                                        </span>
                                        <span v-if="item.last_practiced_at">
                                            • Last practiced {{ item.last_practiced_at }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Mastery Progress & Telemetry Badges -->
                            <div class="flex items-center gap-3 shrink-0 self-end sm:self-center">
                                <!-- Telemetry (Revealed & Failed counts) -->
                                <div class="flex items-center gap-1.5 text-xs text-muted-foreground">
                                    <span
                                        v-if="item.revealed_count > 0"
                                        class="inline-flex items-center gap-1 rounded-md bg-amber-500/10 px-2 py-0.5 text-[11px] font-semibold text-amber-700 dark:text-amber-400"
                                        title="Times answer was revealed"
                                    >
                                        <Eye class="h-3 w-3" />
                                        {{ item.revealed_count }}
                                    </span>
                                    <span
                                        v-if="item.failed_count > 0"
                                        class="inline-flex items-center gap-1 rounded-md bg-destructive/10 px-2 py-0.5 text-[11px] font-semibold text-destructive"
                                        title="Times incorrectly answered"
                                    >
                                        <XCircle class="h-3 w-3" />
                                        {{ item.failed_count }}
                                    </span>
                                </div>

                                <!-- Mastery Pips -->
                                <div class="flex items-center gap-1.5 rounded-lg border border-sidebar-border bg-sidebar/50 px-2.5 py-1 text-xs">
                                    <span class="text-[11px] text-muted-foreground">Mastery:</span>
                                    <div class="flex items-center gap-1">
                                        <span
                                            :class="[
                                                'h-2 w-2 rounded-full',
                                                item.consecutive_correct >= 1
                                                    ? 'bg-emerald-500'
                                                    : 'bg-sidebar-border',
                                            ]"
                                        />
                                        <span
                                            :class="[
                                                'h-2 w-2 rounded-full',
                                                item.consecutive_correct >= 2
                                                    ? 'bg-emerald-500'
                                                    : 'bg-sidebar-border',
                                            ]"
                                        />
                                    </div>
                                    <span class="text-[10px] font-bold text-foreground">
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
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 mb-3">
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
                            v-if="searchQuery || selectedSubjectFilter !== 'all'"
                            type="button"
                            @click="searchQuery = ''; selectedSubjectFilter = 'all'"
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
