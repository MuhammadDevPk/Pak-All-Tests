<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowRight, Sparkles, Target } from '@lucide/vue';
import PendingInvitationsModal from '@/components/PendingInvitationsModal.vue';
import { dashboard } from '@/routes';
import type { DashboardInvitation, Team } from '@/types';

defineProps<{
    pendingInvitations?: DashboardInvitation[];
}>();

defineOptions({
    layout: (props: { currentTeam?: Team | null }) => ({
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: props.currentTeam ? dashboard().url : '/',
            },
        ],
    }),
});

const examTracks = [
    {
        title: 'PMA Long Course Initial',
        category: 'Armed Forces & Defense',
        badge: 'Official Selection Track',
        slug: 'pma-initial-course',
        icon: '🎖️',
        description: 'Computerized intelligence and academic tests: Islamic Studies, Pak History, Geography, GK, and Current Affairs.',
        stat: '892 Verified MCQs',
        status: 'Active Track',
        href: '/d/pma-initial-course',
        themeBorder: 'hover:border-sky-500/50',
    },
    {
        title: 'Revision & Weak MCQs Bank',
        category: 'Adaptive Spaced Repetition',
        badge: 'Threshold Mastery',
        slug: null,
        icon: '🎯',
        description: 'Automated 2-strike spaced repetition isolating questions where answers were revealed or incorrectly attempted.',
        stat: 'Targeted Practice',
        status: 'Continuous',
        href: '/revision-weak-questions',
        themeBorder: 'hover:border-amber-500/50',
    },
    {
        title: 'Civil Services (CSS / PMS)',
        category: 'Competitive Examinations',
        badge: 'FPSC MPT Syllabus',
        slug: 'css-exam-2027',
        icon: '🏛️',
        description: 'Comprehensive general knowledge, Islamic studies, English comprehension, and national affairs dossiers.',
        stat: 'Compulsory Subjects',
        status: 'Syllabus Aligned',
        href: '/d/css-exam-2027',
        themeBorder: 'hover:border-purple-500/50',
    },
];
</script>

