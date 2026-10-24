<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';

const page = usePage();
const isAuthenticated = computed(() => !!page.props.auth?.user);

const mobileMenuOpen = ref(false);
const isYearly = ref(false);
const simAnswer = ref(0);
const showSimModal = ref(false);
const activeTab = ref('Test Engine');

// Simulated MCQ options
const simOptions = ['Ear', 'Nose', 'Tongue', 'Hand'];

// Flagship Exam Programs
const examCategories = [
    {
        title: 'Armed Forces & Defense',
        badge: 'PMA / ISSB / PAF / Navy',
        description:
            'Complete selection pipeline testing from initial computerized intelligence tests to 4-day ISSB psychological evaluations.',
        slug: 'pma-initial-course',
        features: [
            'Verbal & Non-Verbal Matrix Reasoning',
            'Word Association (WAT) & Situation Reactions (SRT)',
            'Academic Selection Tests (GK, English, Physics)',
            'Military Board Oral Interview Prompts',
        ],
        actionLabel: 'Launch PMA Course',
    },
    {
        title: 'GD Pilot — Pakistan Air Force',
        badge: 'PAF / GD Pilot',
        description:
            'Pakistan Air Force GD Pilot course: academic question banks for English, Mathematics, and Physics with timed practice tests.',
        slug: 'gd-pilot',
        features: [
            'English, Mathematics & Physics MCQ banks',
            '336 verified questions',
            'Timed practice with instant scoring',
            '60% / 45-minute phase gating',
        ],
        actionLabel: 'Launch GD Pilot Course',
    },
    {
        title: 'Civil Services (CSS / PMS)',
        badge: 'FPSC Competitive Examination',
        description:
            'Rigorous preparation for compulsory and optional papers with rubric-driven subjective evaluations.',
        slug: 'css-exam-2027',
        note: 'Currently empty — the question bank is being built and courses are on the way.',
        features: [
            '2,500-Word English Essay Evaluation Workspace',
            'General Science & Ability (MPT) MCQs',
            'Past Papers Compilation (2015–2026 Archive)',
            'Current Affairs Monthly Dossiers',
        ],
        actionLabel: 'Explore CSS Curriculum',
    },
    {
        title: 'Higher Education & Entry Tests',
        badge: 'MDCAT / ECAT / NUST / GAT',
        description:
            'High-yield subject banks engineered for competitive medical, engineering, and graduate admission tests.',
        slug: null,
        features: [
            'Chapter-Wise Analytical MCQs',
            'Simulated Full-Length Timed Grand Tests',
            'Detailed Step-by-Step Problem Explanations',
            'Accuracy & Topic Mastery Progress Rings',
        ],
        actionLabel: 'View Test Banks',
    },
];

// Interactive cloud tags (Screenshot 2)
const cloudTags = [
    'Verbal Intelligence',
    'Non-Verbal Matrices',
    'Pattern Completion',
    'Word Association (WAT)',
    'Situation Reaction (SRT)',
    'CSS Essay Rubrics',
    'FPSC Past Papers',
    'Socratic AI Mentor',
    'Zero-Knowledge Keys',
    'Negative Marking Formula',
    'Timer Clock Sync',
    'Subjective Uploads',
    'Decision Lineage',
    'Confidence Scoring',
];
</script>