<template>
    <Head title="Candidate Dashboard — Pak-All-Tests" />

    <PendingInvitationsModal
        v-if="pendingInvitations && pendingInvitations.length > 0"
        :invitations="pendingInvitations"
    />

    <div class="relative flex h-full flex-1 flex-col gap-6 overflow-x-hidden p-4 sm:p-6 lg:p-8">
        <!-- Ambient Mesh Background Lighting -->
        <div class="pointer-events-none absolute inset-0 -z-10 overflow-hidden">
            <div class="absolute -top-32 -left-32 mesh-aura-cyan opacity-60"></div>
            <div class="absolute top-10 -right-32 mesh-aura-warm opacity-40"></div>
        </div>

        <!-- 1. Hero Welcome & Candidate Command Center Banner -->
        <div class="relative flex flex-col justify-between gap-6 rounded-3xl border border-zinc-200/90 bg-white/85 p-6 sm:p-8 shadow-senselab-card backdrop-blur-md dark:border-zinc-800/80 dark:bg-zinc-950/85">
            <div>
                <div class="flex flex-wrap items-center gap-2.5">
                    <div class="inline-flex items-center gap-2 rounded-full border border-sky-200/90 bg-sky-50/80 px-3.5 py-1 text-xs font-semibold text-sky-800 shadow-2xs dark:border-sky-900/60 dark:bg-sky-950/40 dark:text-sky-300">
                        <span class="flex h-2 w-2 rounded-full bg-sky-500 animate-pulse"></span>
                        <span>Candidate Command Center</span>
                        <span class="text-sky-300 dark:text-sky-700">•</span>
                        <span>Live Session Kernel</span>
                    </div>

                    <div class="inline-flex items-center gap-1.5 rounded-full border border-zinc-200/80 bg-zinc-100/70 px-3 py-1 text-xs font-medium text-zinc-700 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-300">
                        <Sparkles class="h-3.5 w-3.5 text-amber-500" />
                        <span>Socratic AI Mentor Available</span>
                    </div>
                </div>

                <h1 class="mt-4 text-3xl sm:text-4xl font-extrabold tracking-tight text-senselab-heading">
                    Prepare With Rigor.<br class="hidden sm:inline" />
                    Master Every Domain.
                </h1>

                <p class="mt-3 max-w-2xl text-sm sm:text-base leading-relaxed text-brand-muted">
                    Your institutional workspace for Pakistan's premier examinations. Engage with verified high-yield question banks, review weak spots with 2-strike spaced repetition, and consult your Socratic mentor.
                </p>
            </div>

            <!-- Quick Action Row -->
            <div class="flex flex-wrap items-center gap-3 pt-2">
                <Link
                    href="/d/pma-initial-course"
                    class="inline-flex items-center gap-2 rounded-full px-6 py-3 text-xs sm:text-sm font-semibold text-white shadow-sm transition-all duration-200 hover:shadow-md hover:scale-[1.02] active:scale-95 cursor-pointer"
                    style="background: var(--brand-gradient);"
                >
                    <span>Launch PMA Initial Course</span>
                    <ArrowRight class="h-4 w-4" />
                </Link>

                <Link
                    href="/revision-weak-questions"
                    class="inline-flex items-center gap-2 rounded-full border border-zinc-200/90 bg-white px-5 py-3 text-xs sm:text-sm font-semibold text-zinc-800 shadow-2xs hover:bg-zinc-50 active:scale-95 transition-all dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200"
                >
                    <Target class="h-4 w-4 text-amber-500" />
                    <span>Revision & Weak MCQs</span>
                </Link>
            </div>
        </div>

        <!-- 2. Flagship Exam Tracks Grid -->
        <div>
            <div class="mb-4 flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-bold text-senselab-heading">
                        Active Exam Tracks & Preparation Workspaces
                    </h2>
                    <p class="text-xs text-brand-muted">
                        Select an official curriculum track to begin practice sessions
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-5 md:grid-cols-2 lg:grid-cols-3">
                <div
                    v-for="track in examTracks"
                    :key="track.title"
                    class="group relative flex flex-col justify-between rounded-3xl border border-zinc-200/90 bg-white/95 p-6 shadow-senselab-card hover:shadow-senselab-elevated hover:-translate-y-1 transition-all duration-300 dark:border-zinc-800 dark:bg-zinc-950/90 backdrop-blur-md"
                    :class="track.themeBorder"
                >
                    <div>
                        <!-- Top Icon & Status Pill -->
                        <div class="flex items-center justify-between">
                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl border border-zinc-200/80 bg-zinc-50 text-2xl shadow-2xs dark:border-zinc-800 dark:bg-zinc-900/60">
                                {{ track.icon }}
                            </div>
                            <span class="inline-flex items-center gap-1.5 rounded-full border border-zinc-200/80 bg-zinc-50 px-3 py-1 text-xs font-semibold text-zinc-700 shadow-2xs dark:border-zinc-800 dark:bg-zinc-900/80 dark:text-zinc-300">
                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                {{ track.status }}
                            </span>
                        </div>

                        <!-- Title & Category -->
                        <div class="mt-4">
                            <span class="text-[11px] font-bold uppercase tracking-wider text-sky-600 dark:text-sky-400">
                                {{ track.category }}
                            </span>
                            <h3 class="text-lg font-bold text-senselab-heading group-hover:text-sky-600 transition-colors leading-snug mt-0.5">
                                {{ track.title }}
                            </h3>
                            <p class="mt-2 text-xs leading-relaxed text-brand-muted">
                                {{ track.description }}
                            </p>
                        </div>
                    </div>

                    <!-- Bottom Action Link -->
                    <div class="mt-6 flex items-center justify-between border-t border-zinc-100 pt-4 dark:border-zinc-800/80">
                        <span class="text-xs font-semibold text-brand-muted">
                            {{ track.stat }}
                        </span>
                        <Link
                            :href="track.href"
                            class="inline-flex items-center gap-1.5 rounded-full px-4 py-2 text-xs font-semibold text-white shadow-2xs hover:shadow-xs transition-all active:scale-95"
                            style="background: var(--brand-gradient);"
                        >
                            <span>Open Track</span>
                            <span>➔</span>
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. System Metrics & Architecture Overview -->
        <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
            <!-- Metric 1: Verified MCQs -->
            <div class="flex flex-col justify-between rounded-3xl border border-sky-100 bg-sky-50/50 p-6 shadow-senselab-card dark:border-sky-900/40 dark:bg-sky-950/20">
                <div class="flex items-center justify-between text-xs font-bold uppercase tracking-wider text-sky-700 dark:text-sky-400">
                    <span>Verified Question Bank</span>
                    <span class="rounded-xl bg-sky-100 p-1.5 dark:bg-sky-900/60">📚</span>
                </div>
                <div class="mt-4">
                    <span class="text-3xl sm:text-4xl font-extrabold tracking-tight text-senselab-heading">892+</span>
                    <p class="text-xs text-brand-muted mt-1 font-medium">Curated and reviewed MCQs across PMA Academic & Defense syllabi.</p>
                </div>
            </div>

            <!-- Metric 2: Academic Subjects -->
            <div class="flex flex-col justify-between rounded-3xl border border-purple-100 bg-purple-50/50 p-6 shadow-senselab-card dark:border-purple-900/40 dark:bg-purple-950/20">
                <div class="flex items-center justify-between text-xs font-bold uppercase tracking-wider text-purple-700 dark:text-purple-400">
                    <span>Academic Disciplines</span>
                    <span class="rounded-xl bg-purple-100 p-1.5 dark:bg-purple-900/60">🎯</span>
                </div>
                <div class="mt-4">
                    <span class="text-3xl sm:text-4xl font-extrabold tracking-tight text-senselab-heading">5 Subjects</span>
                    <p class="text-xs text-brand-muted mt-1 font-medium">Islamiat, Pak History, Geography, GK Science, and Current Affairs.</p>
                </div>
            </div>

            <!-- Metric 3: Threshold Mastery Rule -->
            <div class="flex flex-col justify-between rounded-3xl border border-emerald-100 bg-emerald-50/50 p-6 shadow-senselab-card dark:border-emerald-900/40 dark:bg-emerald-950/20">
                <div class="flex items-center justify-between text-xs font-bold uppercase tracking-wider text-emerald-700 dark:text-emerald-400">
                    <span>Mastery Protocol</span>
                    <span class="rounded-xl bg-emerald-100 p-1.5 dark:bg-emerald-900/60">⚡</span>
                </div>
                <div class="mt-4">
                    <span class="text-3xl sm:text-4xl font-extrabold tracking-tight text-emerald-600 dark:text-emerald-400">2-Strike</span>
                    <p class="text-xs text-brand-muted mt-1 font-medium">Spaced repetition requiring 2 consecutive correct answers to graduate.</p>
                </div>
            </div>
        </div>

        <!-- 4. Interactive Knowledge Graph Visualizer (SenseLab Identity) -->
        <div class="rounded-3xl border border-zinc-200/90 bg-white/95 p-6 sm:p-8 shadow-senselab-card backdrop-blur-md dark:border-zinc-800 dark:bg-zinc-950/90">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-zinc-100 pb-4 dark:border-zinc-800/80">
                <div>
                    <h3 class="text-base font-bold text-senselab-heading">
                        Curriculum Topology & Memory Architecture
                    </h3>
                    <p class="text-xs text-brand-muted">
                        Visualizing domain relations across PMA initial intelligence, academic boards, and competitive syllabi.
                    </p>
                </div>
                <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-500/10 px-3 py-1 text-xs font-bold text-emerald-600 dark:text-emerald-400">
                    <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    Verified Graph Active
                </span>
            </div>

            <div class="mt-6 flex items-center justify-center min-h-[200px]">
                <div class="relative w-full max-w-lg flex items-center justify-center">
                    <svg class="w-full h-44" viewBox="0 0 450 160" fill="none">
                        <!-- Connection Lines -->
                        <path d="M120 80 L225 40" stroke="#cbd5e1" stroke-width="1.5" stroke-dasharray="3 3" />
                        <path d="M120 80 L225 120" stroke="#cbd5e1" stroke-width="1.5" stroke-dasharray="3 3" />
                        <path d="M225 40 L340 50" stroke="#cbd5e1" stroke-width="1.5" stroke-dasharray="3 3" />
                        <path d="M225 120 L340 110" stroke="#cbd5e1" stroke-width="1.5" stroke-dasharray="3 3" />
                        <path d="M225 40 L225 120" stroke="#93c5fd" stroke-width="2" />

                        <!-- Node 1: Verbal -->
                        <circle cx="120" cy="80" r="26" fill="#ffffff" stroke="#0284c7" stroke-width="2.5" />
                        <text x="120" y="83" font-size="10" font-weight="bold" fill="#0284c7" text-anchor="middle">Verbal</text>

                        <!-- Central Node: Core Knowledge -->
                        <circle cx="225" cy="40" r="30" fill="#ffffff" stroke="#ea580c" stroke-width="2.5" />
                        <text x="225" y="38" font-size="10" font-weight="bold" fill="#ea580c" text-anchor="middle">Syllabus</text>
                        <text x="225" y="49" font-size="8" fill="#64748b" text-anchor="middle">Core</text>

                        <!-- Node 3: Non-Verbal -->
                        <circle cx="225" cy="120" r="24" fill="#ffffff" stroke="#8b5cf6" stroke-width="2" />
                        <text x="225" y="123" font-size="9" font-weight="bold" fill="#8b5cf6" text-anchor="middle">Matrices</text>

                        <!-- Node 4: ISSB -->
                        <circle cx="340" cy="50" r="22" fill="#ffffff" stroke="#10b981" stroke-width="2" />
                        <text x="340" y="53" font-size="9" font-weight="bold" fill="#10b981" text-anchor="middle">WAT/SRT</text>

                        <!-- Node 5: Academics -->
                        <circle cx="340" cy="110" r="20" fill="#ffffff" stroke="#f59e0b" stroke-width="2" />
                        <text x="340" y="113" font-size="8" font-weight="bold" fill="#f59e0b" text-anchor="middle">Academics</text>
                    </svg>
                </div>
            </div>
        </div>
    </div>
</template>