<template>
    <Head title="Pak-All-Tests — Build Knowledge That Improves Itself" />

    <div
        class="relative min-h-screen overflow-x-hidden bg-brand-canvas font-sans text-brand-body selection:bg-sky-500/20 selection:text-sky-600"
    >
        <!-- Subtle Ambient Background Mesh Aura (SenseLab Screenshot 1) -->
        <div class="pointer-events-none absolute inset-0 -z-10 overflow-hidden">
            <div class="mesh-aura-cyan-lg absolute -top-40 -left-40"></div>
            <div class="mesh-aura-warm-lg absolute top-20 -right-40"></div>
        </div>

        <!-- ═══════════════════════════════════════════════════════════════
             1. HEADER NAVIGATION (SenseLab Screenshot 1)
             ═══════════════════════════════════════════════════════════════ -->
        <header
            class="sticky top-0 z-40 w-full border-b border-zinc-100/90 bg-white/80 backdrop-blur-md transition-colors dark:border-zinc-800/80 dark:bg-zinc-950/80"
        >
            <div
                class="mx-auto flex h-18 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8"
            >
                <!-- SenseLab Ribbon Logo & Platform Brand -->
                <Link href="/" class="group flex items-center gap-2.5">
                    <!-- Ribbon S / Infinity Mark -->
                    <div
                        class="relative flex h-9 w-9 items-center justify-center"
                    >
                        <AppLogoIcon
                            class="h-8 w-8 transition-transform duration-300 group-hover:scale-105"
                        />
                    </div>
                    <span
                        class="text-lg font-bold tracking-tight text-brand-heading transition-colors group-hover:text-sky-600"
                    >
                        Pak-All-Tests
                    </span>
                </Link>

                <!-- Center Navigation Menu (Exact SenseLab Screenshot 1) -->
                <nav
                    class="hidden items-center gap-7 text-sm font-medium text-brand-muted md:flex"
                >
                    <a
                        href="#"
                        class="font-semibold text-sky-600 transition-colors"
                        >Home</a
                    >
                    <a
                        href="#how-it-works"
                        class="transition-colors hover:text-brand-heading"
                        >About</a
                    >
                    <a
                        href="#exams"
                        class="flex items-center gap-1 transition-colors hover:text-brand-heading"
                    >
                        Examinations
                        <svg
                            class="h-3.5 w-3.5 opacity-60"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M19 9l-7 7-7-7"
                            />
                        </svg>
                    </a>
                    <a
                        href="#architecture"
                        class="transition-colors hover:text-brand-heading"
                        >Solutions</a
                    >
                    <a
                        href="#pricing"
                        class="flex items-center gap-1 transition-colors hover:text-brand-heading"
                    >
                        Pricing
                        <svg
                            class="h-3.5 w-3.5 opacity-60"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M19 9l-7 7-7-7"
                            />
                        </svg>
                    </a>
                </nav>

                <!-- Right Action: SenseLab Black Pill Button (Screenshot 1) -->
                <div class="hidden items-center gap-3 sm:flex">
                    <template v-if="isAuthenticated">
                        <Link
                            href="/d/pma-initial-course"
                            class="inline-flex items-center gap-2 rounded-full bg-zinc-950 px-5 py-2 text-xs font-semibold text-white shadow-xs transition-all hover:bg-zinc-800 active:scale-95 dark:bg-white dark:text-zinc-950 dark:hover:bg-zinc-200"
                        >
                            <span>Exam Dashboard</span>
                            <span>→</span>
                        </Link>
                    </template>
                    <template v-else>
                        <Link
                            href="/login"
                            class="inline-flex items-center gap-1.5 rounded-full bg-zinc-950 px-5 py-2 text-xs font-semibold text-white shadow-xs transition-all hover:bg-zinc-800 active:scale-95 dark:bg-white dark:text-zinc-950 dark:hover:bg-zinc-200"
                        >
                            <span>Login</span>
                            <svg
                                class="h-3.5 w-3.5"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="2.5"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"
                                />
                            </svg>
                        </Link>

                        <button
                            type="button"
                            @click="router.post('/guest-login')"
                            class="inline-flex cursor-pointer items-center gap-1.5 rounded-full border border-amber-300/80 bg-amber-500/10 px-4 py-2 text-xs font-semibold text-amber-800 shadow-xs transition-all hover:bg-amber-500/20 active:scale-95 dark:border-amber-500/40 dark:bg-amber-950/40 dark:text-amber-300"
                        >
                            <span
                                >⚡ Instant 1-Day Guest Pass (No
                                Registration)</span
                            >
                            <span>→</span>
                        </button>
                    </template>
                </div>

                <!-- Mobile Hamburger Button -->
                <button
                    type="button"
                    @click="mobileMenuOpen = !mobileMenuOpen"
                    class="rounded-lg p-2 text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900 md:hidden dark:text-zinc-400 dark:hover:bg-zinc-900"
                    aria-label="Toggle navigation"
                >
                    <svg
                        class="h-6 w-6"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <path
                            v-if="!mobileMenuOpen"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M4 6h16M4 12h16m-7 6h7"
                        />
                        <path
                            v-else
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M6 18L18 6M6 6l12 12"
                        />
                    </svg>
                </button>
            </div>

            <!-- Mobile Navigation Drawer -->
            <div
                v-if="mobileMenuOpen"
                class="border-b border-zinc-200 bg-white px-4 pt-2 pb-6 md:hidden dark:border-zinc-800 dark:bg-zinc-950"
            >
                <nav class="flex flex-col gap-2.5 text-sm font-medium">
                    <a
                        href="#"
                        @click="mobileMenuOpen = false"
                        class="py-2 font-semibold text-sky-600"
                        >Home</a
                    >
                    <a
                        href="#how-it-works"
                        @click="mobileMenuOpen = false"
                        class="py-2 text-zinc-700 dark:text-zinc-300"
                        >About</a
                    >
                    <a
                        href="#exams"
                        @click="mobileMenuOpen = false"
                        class="py-2 text-zinc-700 dark:text-zinc-300"
                        >Examinations</a
                    >
                    <a
                        href="#architecture"
                        @click="mobileMenuOpen = false"
                        class="py-2 text-zinc-700 dark:text-zinc-300"
                        >Architecture</a
                    >
                    <a
                        href="#pricing"
                        @click="mobileMenuOpen = false"
                        class="py-2 text-zinc-700 dark:text-zinc-300"
                        >Pricing</a
                    >
                    <div
                        class="flex flex-col gap-2 border-t border-zinc-100 pt-2 dark:border-zinc-800"
                    >
                        <button
                            v-if="!isAuthenticated"
                            type="button"
                            @click="
                                mobileMenuOpen = false;
                                router.post('/guest-login');
                            "
                            class="inline-flex w-full cursor-pointer items-center justify-center gap-1.5 rounded-full border border-amber-300/80 bg-amber-500/10 py-2.5 text-center text-xs font-semibold text-amber-800 shadow-xs transition-all hover:bg-amber-500/20 active:scale-95 dark:border-amber-500/40 dark:bg-amber-950/40 dark:text-amber-300"
                        >
                            <span
                                >⚡ Instant 1-Day Guest Pass (No
                                Registration)</span
                            >
                            <span>→</span>
                        </button>
                        <Link
                            href="/login"
                            class="w-full rounded-full border border-zinc-300 py-2.5 text-center text-xs font-medium text-zinc-800 dark:border-zinc-700 dark:text-zinc-200"
                        >
                            Sign In
                        </Link>
                        <Link
                            href="/register"
                            class="w-full rounded-full bg-zinc-950 py-2.5 text-center text-xs font-semibold text-white dark:bg-white dark:text-zinc-950"
                        >
                            Get Started Free
                        </Link>
                    </div>
                </nav>
            </div>
        </header>

        <main class="flex-1">
            <!-- ═══════════════════════════════════════════════════════════════
                 2. HERO SECTION (SenseLab Screenshot 1)
                 ═══════════════════════════════════════════════════════════════ -->
            <section
                class="relative px-4 pt-16 pb-20 text-center sm:px-6 md:pt-24 md:pb-28 lg:px-8"
            >
                <!-- Backed by / Institutional Endorsement Pill (Screenshot 1) -->
                <div class="mb-6 inline-flex items-center justify-center">
                    <div
                        class="inline-flex items-center gap-2 rounded-full border border-zinc-200/90 bg-zinc-100/80 px-4 py-1.5 text-xs font-medium text-zinc-700 shadow-xs dark:border-zinc-800 dark:bg-zinc-900/80 dark:text-zinc-300"
                    >
                        <span class="font-normal text-zinc-500">Backed by</span>
                        <span
                            class="flex items-center gap-1 font-bold tracking-tight text-zinc-900 dark:text-white"
                        >
                            National Defense & Competitive Syllabi
                        </span>
                    </div>
                </div>

                <!-- Main Giant SenseLab Headline -->
                <h1
                    class="mx-auto max-w-4xl text-4xl leading-[1.08] font-extrabold tracking-tight text-brand-heading sm:text-6xl lg:text-7xl"
                >
                    Build Knowledge That<br class="hidden sm:inline" />
                    Improves Itself.
                </h1>

                <!-- Subtitle Paragraph (Screenshot 1) -->
                <p
                    class="mx-auto mt-6 max-w-2xl text-base leading-relaxed font-normal text-brand-muted sm:text-lg"
                >
                    Pak-All-Tests is the recursive self-improvement preparation
                    infrastructure for Pakistan's premier examinations. Turn
                    what worked and failed into verified learning that makes you
                    sharper, faster, and more reliable with every test session.
                </p>

                <!-- Dual Action CTAs: SenseLab Gradient Button + White Pill Button (Screenshot 1) -->
                <div
                    class="mt-8 flex flex-wrap items-center justify-center gap-3.5"
                >
                    <Link
                        :href="
                            isAuthenticated
                                ? '/d/pma-initial-course'
                                : '/register'
                        "
                        class="inline-flex items-center gap-2 rounded-full px-7 py-3.5 text-sm font-semibold text-white shadow-md transition-all duration-200 hover:scale-[1.02] hover:shadow-lg active:scale-95"
                        style="background: var(--brand-gradient)"
                    >
                        <span>Start Free</span>
                        <svg
                            class="h-4 w-4"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="2.5"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M15.59 14.37a6 6 0 01-5.84 7.38v-4.8m5.84-2.58a14.98 14.98 0 006.16-12.12A14.98 14.98 0 009.63 8.41m5.96 5.96a14.926 14.926 0 01-5.84 2.58m-.12-8.54a6 6 0 10-8.48 8.48l1.41-1.41"
                            />
                        </svg>
                    </Link>

                    <button
                        v-if="!isAuthenticated"
                        type="button"
                        class="inline-flex items-center gap-2 rounded-full border border-amber-300/80 bg-amber-500/10 px-6 py-3.5 text-sm font-semibold text-amber-800 shadow-xs transition-all hover:bg-amber-500/20 active:scale-95 dark:border-amber-500/30 dark:bg-amber-950/40 dark:text-amber-300"
                        @click="router.post('/guest-login')"
                    >
                        <span>⚡ Instant 1-Day Access</span>
                        <span>→</span>
                    </button>

                    <a
                        href="#how-it-works"
                        class="inline-flex items-center gap-2 rounded-full border border-zinc-200/90 bg-white px-7 py-3.5 text-sm font-semibold text-zinc-800 shadow-xs transition-all hover:bg-zinc-50 active:scale-95 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200"
                    >
                        <span>How it works</span>
                        <span>✨</span>
                    </a>
                </div>

                <!-- ═══════════════════════════════════════════════════════════
                     HERO MOCKUP CANVAS + 3 OVERLAPPING CARDS (Screenshot 1)
                     ═══════════════════════════════════════════════════════════ -->
                <div class="relative mx-auto mt-14 max-w-5xl">
                    <!-- Main Application Window Container (Screenshot 1) -->
                    <div
                        class="overflow-hidden rounded-3xl border border-zinc-200/90 bg-white/95 text-left shadow-2xl backdrop-blur-md dark:border-zinc-800 dark:bg-zinc-950/95"
                    >
                        <!-- Top App Bar -->
                        <div
                            class="flex items-center justify-between border-b border-zinc-100 bg-zinc-50/70 px-4 py-3 dark:border-zinc-800/80 dark:bg-zinc-900/60"
                        >
                            <div class="flex items-center gap-2">
                                <div class="flex gap-1.5">
                                    <div
                                        class="h-3 w-3 rounded-full bg-rose-400"
                                    ></div>
                                    <div
                                        class="h-3 w-3 rounded-full bg-amber-400"
                                    ></div>
                                    <div
                                        class="h-3 w-3 rounded-full bg-emerald-400"
                                    ></div>
                                </div>
                                <span
                                    class="ml-2 font-mono text-xs font-medium text-zinc-500"
                                    >Pak-All-Tests Kernel • Live</span
                                >
                            </div>

                            <!-- Search Bar Pill -->
                            <div
                                class="hidden items-center gap-2 rounded-full border border-zinc-200/80 bg-white px-3.5 py-1 text-xs text-zinc-400 sm:flex dark:border-zinc-700 dark:bg-zinc-800"
                            >
                                <svg
                                    class="h-3.5 w-3.5 text-zinc-400"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                                    />
                                </svg>
                                <span
                                    >Search syllabus, past papers, MCQs...</span
                                >
                                <span
                                    class="rounded bg-zinc-100 px-1.5 py-0.5 font-mono text-[10px] font-semibold text-zinc-500 dark:bg-zinc-700"
                                    >⌘K</span
                                >
                            </div>

                            <!-- User Pill -->
                            <div
                                class="flex items-center gap-2 font-mono text-xs text-zinc-600 dark:text-zinc-400"
                            >
                                <span
                                    class="h-2 w-2 rounded-full bg-emerald-500"
                                ></span>
                                <span>cadet.2026@paktests.pk</span>
                            </div>
                        </div>

                        <!-- Inner Tab Bar -->
                        <div
                            class="no-scrollbar flex items-center gap-1 overflow-x-auto border-b border-zinc-100 px-4 py-2 text-xs font-medium text-zinc-500 dark:border-zinc-800"
                        >
                            <button
                                v-for="tab in [
                                    'Overview',
                                    'Brain Brief',
                                    'Test Engine',
                                    'Memory Graph',
                                    'Knowledge Graph',
                                    'Expertise',
                                    'Tiers',
                                ]"
                                :key="tab"
                                @click="activeTab = tab"
                                type="button"
                                :class="[
                                    'rounded-lg px-3 py-1.5 whitespace-nowrap transition-colors',
                                    activeTab === tab
                                        ? 'bg-zinc-100 font-semibold text-zinc-900 dark:bg-zinc-800 dark:text-white'
                                        : 'hover:text-zinc-900 dark:hover:text-white',
                                ]"
                            >
                                {{ tab }}
                            </button>
                        </div>

                        <!-- Metric Cards Row (Screenshot 1: 3 cards) -->
                        <div class="p-6">
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                <!-- Metric 1: Blue -->
                                <div
                                    class="rounded-2xl border border-sky-100 bg-sky-50/50 p-4 dark:border-sky-900/40 dark:bg-sky-950/20"
                                >
                                    <div
                                        class="flex items-center justify-between text-xs font-bold tracking-wider text-sky-700 uppercase dark:text-sky-400"
                                    >
                                        <span>ENTRIES WRITTEN</span>
                                        <span
                                            class="rounded bg-sky-100 p-1 dark:bg-sky-900/60"
                                            >✏️</span
                                        >
                                    </div>
                                    <div
                                        class="mt-2 text-2xl font-extrabold text-zinc-900 dark:text-white"
                                    >
                                        3
                                    </div>
                                    <div
                                        class="text-[11px] font-medium text-zinc-500"
                                    >
                                        reasoning patterns indexed
                                    </div>
                                </div>

                                <!-- Metric 2: Purple -->
                                <div
                                    class="rounded-2xl border border-purple-100 bg-purple-50/50 p-4 dark:border-purple-900/40 dark:bg-purple-950/20"
                                >
                                    <div
                                        class="flex items-center justify-between text-xs font-bold tracking-wider text-purple-700 uppercase dark:text-purple-400"
                                    >
                                        <span>DOMAINS MASTERED</span>
                                        <span
                                            class="rounded bg-purple-100 p-1 dark:bg-purple-900/60"
                                            >🎯</span
                                        >
                                    </div>
                                    <div
                                        class="mt-2 text-2xl font-extrabold text-zinc-900 dark:text-white"
                                    >
                                        2
                                    </div>
                                    <div
                                        class="text-[11px] font-medium text-zinc-500"
                                    >
                                        across active syllabi
                                    </div>
                                </div>

                                <!-- Metric 3: Emerald -->
                                <div
                                    class="rounded-2xl border border-emerald-100 bg-emerald-50/50 p-4 dark:border-emerald-900/40 dark:bg-emerald-950/20"
                                >
                                    <div
                                        class="flex items-center justify-between text-xs font-bold tracking-wider text-emerald-700 uppercase dark:text-emerald-400"
                                    >
                                        <span>TOTAL TEST READS</span>
                                        <span
                                            class="rounded bg-emerald-100 p-1 dark:bg-emerald-900/60"
                                            >⚡</span
                                        >
                                    </div>
                                    <div
                                        class="mt-2 text-2xl font-extrabold text-zinc-900 dark:text-white"
                                    >
                                        8
                                    </div>
                                    <div
                                        class="text-[11px] font-medium text-zinc-500"
                                    >
                                        memory graphs evaluated
                                    </div>
                                </div>
                            </div>

                            <!-- Knowledge Graph SVG Visualization (Screenshot 1) -->
                            <div
                                class="min-h-\[220px\] mt-6 flex items-center justify-center rounded-2xl border border-zinc-100 bg-zinc-50/40 p-6 dark:border-zinc-800 dark:bg-zinc-900/30"
                            >
                                <div
                                    class="relative flex w-full max-w-lg items-center justify-center"
                                >
                                    <svg
                                        class="h-44 w-full"
                                        viewBox="0 0 450 160"
                                        fill="none"
                                    >
                                        <!-- Connection Lines -->
                                        <path
                                            d="M120 80 L225 40"
                                            stroke="#cbd5e1"
                                            stroke-width="1.5"
                                            stroke-dasharray="3 3"
                                        />
                                        <path
                                            d="M120 80 L225 120"
                                            stroke="#cbd5e1"
                                            stroke-width="1.5"
                                            stroke-dasharray="3 3"
                                        />
                                        <path
                                            d="M225 40 L340 50"
                                            stroke="#cbd5e1"
                                            stroke-width="1.5"
                                            stroke-dasharray="3 3"
                                        />
                                        <path
                                            d="M225 120 L340 110"
                                            stroke="#cbd5e1"
                                            stroke-width="1.5"
                                            stroke-dasharray="3 3"
                                        />
                                        <path
                                            d="M225 40 L225 120"
                                            stroke="#93c5fd"
                                            stroke-width="2"
                                        />

                                        <!-- Node 1: Verbal -->
                                        <circle
                                            cx="120"
                                            cy="80"
                                            r="26"
                                            fill="#ffffff"
                                            stroke="#0284c7"
                                            stroke-width="2.5"
                                        />
                                        <text
                                            x="120"
                                            y="83"
                                            font-size="10"
                                            font-weight="bold"
                                            fill="#0284c7"
                                            text-anchor="middle"
                                        >
                                            Verbal
                                        </text>

                                        <!-- Central Node: Core Knowledge -->
                                        <circle
                                            cx="225"
                                            cy="40"
                                            r="30"
                                            fill="#ffffff"
                                            stroke="#ea580c"
                                            stroke-width="2.5"
                                        />
                                        <text
                                            x="225"
                                            y="38"
                                            font-size="10"
                                            font-weight="bold"
                                            fill="#ea580c"
                                            text-anchor="middle"
                                        >
                                            Syllabus
                                        </text>
                                        <text
                                            x="225"
                                            y="49"
                                            font-size="8"
                                            fill="#64748b"
                                            text-anchor="middle"
                                        >
                                            Core
                                        </text>

                                        <!-- Node 3: Non-Verbal -->
                                        <circle
                                            cx="225"
                                            cy="120"
                                            r="24"
                                            fill="#ffffff"
                                            stroke="#8b5cf6"
                                            stroke-width="2"
                                        />
                                        <text
                                            x="225"
                                            y="123"
                                            font-size="9"
                                            font-weight="bold"
                                            fill="#8b5cf6"
                                            text-anchor="middle"
                                        >
                                            Matrices
                                        </text>

                                        <!-- Node 4: ISSB -->
                                        <circle
                                            cx="340"
                                            cy="50"
                                            r="22"
                                            fill="#ffffff"
                                            stroke="#10b981"
                                            stroke-width="2"
                                        />
                                        <text
                                            x="340"
                                            y="53"
                                            font-size="9"
                                            font-weight="bold"
                                            fill="#10b981"
                                            text-anchor="middle"
                                        >
                                            WAT/SRT
                                        </text>

                                        <!-- Node 5: Academics -->
                                        <circle
                                            cx="340"
                                            cy="110"
                                            r="20"
                                            fill="#ffffff"
                                            stroke="#f59e0b"
                                            stroke-width="2"
                                        />
                                        <text
                                            x="340"
                                            y="113"
                                            font-size="8"
                                            font-weight="bold"
                                            fill="#f59e0b"
                                            text-anchor="middle"
                                        >
                                            CSS MPT
                                        </text>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ═══════════════════════════════════════════════════════
                         3 FLOATING OVERLAPPING CARDS (SenseLab Signature!)
                         ═══════════════════════════════════════════════════════ -->

                    <!-- Floating Card 1 (Left): Rooms with Door Icon (Screenshot 1) -->
                    <div
                        class="animate-fade-in absolute top-16 -left-10 hidden w-56 flex-col items-center rounded-2xl border border-zinc-200/90 bg-white/95 p-5 text-center shadow-xl backdrop-blur-md lg:flex dark:border-zinc-800 dark:bg-zinc-900/95"
                    >
                        <!-- Isometric Door Icon -->
                        <div
                            class="mb-3 flex h-14 w-14 items-center justify-center rounded-xl bg-amber-50 dark:bg-amber-950/40"
                        >
                            <span class="text-3xl">🚪</span>
                        </div>
                        <h4
                            class="text-sm font-bold text-zinc-900 dark:text-white"
                        >
                            Domain Rooms
                        </h4>
                        <p class="mt-1 text-xs leading-snug text-zinc-500">
                            A focused preparation space where exam domains
                            coordinate.
                        </p>
                    </div>

                    <!-- Floating Card 2 (Bottom Left): Dots + No Framework Changes (Screenshot 1) -->
                    <div
                        class="absolute bottom-8 -left-6 hidden w-64 flex-col rounded-2xl border border-zinc-200/90 bg-white/95 p-4 text-left shadow-xl backdrop-blur-md lg:flex dark:border-zinc-800 dark:bg-zinc-900/95"
                    >
                        <div class="mb-2.5 flex items-center gap-1.5">
                            <span
                                class="h-2 w-2 rounded-full bg-sky-500"
                            ></span>
                            <span
                                class="h-2 w-2 rounded-full bg-cyan-400"
                            ></span>
                            <span
                                class="h-2 w-2 rounded-full bg-pink-400"
                            ></span>
                            <span
                                class="h-2 w-2 rounded-full bg-amber-400"
                            ></span>
                        </div>
                        <p
                            class="text-xs leading-relaxed font-medium text-zinc-700 dark:text-zinc-300"
                        >
                            Zero friction. Practice questions, read rationale,
                            commit mastery.
                        </p>
                    </div>

                    <!-- Floating Card 3 (Right): Framework Agnostic Network (Screenshot 1) -->
                    <div
                        class="absolute top-28 -right-8 hidden w-60 flex-col items-center rounded-2xl border border-zinc-200/90 bg-white/95 p-5 text-center shadow-xl backdrop-blur-md lg:flex dark:border-zinc-800 dark:bg-zinc-900/95"
                    >
                        <h4
                            class="mb-3 text-sm font-bold text-zinc-900 dark:text-white"
                        >
                            Universal Coverage
                        </h4>
                        <div
                            class="relative flex h-28 w-28 items-center justify-center"
                        >
                            <!-- Center Logo -->
                            <div
                                class="relative z-10 flex h-10 w-10 items-center justify-center rounded-full bg-linear-to-tr from-sky-500 to-amber-500 text-xs font-bold text-white shadow-md"
                            >
                                P
                            </div>
                            <!-- Radiating Dashed Circles -->
                            <div
                                class="absolute inset-0 rounded-full border border-dashed border-zinc-300 dark:border-zinc-700"
                            ></div>

                            <!-- Badges on Perimeter -->
                            <span
                                class="absolute top-0 rounded bg-zinc-100 px-1.5 py-0.5 text-[10px] font-bold text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300"
                                >PMA</span
                            >
                            <span
                                class="absolute right-0 rounded bg-zinc-100 px-1.5 py-0.5 text-[10px] font-bold text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300"
                                >ISSB</span
                            >
                            <span
                                class="absolute bottom-0 rounded bg-zinc-100 px-1.5 py-0.5 text-[10px] font-bold text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300"
                                >CSS</span
                            >
                            <span
                                class="absolute left-0 rounded bg-zinc-100 px-1.5 py-0.5 text-[10px] font-bold text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300"
                                >PAF</span
                            >
                        </div>
                    </div>
                </div>

                <!-- Bottom Separator Pill (Screenshot 1 & 2 Transition) -->
                <div class="mt-16 flex justify-center" id="how-it-works">
                    <a
                        href="#how-it-works"
                        class="inline-flex items-center gap-1.5 rounded-full border border-zinc-200/90 bg-white px-4 py-1.5 text-xs font-semibold text-zinc-700 shadow-xs hover:bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-300"
                    >
                        <span>🕹️</span>
                        <span>How it works</span>
                    </a>
                </div>
            </section>

            <!-- ═══════════════════════════════════════════════════════════════
                 3. SECTION: BENTO FEATURE GRID (SenseLab Screenshot 2)
                 ═══════════════════════════════════════════════════════════════ -->
            <section
                class="mx-auto max-w-7xl px-4 py-16 sm:px-6 md:py-24 lg:px-8"
            >
                <div class="mx-auto mb-14 max-w-3xl text-center">
                    <h2
                        class="text-3xl font-extrabold tracking-tight text-brand-heading sm:text-5xl"
                    >
                        The more you practice,<br />
                        the more you learn.
                    </h2>
                    <p
                        class="mt-4 text-base leading-relaxed font-normal text-brand-muted sm:text-lg"
                    >
                        As learning compounds, candidates repeat less work,
                        eliminate recurring traps, and make smarter decisions
                        with every task.
                    </p>
                </div>

                <!-- 6-Card Bento Grid (Matching Screenshot 2) -->
                <div class="grid grid-cols-1 gap-6 md:grid-cols-4">
                    <!-- Card 1 (Top Left, 1-col): Shared Knowledge -->
                    <div
                        class="flex flex-col items-center justify-center rounded-3xl border border-zinc-200/80 bg-white p-7 text-center shadow-xs transition-all hover:border-zinc-300 md:col-span-1 dark:border-zinc-800 dark:bg-zinc-900/60"
                    >
                        <div
                            class="mb-4 flex h-12 w-12 items-center justify-center rounded-2xl bg-linear-to-r from-sky-500 to-amber-500 text-white shadow-sm"
                        >
                            <span class="text-xl">🧬</span>
                        </div>
                        <h3 class="mb-2 text-lg font-bold text-brand-heading">
                            Shared Knowledge
                        </h3>
                        <p class="text-xs leading-relaxed text-brand-muted">
                            Share syllabus banks, verified questions, and proven
                            strategies across domains.
                        </p>
                    </div>

                    <!-- Card 2 (Top Center, 2-cols): Outcome-Validated Learning (Screenshot 2) -->
                    <div
                        class="flex flex-col justify-center rounded-3xl border border-zinc-200/80 bg-white p-8 shadow-xs transition-all hover:border-zinc-300 md:col-span-2 dark:border-zinc-800 dark:bg-zinc-900/60"
                    >
                        <!-- 4 Dots (Screenshot 2) -->
                        <div class="mb-4 flex items-center gap-1.5">
                            <span
                                class="h-2.5 w-2.5 rounded-full bg-sky-400"
                            ></span>
                            <span
                                class="h-2.5 w-2.5 rounded-full bg-indigo-400"
                            ></span>
                            <span
                                class="h-2.5 w-2.5 rounded-full bg-pink-400"
                            ></span>
                            <span
                                class="h-2.5 w-2.5 rounded-full bg-amber-400"
                            ></span>
                        </div>
                        <span
                            class="mb-2 text-xs font-bold tracking-wider text-sky-600 uppercase"
                            >Outcome-Validated Learning</span
                        >
                        <h3
                            class="text-2xl leading-snug font-bold text-brand-heading sm:text-3xl"
                        >
                            Use real test attempts to reinforce
                            <span class="font-extrabold text-sky-600"
                                >what works</span
                            >
                            and correct
                            <span class="font-extrabold text-amber-600"
                                >what doesn't</span
                            >.
                        </h3>
                    </div>

                    <!-- Card 3 (Top Right, 1-col): Audit Any Decision (Screenshot 2) -->
                    <div
                        class="flex flex-col justify-between rounded-3xl border border-zinc-200/80 bg-white p-7 shadow-xs transition-all hover:border-zinc-300 md:col-span-1 dark:border-zinc-800 dark:bg-zinc-900/60"
                    >
                        <div>
                            <h3
                                class="mb-2 text-lg font-bold text-brand-heading"
                            >
                                Audit Any Decision
                            </h3>
                            <p
                                class="mb-4 text-xs leading-relaxed text-brand-muted"
                            >
                                Full question timeline and mathematical trace
                                behind every explanation.
                            </p>
                        </div>
                        <div
                            class="rounded-xl border border-zinc-100 bg-zinc-50 p-2.5 dark:border-zinc-800 dark:bg-zinc-900"
                        >
                            <div
                                class="flex items-center justify-between font-mono text-[11px] text-zinc-500"
                            >
                                <span>MCQ #14</span>
                                <span class="font-bold text-emerald-600"
                                    >100% Match</span
                                >
                            </div>
                            <div
                                class="mt-1.5 h-1.5 w-full overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-800"
                            >
                                <div
                                    class="h-full w-4/5 rounded-full bg-sky-500"
                                ></div>
                            </div>
                        </div>
                    </div>

                    <!-- Card 4 (Bottom Left, 1-col): Shared Spaces (Screenshot 2) -->
                    <div
                        class="flex flex-col justify-between rounded-3xl border border-zinc-200/80 bg-white p-7 shadow-xs transition-all hover:border-zinc-300 md:col-span-1 dark:border-zinc-800 dark:bg-zinc-900/60"
                    >
                        <div>
                            <h3
                                class="mb-2 text-lg font-bold text-brand-heading"
                            >
                                Focused Workspaces
                            </h3>
                            <p
                                class="mb-4 text-xs leading-relaxed text-brand-muted"
                            >
                                Dedicated interactive players for timed MCQs,
                                verbal series, and essay evaluation.
                            </p>
                        </div>
                        <div
                            class="rounded-xl border border-zinc-100 bg-zinc-50 p-3 font-mono text-[10px] text-zinc-600 dark:border-zinc-800 dark:bg-zinc-900"
                        >
                            <span class="text-sky-600">PMA_Long_Course</span>
                            &gt; Verbal_Test_84Q
                        </div>
                    </div>

                    <!-- Card 5 (Bottom Center, 2-cols): Floating Pill Cloud (Exact Screenshot 2) -->
                    <div
                        class="flex items-center justify-center rounded-3xl border border-zinc-200/80 bg-white p-8 shadow-xs transition-all hover:border-zinc-300 md:col-span-2 dark:border-zinc-800 dark:bg-zinc-900/60"
                    >
                        <div
                            class="flex max-w-lg flex-wrap justify-center gap-2"
                        >
                            <span
                                v-for="(tag, idx) in cloudTags"
                                :key="idx"
                                :class="[
                                    'rounded-full px-3.5 py-1.5 text-xs font-medium shadow-xs transition-all hover:scale-105',
                                    idx % 4 === 0
                                        ? 'border border-sky-100 bg-sky-50 text-sky-700 dark:border-sky-900 dark:bg-sky-950/40 dark:text-sky-300'
                                        : idx % 4 === 1
                                          ? 'border border-amber-100 bg-amber-50 text-amber-700 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-300'
                                          : idx % 4 === 2
                                            ? 'border border-purple-100 bg-purple-50 text-purple-700 dark:border-purple-900 dark:bg-purple-950/40 dark:text-purple-300'
                                            : 'border border-zinc-200 bg-zinc-100 text-zinc-700 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300',
                                ]"
                            >
                                {{ tag }}
                            </span>
                        </div>
                    </div>

                    <!-- Card 6 (Bottom Right, 1-col): Train Your Own Models / Master Exam Pattern -->
                    <div
                        class="flex flex-col justify-between rounded-3xl border border-zinc-200/80 bg-white p-7 shadow-xs transition-all hover:border-zinc-300 md:col-span-1 dark:border-zinc-800 dark:bg-zinc-900/60"
                    >
                        <div>
                            <h3
                                class="mb-2 text-lg font-bold text-brand-heading"
                            >
                                Master Your Exam Pattern
                            </h3>
                            <p
                                class="mb-4 text-xs leading-relaxed text-brand-muted"
                            >
                                Turn every practice mistake into focused
                                remediation with targeted Socratic AI feedback.
                            </p>
                        </div>
                        <div class="flex items-center justify-center py-2">
                            <span class="text-3xl">🕸️</span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ═══════════════════════════════════════════════════════════════
                 4. SECTION: DARK LASER CIRCUIT VIDEO FEATURE (SenseLab Screenshot 3)
                 ═══════════════════════════════════════════════════════════════ -->
            <section
                class="px-4 py-16 text-center sm:px-6 md:py-24 lg:px-8"
                id="simulator"
            >
                <!-- Pill Tag (Screenshot 3) -->
                <div class="mb-5 inline-flex items-center justify-center">
                    <span
                        class="inline-flex items-center gap-1.5 rounded-full border border-zinc-200/90 bg-white px-4 py-1.5 text-xs font-semibold text-zinc-700 shadow-xs dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-300"
                    >
                        <span>🎯</span>
                        <span>Pak-All-Tests Explained</span>
                    </span>
                </div>

                <h2
                    class="mx-auto max-w-3xl text-3xl font-extrabold tracking-tight text-brand-heading sm:text-5xl"
                >
                    See Adaptive Exam Mastery in action.
                </h2>
                <p
                    class="mx-auto mt-4 max-w-2xl text-base font-normal text-brand-muted sm:text-lg"
                >
                    You focus on preparation. Watch our platform turn every test
                    session into measurable score gains.
                </p>

                <!-- SenseLab Deep Obsidian Dark Laser Container (Exact Screenshot 3) -->
                <div
                    class="min-h-\[420px\] relative mx-auto mt-12 flex max-w-5xl flex-col items-center justify-center overflow-hidden rounded-3xl bg-brand-dark p-8 text-white shadow-2xl sm:p-20"
                >
                    <!-- Glowing Laser Vector Circuit Paths in Background -->
                    <div class="pointer-events-none absolute inset-0">
                        <svg
                            class="h-full w-full opacity-60"
                            viewBox="0 0 800 400"
                            fill="none"
                        >
                            <path
                                d="M 50 350 Q 200 320 300 240 T 550 180 T 750 80"
                                stroke="url(#laserCyan)"
                                stroke-width="2.5"
                            />
                            <path
                                d="M 80 380 Q 250 350 380 260 T 600 200 T 780 120"
                                stroke="url(#laserBlue)"
                                stroke-width="1.5"
                            />
                            <defs>
                                <linearGradient
                                    id="laserCyan"
                                    x1="0%"
                                    y1="0%"
                                    x2="100%"
                                    y2="0%"
                                >
                                    <stop
                                        offset="0%"
                                        stop-color="#38bdf8"
                                        stop-opacity="0.1"
                                    />
                                    <stop
                                        offset="60%"
                                        stop-color="#38bdf8"
                                        stop-opacity="0.9"
                                    />
                                    <stop
                                        offset="100%"
                                        stop-color="#ea580c"
                                        stop-opacity="0.2"
                                    />
                                </linearGradient>
                                <linearGradient
                                    id="laserBlue"
                                    x1="0%"
                                    y1="0%"
                                    x2="100%"
                                    y2="0%"
                                >
                                    <stop
                                        offset="0%"
                                        stop-color="#0284c7"
                                        stop-opacity="0.2"
                                    />
                                    <stop
                                        offset="50%"
                                        stop-color="#60a5fa"
                                        stop-opacity="0.8"
                                    />
                                    <stop
                                        offset="100%"
                                        stop-color="#8b5cf6"
                                        stop-opacity="0.1"
                                    />
                                </linearGradient>
                            </defs>
                        </svg>
                    </div>

                    <!-- Glowing Glass Isometric Plane Graphic -->
                    <div class="relative z-10 max-w-2xl text-center">
                        <h3
                            class="text-3xl leading-tight font-extrabold tracking-tight text-white sm:text-5xl"
                        >
                            Your scores should improve with every test.
                        </h3>
                        <p
                            class="mx-auto mt-4 max-w-xl text-sm font-normal text-zinc-400 sm:text-base"
                        >
                            See how Pak-All-Tests turns test attempts and
                            solutions into verified national exam readiness.
                        </p>

                        <!-- Large Glowing Frosted Glass Play Button (Screenshot 3) -->
                        <div class="mt-8 flex justify-center">
                            <button
                                type="button"
                                @click="showSimModal = true"
                                class="group relative flex h-18 w-18 cursor-pointer items-center justify-center rounded-full border border-white/20 bg-white/10 text-white shadow-xl backdrop-blur-md transition-all duration-300 hover:scale-110 hover:bg-white/20 active:scale-95"
                                aria-label="Open Interactive Exam Simulator"
                            >
                                <span
                                    class="absolute inset-0 rounded-full bg-sky-500/20 blur-md transition-colors group-hover:bg-sky-500/40"
                                ></span>
                                <svg
                                    class="relative h-7 w-7 translate-x-0.5 text-white"
                                    fill="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path d="M8 5v14l11-7z" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- CTA Below Video Container (Screenshot 3) -->
                <div class="mt-8 flex justify-center">
                    <Link
                        :href="
                            isAuthenticated
                                ? '/d/pma-initial-course'
                                : '/register'
                        "
                        class="inline-flex items-center gap-2 rounded-full px-7 py-3.5 text-sm font-semibold text-white shadow-md transition-all duration-200 hover:scale-105 hover:shadow-lg active:scale-95"
                        style="background: var(--brand-gradient)"
                    >
                        <span>Start Free</span>
                        <svg
                            class="h-4 w-4"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="2.5"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M15.59 14.37a6 6 0 01-5.84 7.38v-4.8m5.84-2.58a14.98 14.98 0 006.16-12.12A14.98 14.98 0 009.63 8.41m5.96 5.96a14.926 14.926 0 01-5.84 2.58m-.12-8.54a6 6 0 10-8.48 8.48l1.41-1.41"
                            />
                        </svg>
                    </Link>
                </div>
            </section>

            <!-- ═══════════════════════════════════════════════════════════════
                 5. SECTION: 6-NODE ARCHITECTURE HUB (SenseLab Screenshot 4)
                 ═══════════════════════════════════════════════════════════════ -->
            <section
                class="mx-auto max-w-7xl px-4 py-16 text-center sm:px-6 md:py-24 lg:px-8"
                id="architecture"
            >
                <!-- Pill Tag (Screenshot 4) -->
                <div class="mb-5 inline-flex items-center justify-center">
                    <span
                        class="inline-flex items-center gap-1.5 rounded-full border border-zinc-200/90 bg-white px-4 py-1.5 text-xs font-semibold text-zinc-700 shadow-xs dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-300"
                    >
                        <span>🔁</span>
                        <span>The missing learning loop</span>
                    </span>
                </div>

                <h2
                    class="mx-auto max-w-3xl text-3xl font-extrabold tracking-tight text-brand-heading sm:text-5xl"
                >
                    Every test should make the next one better.
                </h2>
                <p
                    class="mx-auto mt-4 max-w-2xl text-base font-normal text-brand-muted sm:text-lg"
                >
                    Pak-All-Tests turns success and failure into learning you
                    can use to improve what you do next.
                </p>

                <!-- 6-Node Architecture Layout with Central Glowing Core (Screenshot 4) -->
                <div class="relative mt-14">
                    <!-- Top 3 Nodes -->
                    <div class="mb-12 grid grid-cols-1 gap-6 md:grid-cols-3">
                        <!-- Node 1: Retrieval != Learning -->
                        <div
                            class="rounded-3xl border border-zinc-200/80 bg-white p-7 text-left shadow-xs transition-all hover:border-zinc-300 dark:border-zinc-800 dark:bg-zinc-900/60"
                        >
                            <h3
                                class="mb-2 text-base font-bold text-brand-heading"
                            >
                                Practice ≠ Guesswork
                            </h3>
                            <p
                                class="mb-4 text-xs leading-relaxed text-brand-muted"
                            >
                                Memorizing random online dumps isn't
                                preparation. Pak-All-Tests validates authentic
                                syllabus ground truths.
                            </p>
                            <div
                                class="rounded-xl border border-zinc-100 bg-zinc-50/80 p-3 font-mono text-[11px] text-zinc-600 dark:border-zinc-800 dark:bg-zinc-900"
                            >
                                <div>AS&amp;RC Intelligence Pattern</div>
                                <div class="mt-1 font-bold text-sky-600">
                                    Verified: 84 MCQs / 30m
                                </div>
                            </div>
                        </div>

                        <!-- Node 2: Authentic Past Papers -->
                        <div
                            class="rounded-3xl border border-zinc-200/80 bg-white p-7 text-left shadow-xs transition-all hover:border-zinc-300 dark:border-zinc-800 dark:bg-zinc-900/60"
                        >
                            <h3
                                class="mb-2 text-base font-bold text-brand-heading"
                            >
                                Authentic Past Papers
                            </h3>
                            <p
                                class="mb-4 text-xs leading-relaxed text-brand-muted"
                            >
                                Complete archive of solved examinations from
                                2015 to 2026. Practice with authentic test
                                constraints.
                            </p>
                            <div
                                class="rounded-xl border border-zinc-100 bg-zinc-50/80 p-3 font-mono text-[11px] text-zinc-600 dark:border-zinc-800 dark:bg-zinc-900"
                            >
                                <div>CSS Compulsory Papers</div>
                                <div class="mt-1 font-bold text-amber-600">
                                    10-Year Solved Rubrics
                                </div>
                            </div>
                        </div>

                        <!-- Node 3: Psychological Profiling -->
                        <div
                            class="rounded-3xl border border-zinc-200/80 bg-white p-7 text-left shadow-xs transition-all hover:border-zinc-300 dark:border-zinc-800 dark:bg-zinc-900/60"
                        >
                            <h3
                                class="mb-2 text-base font-bold text-brand-heading"
                            >
                                Psychological Profiling
                            </h3>
                            <p
                                class="mb-4 text-xs leading-relaxed text-brand-muted"
                            >
                                Specialized WAT, SRT, and TAT modules formatted
                                to ISSB board psychological evaluation
                                standards.
                            </p>
                            <div
                                class="rounded-xl border border-zinc-100 bg-zinc-50/80 p-3 font-mono text-[11px] text-zinc-600 dark:border-zinc-800 dark:bg-zinc-900"
                            >
                                <div>WAT 60-Word Flash Pipeline</div>
                                <div class="mt-1 font-bold text-emerald-600">
                                    Active Timer: 10s / Word
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Central Glowing Core Node (Exact Screenshot 4) -->
                    <div class="my-6 flex justify-center">
                        <div
                            class="relative flex h-20 w-20 items-center justify-center rounded-full border-4 border-white bg-zinc-950 shadow-2xl dark:border-zinc-800"
                        >
                            <span
                                class="absolute -inset-2 rounded-full bg-sky-500/20 blur-lg"
                            ></span>
                            <div
                                class="relative flex h-10 w-10 items-center justify-center"
                            >
                                <span
                                    class="bg-linear-to-r from-sky-400 to-amber-500 bg-clip-text text-2xl font-black text-transparent"
                                    >P</span
                                >
                            </div>
                        </div>
                    </div>

                    <!-- Bottom 3 Nodes -->
                    <div class="mt-12 grid grid-cols-1 gap-6 md:grid-cols-3">
                        <!-- Node 4: Confidence Scoring -->
                        <div
                            class="rounded-3xl border border-zinc-200/80 bg-white p-7 text-left shadow-xs transition-all hover:border-zinc-300 dark:border-zinc-800 dark:bg-zinc-900/60"
                        >
                            <h3
                                class="mb-2 text-base font-bold text-brand-heading"
                            >
                                Confidence Scoring
                            </h3>
                            <p
                                class="mb-4 text-xs leading-relaxed text-brand-muted"
                            >
                                Knowledge is weighted by what you've actually
                                mastered, distinguishing lucky guesses from
                                rock-solid ability.
                            </p>
                            <div
                                class="rounded-xl border border-zinc-100 bg-zinc-50/80 p-3 font-mono text-[11px] text-zinc-600 dark:border-zinc-800 dark:bg-zinc-900"
                            >
                                <div>Confidence Index</div>
                                <div class="mt-1 font-bold text-sky-600">
                                    Weighted Mastery: 94.2%
                                </div>
                            </div>
                        </div>

                        <!-- Node 5: Socratic AI Mentor -->
                        <div
                            class="rounded-3xl border border-zinc-200/80 bg-white p-7 text-left shadow-xs transition-all hover:border-zinc-300 dark:border-zinc-800 dark:bg-zinc-900/60"
                        >
                            <h3
                                class="mb-2 text-base font-bold text-brand-heading"
                            >
                                Real-Time Socratic AI
                            </h3>
                            <p
                                class="mb-4 text-xs leading-relaxed text-brand-muted"
                            >
                                Stuck on a series or analogy question? Get
                                instant step-by-step guidance without spoiling
                                answers.
                            </p>
                            <div
                                class="rounded-xl border border-zinc-100 bg-zinc-50/80 p-3 font-mono text-[11px] text-zinc-600 dark:border-zinc-800 dark:bg-zinc-900"
                            >
                                <div>Socratic Mode</div>
                                <div class="mt-1 font-bold text-purple-600">
                                    2-3 Line Tactical Hints
                                </div>
                            </div>
                        </div>

                        <!-- Node 6: Decision Lineage -->
                        <div
                            class="rounded-3xl border border-zinc-200/80 bg-white p-7 text-left shadow-xs transition-all hover:border-zinc-300 dark:border-zinc-800 dark:bg-zinc-900/60"
                        >
                            <h3
                                class="mb-2 text-base font-bold text-brand-heading"
                            >
                                Decision Lineage
                            </h3>
                            <p
                                class="mb-4 text-xs leading-relaxed text-brand-muted"
                            >
                                Full historical timeline of every attempt, time
                                spent per MCQ, and score progression.
                            </p>
                            <div
                                class="rounded-xl border border-zinc-100 bg-zinc-50/80 p-3 font-mono text-[11px] text-zinc-600 dark:border-zinc-800 dark:bg-zinc-900"
                            >
                                <div>Performance Trace</div>
                                <div class="mt-1 font-bold text-amber-600">
                                    Sub-second Latency Logs
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ═══════════════════════════════════════════════════════════════
                 6. SECTION: CURRICULUM DIRECTORY (Exam Programs)
                 ═══════════════════════════════════════════════════════════════ -->
            <section
                class="mx-auto max-w-7xl px-4 py-16 sm:px-6 md:py-24 lg:px-8"
                id="exams"
            >
                <div class="mx-auto mb-14 max-w-3xl text-center">
                    <div
                        class="mb-4 inline-flex items-center gap-1.5 rounded-full border border-zinc-200/90 bg-white px-4 py-1.5 text-xs font-semibold text-zinc-700 shadow-xs dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-300"
                    >
                        <span>📚</span>
                        <span>Official Syllabus Directory</span>
                    </div>
                    <h2
                        class="text-3xl font-extrabold tracking-tight text-brand-heading sm:text-5xl"
                    >
                        Targeted Programs for Pakistan's Leading Examinations
                    </h2>
                    <p
                        class="mt-4 text-base font-normal text-brand-muted sm:text-lg"
                    >
                        Each curriculum features custom server-driven flows with
                        tailored steppers, multi-tier tab layouts, and automated
                        scoring.
                    </p>
                </div>

                <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                    <div
                        v-for="(cat, idx) in examCategories"
                        :key="idx"
                        class="flex flex-col justify-between rounded-3xl border border-zinc-200/80 bg-white p-7 shadow-xs transition-all hover:border-zinc-300 dark:border-zinc-800 dark:bg-zinc-900/60"
                    >
                        <div>
                            <span
                                class="mb-3 inline-block rounded-full border border-sky-100 bg-sky-50 px-3 py-1 text-xs font-bold text-sky-700 dark:border-sky-900 dark:bg-sky-950/40 dark:text-sky-300"
                            >
                                {{ cat.badge }}
                            </span>
                            <h3
                                class="mb-2 text-xl font-bold text-brand-heading"
                            >
                                {{ cat.title }}
                            </h3>
                            <p
                                class="mb-6 text-xs leading-relaxed text-brand-muted"
                            >
                                {{ cat.description }}
                            </p>

                            <ul
                                class="mb-6 space-y-2.5 border-t border-zinc-100 pt-4 text-xs text-zinc-700 dark:border-zinc-800 dark:text-zinc-300"
                            >
                                <li
                                    v-for="(feat, fIdx) in cat.features"
                                    :key="fIdx"
                                    class="flex items-start gap-2"
                                >
                                    <span class="font-bold text-sky-600"
                                        >✓</span
                                    >
                                    <span>{{ feat }}</span>
                                </li>
                            </ul>
                            <p
                                v-if="cat.note"
                                class="mb-2 flex items-start gap-1.5 text-[11px] leading-relaxed text-amber-700 dark:text-amber-400"
                            >
                                <span aria-hidden="true">ⓘ</span>
                                <span>{{ cat.note }}</span>
                            </p>
                        </div>

                        <div>
                            <Link
                                v-if="cat.slug && isAuthenticated"
                                :href="`/d/${cat.slug}`"
                                class="inline-flex w-full items-center justify-center rounded-full bg-zinc-950 py-3 text-xs font-semibold text-white shadow-xs transition-all hover:bg-zinc-800 active:scale-95 dark:bg-white dark:text-zinc-950 dark:hover:bg-zinc-200"
                            >
                                {{ cat.actionLabel }} →
                            </Link>
                            <Link
                                v-else-if="cat.slug"
                                href="/register"
                                class="inline-flex w-full items-center justify-center rounded-full bg-zinc-950 py-3 text-xs font-semibold text-white shadow-xs transition-all hover:bg-zinc-800 active:scale-95 dark:bg-white dark:text-zinc-950 dark:hover:bg-zinc-200"
                            >
                                {{ cat.actionLabel }} →
                            </Link>
                            <button
                                v-else
                                type="button"
                                class="inline-flex w-full cursor-not-allowed items-center justify-center rounded-full border border-zinc-200 bg-zinc-50 py-3 text-xs font-medium text-zinc-400 dark:border-zinc-800 dark:bg-zinc-900"
                            >
                                In Active Deployment
                            </button>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ═══════════════════════════════════════════════════════════════
                 7. SECTION: PLANS & PRICING WITH ORBITAL ARCH (SenseLab Screenshot 5)
                 ═══════════════════════════════════════════════════════════════ -->
            <section
                class="mx-auto max-w-7xl px-4 py-16 text-center sm:px-6 md:py-24 lg:px-8"
                id="pricing"
            >
                <!-- Orbital Framework Arch Graphic (Exact Screenshot 5) -->
                <div
                    class="relative mx-auto mb-10 flex h-44 w-full max-w-xl items-center justify-center overflow-hidden"
                >
                    <svg
                        class="h-full w-full"
                        viewBox="0 0 500 200"
                        fill="none"
                    >
                        <!-- Concentric Orbital Arcs -->
                        <path
                            d="M 50 190 A 200 200 0 0 1 450 190"
                            stroke="#e2e8f0"
                            stroke-width="1.5"
                        />
                        <path
                            d="M 100 190 A 150 150 0 0 1 400 190"
                            stroke="#e2e8f0"
                            stroke-width="1.5"
                            stroke-dasharray="4 4"
                        />
                        <path
                            d="M 160 190 A 90 90 0 0 1 340 190"
                            stroke="#e2e8f0"
                            stroke-width="1.5"
                        />

                        <!-- Core at Base -->
                        <circle cx="250" cy="190" r="18" fill="#0284c7" />
                        <text
                            x="250"
                            y="194"
                            font-size="10"
                            font-weight="bold"
                            fill="#ffffff"
                            text-anchor="middle"
                        >
                            PAT
                        </text>

                        <!-- Orbiting Badges -->
                        <circle
                            cx="100"
                            cy="120"
                            r="14"
                            fill="#ffffff"
                            stroke="#cbd5e1"
                            stroke-width="1.5"
                        />
                        <text
                            x="100"
                            y="124"
                            font-size="8"
                            font-weight="bold"
                            fill="#0284c7"
                            text-anchor="middle"
                        >
                            PMA
                        </text>

                        <circle
                            cx="170"
                            cy="80"
                            r="14"
                            fill="#ffffff"
                            stroke="#cbd5e1"
                            stroke-width="1.5"
                        />
                        <text
                            x="170"
                            y="84"
                            font-size="8"
                            font-weight="bold"
                            fill="#ea580c"
                            text-anchor="middle"
                        >
                            CSS
                        </text>

                        <circle
                            cx="250"
                            cy="60"
                            r="16"
                            fill="#ffffff"
                            stroke="#0284c7"
                            stroke-width="2"
                        />
                        <text
                            x="250"
                            y="64"
                            font-size="9"
                            font-weight="bold"
                            fill="#0284c7"
                            text-anchor="middle"
                        >
                            ISSB
                        </text>

                        <circle
                            cx="330"
                            cy="80"
                            r="14"
                            fill="#ffffff"
                            stroke="#cbd5e1"
                            stroke-width="1.5"
                        />
                        <text
                            x="330"
                            y="84"
                            font-size="8"
                            font-weight="bold"
                            fill="#8b5cf6"
                            text-anchor="middle"
                        >
                            PAF
                        </text>

                        <circle
                            cx="400"
                            cy="120"
                            r="14"
                            fill="#ffffff"
                            stroke="#cbd5e1"
                            stroke-width="1.5"
                        />
                        <text
                            x="400"
                            y="124"
                            font-size="8"
                            font-weight="bold"
                            fill="#10b981"
                            text-anchor="middle"
                        >
                            NAVY
                        </text>
                    </svg>
                </div>

                <!-- Pill Tag (Screenshot 5) -->
                <div class="mb-5 inline-flex items-center justify-center">
                    <span
                        class="inline-flex items-center gap-1.5 rounded-full border border-zinc-200/90 bg-white px-4 py-1.5 text-xs font-semibold text-zinc-700 shadow-xs dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-300"
                    >
                        <span>💎</span>
                        <span>Plans &amp; Pricing</span>
                    </span>
                </div>

                <h2
                    class="mx-auto max-w-3xl text-3xl font-extrabold tracking-tight text-brand-heading sm:text-5xl"
                >
                    Start free.<br />
                    Scale with your preparation.
                </h2>
                <p
                    class="mx-auto mt-4 max-w-2xl text-base font-normal text-brand-muted sm:text-lg"
                >
                    Every plan includes curriculum access, intelligent
                    simulators, and step-by-step solutions. You only pay for
                    full AI tutoring &amp; grand test series.
                </p>

                <!-- Billing Monthly / Yearly Toggle (Screenshot 5) -->
                <div class="mt-8 flex justify-center">
                    <div
                        class="inline-flex items-center rounded-full border border-zinc-200 bg-zinc-100 p-1 dark:border-zinc-800 dark:bg-zinc-900"
                    >
                        <button
                            type="button"
                            @click="isYearly = false"
                            :class="[
                                'rounded-full px-5 py-2 text-xs font-semibold transition-all',
                                !isYearly
                                    ? 'bg-white text-zinc-950 shadow-xs dark:bg-zinc-800 dark:text-white'
                                    : 'text-zinc-600 dark:text-zinc-400',
                            ]"
                        >
                            Billing Monthly
                        </button>
                        <button
                            type="button"
                            @click="isYearly = true"
                            :class="[
                                'flex items-center gap-1 rounded-full px-5 py-2 text-xs font-semibold transition-all',
                                isYearly
                                    ? 'bg-white text-zinc-950 shadow-xs dark:bg-zinc-800 dark:text-white'
                                    : 'text-zinc-600 dark:text-zinc-400',
                            ]"
                        >
                            <span>Billing Yearly</span>
                            <span
                                class="rounded-full bg-emerald-100 px-1.5 py-0.5 text-[10px] font-bold text-emerald-700"
                                >Save 25%</span
                            >
                        </button>
                    </div>
                </div>

                <!-- 3 Pricing Cards (Exact Screenshot 5) -->
                <div
                    class="mx-auto mt-14 grid max-w-6xl grid-cols-1 gap-8 text-left md:grid-cols-3"
                >
                    <!-- Tier 1: Free -->
                    <div
                        class="flex flex-col justify-between rounded-3xl border border-zinc-200/80 bg-white p-8 shadow-xs dark:border-zinc-800 dark:bg-zinc-900/60"
                    >
                        <div>
                            <span
                                class="mb-4 inline-block rounded-full bg-zinc-100 px-3 py-1 text-xs font-semibold text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300"
                            >
                                Free
                            </span>
                            <div class="text-xs text-brand-muted">
                                Perfect for exploration
                            </div>
                            <div class="mt-3 flex items-baseline gap-1">
                                <span
                                    class="text-4xl font-extrabold text-brand-heading"
                                    >Rs 0</span
                                >
                                <span class="text-xs text-brand-muted"
                                    >/ month</span
                                >
                            </div>

                            <div
                                class="mt-6 border-t border-zinc-100 pt-6 dark:border-zinc-800"
                            >
                                <div
                                    class="mb-3 text-xs font-bold text-brand-heading"
                                >
                                    Plan includes:
                                </div>
                                <ul
                                    class="space-y-2.5 text-xs text-brand-muted"
                                >
                                    <li class="flex items-center gap-2">
                                        <span class="font-bold text-sky-600"
                                            >✓</span
                                        >
                                        <span>5 Practice Tests / month</span>
                                    </li>
                                    <li class="flex items-center gap-2">
                                        <span class="font-bold text-sky-600"
                                            >✓</span
                                        >
                                        <span>Standard Answer Keys</span>
                                    </li>
                                    <li class="flex items-center gap-2">
                                        <span class="font-bold text-sky-600"
                                            >✓</span
                                        >
                                        <span>Curriculum Navigation</span>
                                    </li>
                                    <li class="flex items-center gap-2">
                                        <span class="font-bold text-sky-600"
                                            >✓</span
                                        >
                                        <span>Community Support</span>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <div class="mt-8">
                            <Link
                                href="/register"
                                class="inline-flex w-full items-center justify-center rounded-full border border-sky-600/40 bg-white py-3 text-xs font-semibold text-sky-700 shadow-xs transition-all hover:border-sky-600 hover:bg-sky-50 active:scale-95 dark:bg-zinc-900 dark:text-sky-400"
                            >
                                Choose This Plan
                            </Link>
                        </div>
                    </div>

                    <!-- Tier 2: Starter / Cadet Pro (Featured Radiant Border - Screenshot 5) -->
                    <div
                        class="relative flex scale-[1.02] flex-col justify-between rounded-3xl border-2 border-sky-400/80 bg-white p-8 shadow-xl dark:border-sky-500/80 dark:bg-zinc-900"
                    >
                        <div
                            class="absolute -top-3 right-8 left-8 flex items-center justify-between"
                        >
                            <span
                                class="rounded-full bg-sky-100 px-3 py-0.5 text-[11px] font-bold text-sky-700 shadow-xs"
                                >Starter</span
                            >
                            <span
                                class="rounded-full bg-amber-100 px-3 py-0.5 text-[11px] font-bold text-amber-700 shadow-xs"
                                >Most Popular</span
                            >
                        </div>

                        <div>
                            <div class="mt-2 text-xs text-brand-muted">
                                Perfect for serious aspirants
                            </div>
                            <div class="mt-3 flex items-baseline gap-1">
                                <span
                                    class="text-4xl font-extrabold text-brand-heading"
                                >
                                    {{ isYearly ? 'Rs 1,125' : 'Rs 1,499' }}
                                </span>
                                <span class="text-xs text-brand-muted"
                                    >/ month</span
                                >
                            </div>

                            <div
                                class="mt-6 border-t border-zinc-100 pt-6 dark:border-zinc-800"
                            >
                                <div
                                    class="mb-3 text-xs font-bold text-brand-heading"
                                >
                                    Plan includes:
                                </div>
                                <ul
                                    class="space-y-2.5 text-xs text-zinc-700 dark:text-zinc-300"
                                >
                                    <li class="flex items-center gap-2">
                                        <span class="font-bold text-sky-600"
                                            >✓</span
                                        >
                                        <span>Unlimited Intelligence MCQs</span>
                                    </li>
                                    <li class="flex items-center gap-2">
                                        <span class="font-bold text-sky-600"
                                            >✓</span
                                        >
                                        <span
                                            >Full ISSB Modules (WAT, SRT,
                                            TAT)</span
                                        >
                                    </li>
                                    <li class="flex items-center gap-2">
                                        <span class="font-bold text-sky-600"
                                            >✓</span
                                        >
                                        <span
                                            >24/7 Socratic AI Mentor
                                            Access</span
                                        >
                                    </li>
                                    <li class="flex items-center gap-2">
                                        <span class="font-bold text-sky-600"
                                            >✓</span
                                        >
                                        <span
                                            >Timed Grand Simulations with
                                            Rank</span
                                        >
                                    </li>
                                    <li class="flex items-center gap-2">
                                        <span class="font-bold text-sky-600"
                                            >✓</span
                                        >
                                        <span
                                            >Instant Solution Proofs &amp;
                                            Notes</span
                                        >
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <div class="mt-8">
                            <Link
                                :href="
                                    isAuthenticated
                                        ? '/d/pma-initial-course'
                                        : '/register'
                                "
                                class="inline-flex w-full items-center justify-center rounded-full bg-zinc-950 py-3 text-xs font-semibold text-white shadow-md transition-all hover:bg-zinc-800 active:scale-95 dark:bg-white dark:text-zinc-950 dark:hover:bg-zinc-200"
                            >
                                Choose This Plan
                            </Link>
                        </div>
                    </div>

                    <!-- Tier 3: Pro / Academy Elite (Screenshot 5) -->
                    <div
                        class="flex flex-col justify-between rounded-3xl border border-zinc-200/80 bg-white p-8 shadow-xs dark:border-zinc-800 dark:bg-zinc-900/60"
                    >
                        <div>
                            <span
                                class="mb-4 inline-block rounded-full bg-zinc-100 px-3 py-1 text-xs font-semibold text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300"
                            >
                                Pro
                            </span>
                            <div class="text-xs text-brand-muted">
                                Perfect for CSS / PMS batches
                            </div>
                            <div class="mt-3 flex items-baseline gap-1">
                                <span
                                    class="text-4xl font-extrabold text-brand-heading"
                                >
                                    {{ isYearly ? 'Rs 2,999' : 'Rs 3,999' }}
                                </span>
                                <span class="text-xs text-brand-muted"
                                    >/ month</span
                                >
                            </div>

                            <div
                                class="mt-6 border-t border-zinc-100 pt-6 dark:border-zinc-800"
                            >
                                <div
                                    class="mb-3 text-xs font-bold text-brand-heading"
                                >
                                    Plan includes:
                                </div>
                                <ul
                                    class="space-y-2.5 text-xs text-brand-muted"
                                >
                                    <li class="flex items-center gap-2">
                                        <span class="font-bold text-sky-600"
                                            >✓</span
                                        >
                                        <span>All Cadet Pro Features</span>
                                    </li>
                                    <li class="flex items-center gap-2">
                                        <span class="font-bold text-sky-600"
                                            >✓</span
                                        >
                                        <span>2,500-Word Essay Evaluation</span>
                                    </li>
                                    <li class="flex items-center gap-2">
                                        <span class="font-bold text-sky-600"
                                            >✓</span
                                        >
                                        <span
                                            >10-Year Solved Past Papers
                                            Archive</span
                                        >
                                    </li>
                                    <li class="flex items-center gap-2">
                                        <span class="font-bold text-sky-600"
                                            >✓</span
                                        >
                                        <span
                                            >Rubric-Driven Teacher
                                            Feedback</span
                                        >
                                    </li>
                                    <li class="flex items-center gap-2">
                                        <span class="font-bold text-sky-600"
                                            >✓</span
                                        >
                                        <span>24/7 Dedicated Support Desk</span>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <div class="mt-8">
                            <Link
                                :href="
                                    isAuthenticated
                                        ? '/d/css-exam-2027'
                                        : '/register'
                                "
                                class="inline-flex w-full items-center justify-center rounded-full border border-sky-600/40 bg-white py-3 text-xs font-semibold text-sky-700 shadow-xs transition-all hover:border-sky-600 hover:bg-sky-50 active:scale-95 dark:bg-zinc-900 dark:text-sky-400"
                            >
                                Choose This Plan
                            </Link>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ═══════════════════════════════════════════════════════════════
                 8. FINAL ENROLLMENT BANNER (SenseLab Gradient Theme)
                 ═══════════════════════════════════════════════════════════════ -->
            <section
                class="border-t border-zinc-100 bg-zinc-50/50 px-4 py-20 text-center sm:px-6 lg:px-8 dark:border-zinc-800 dark:bg-zinc-900/20"
            >
                <div class="mx-auto max-w-4xl">
                    <span
                        class="mb-3 block text-xs font-bold tracking-widest text-sky-600 uppercase"
                    >
                        Enrollment Open for 2026 / 2027 Batches
                    </span>
                    <h2
                        class="text-3xl font-extrabold tracking-tight text-brand-heading sm:text-5xl"
                    >
                        Begin Your Official Preparation Journey Today.
                    </h2>
                    <p
                        class="mx-auto mt-4 max-w-xl text-base font-normal text-brand-muted sm:text-lg"
                    >
                        Access structured intelligence quizzes, psychological
                        evaluation workspaces, and full-length simulated grand
                        tests immediately.
                    </p>
                    <div class="mt-8 flex justify-center gap-4">
                        <Link
                            :href="
                                isAuthenticated
                                    ? '/d/pma-initial-course'
                                    : '/register'
                            "
                            class="inline-flex items-center gap-2 rounded-full px-8 py-3.5 text-sm font-semibold text-white shadow-md transition-all hover:shadow-lg active:scale-95"
                            style="background: var(--brand-gradient)"
                        >
                            <span>{{
                                isAuthenticated
                                    ? 'Launch Exam Engine →'
                                    : 'Create Free Account →'
                            }}</span>
                        </Link>
                    </div>
                </div>
            </section>
        </main>

        <!-- ═══════════════════════════════════════════════════════════════
             9. SENSELAB LUXURY FOOTER (Screenshot 1, 5)
             ═══════════════════════════════════════════════════════════════ -->
        <footer
            class="border-t border-zinc-100 bg-white py-12 text-xs text-zinc-500 dark:border-zinc-800 dark:bg-zinc-950"
        >
            <div
                class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-6 px-4 sm:flex-row sm:px-6 lg:px-8"
            >
                <div class="flex items-center gap-3">
                    <div
                        class="flex h-7 w-7 items-center justify-center rounded-lg bg-zinc-950 text-xs font-bold text-white dark:bg-white dark:text-zinc-950"
                    >
                        P
                    </div>
                    <span class="font-bold text-brand-heading"
                        >Pak-All-Tests</span
                    >
                    <span class="text-zinc-300 dark:text-zinc-700">•</span>
                    <span
                        >All Rights Reserved &copy;
                        {{ new Date().getFullYear() }}</span
                    >
                </div>

                <div class="flex flex-wrap items-center gap-6 font-medium">
                    <Link
                        href="/admin"
                        class="transition-colors hover:text-zinc-900 dark:hover:text-white"
                        >Admin Portal</Link
                    >
                    <Link
                        href="/login"
                        class="transition-colors hover:text-zinc-900 dark:hover:text-white"
                        >Candidate Sign In</Link
                    >
                    <Link
                        href="/register"
                        class="transition-colors hover:text-zinc-900 dark:hover:text-white"
                        >Student Registration</Link
                    >
                </div>
            </div>
        </footer>

        <!-- ═══════════════════════════════════════════════════════════════
             10. INTERACTIVE TEST SIMULATOR MODAL (Triggered by Play Button)
             ═══════════════════════════════════════════════════════════════ -->
        <div
            v-if="showSimModal"
            class="animate-fade-in fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm"
        >
            <div
                class="relative w-full max-w-lg rounded-3xl border border-zinc-200 bg-white p-6 shadow-2xl dark:border-zinc-800 dark:bg-zinc-950"
            >
                <div
                    class="flex items-center justify-between border-b border-zinc-100 pb-3 dark:border-zinc-800"
                >
                    <div>
                        <div
                            class="font-mono text-[11px] font-bold text-sky-600 uppercase"
                        >
                            Live Simulator Demo
                        </div>
                        <div
                            class="text-sm font-bold text-zinc-900 dark:text-white"
                        >
                            PMA Verbal Intelligence Test
                        </div>
                    </div>
                    <button
                        @click="showSimModal = false"
                        class="rounded-full p-1.5 text-zinc-400 hover:bg-zinc-100 hover:text-zinc-700 dark:hover:bg-zinc-800"
                    >
                        ✕
                    </button>
                </div>

                <div class="py-4">
                    <div class="mb-2 font-mono text-xs text-zinc-500">
                        Question 1 of 4 • Analogies
                    </div>
                    <p
                        class="mb-4 text-sm font-semibold text-zinc-900 dark:text-white"
                    >
                        Light is to Eye as Sound is to ?
                    </p>

                    <div class="space-y-2">
                        <button
                            v-for="(opt, idx) in simOptions"
                            :key="idx"
                            type="button"
                            @click="simAnswer = idx"
                            :class="[
                                'flex w-full items-center justify-between rounded-xl border p-3 text-left text-xs font-medium transition-all',
                                simAnswer === idx
                                    ? 'border-sky-500 bg-sky-50 font-bold text-sky-800 dark:bg-sky-950/40 dark:text-sky-300'
                                    : 'border-zinc-200 hover:bg-zinc-50 dark:border-zinc-800 dark:hover:bg-zinc-900',
                            ]"
                        >
                            <span
                                >{{ String.fromCharCode(65 + idx) }})
                                {{ opt }}</span
                            >
                            <span
                                v-if="simAnswer === idx"
                                class="font-bold text-sky-600"
                                >✓ Selected</span
                            >
                        </button>
                    </div>
                </div>

                <div
                    class="flex items-center justify-between border-t border-zinc-100 pt-3 dark:border-zinc-800"
                >
                    <span class="font-mono text-[11px] text-zinc-400"
                        >Timer: 19:42 remaining</span
                    >
                    <Link
                        :href="
                            isAuthenticated
                                ? '/d/pma-initial-course'
                                : '/register'
                        "
                        class="rounded-full px-4 py-2 text-xs font-semibold text-white shadow-sm"
                        style="background: var(--brand-gradient)"
                    >
                        Launch Full Engine →
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar {
    display: none;
}
.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>
