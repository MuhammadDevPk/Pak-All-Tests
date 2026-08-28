<script setup>
import { ref, computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';

const page = usePage();
const isAuthenticated = computed(() => !!page.props.auth?.user);

const mobileMenuOpen = ref(false);
const simAnswer = ref(2);

const examCategories = [
    {
        title: 'Armed Forces & Defense',
        badge: 'PMA / ISSB / PAF / Navy',
        description: 'Complete selection pipeline testing from initial computerized intelligence tests to 4-day ISSB psychological evaluations.',
        slug: 'pma-initial-course',
        features: [
            'Verbal & Non-Verbal Matrix Reasoning',
            'Word Association (WAT) & Situation Reactions (SRT)',
            'Academic Selection Tests (GK, English, Physics)',
            'Military Board Oral Interview Prompts'
        ],
        actionLabel: 'Launch PMA Course',
    },
    {
        title: 'Civil Services (CSS / PMS)',
        badge: 'FPSC Competitive Examination',
        description: 'Rigorous preparation for compulsory and optional papers with rubric-driven subjective evaluations.',
        slug: 'css-exam-2027',
        features: [
            '2,500-Word English Essay Evaluation Workspace',
            'General Science & Ability (MPT) MCQs',
            'Past Papers Compilation (2015–2026 Archive)',
            'Current Affairs Monthly Dossiers'
        ],
        actionLabel: 'Explore CSS Curriculum',
    },
    {
        title: 'Higher Education & Entry Tests',
        badge: 'MDCAT / ECAT / NUST / GAT',
        description: 'High-yield subject banks engineered for competitive medical, engineering, and graduate admission tests.',
        slug: null,
        features: [
            'Chapter-Wise Analytical MCQs',
            'Simulated Full-Length Timed Grand Tests',
            'Detailed Step-by-Step Problem Explanations',
            'Accuracy & Topic Mastery Progress Rings'
        ],
        actionLabel: 'View Test Banks',
    }
];

const capabilities = [
    {
        number: '01',
        title: 'Server-Driven UI Architecture',
        description: 'Curricula, multi-phase steppers, and question modules update dynamically from the administrative kernel with sub-millisecond overhead.'
    },
    {
        number: '02',
        title: 'Multi-Modal Test Execution',
        description: 'Run timed computerized MCQs with active question maps, or upload handwritten essays and psychological responses for expert grading.'
    },
    {
        number: '03',
        title: 'Sub-Millisecond Query Response',
        description: 'Built on PostgreSQL hierarchical ltree paths and Redis caching layers for instant page delivery under heavy national test traffic.'
    },
    {
        number: '04',
        title: 'Real-Time Performance Diagnostics',
        description: 'Continuous scoring feedback, passing threshold benchmarks, and explanation breakdowns upon every test submission.'
    }
];
</script>

<template>
    <Head title="Pak-All-Tests — National Examination & Defense Prep Portal" />

    <div class="min-h-screen bg-background text-foreground flex flex-col selection:bg-primary/20 selection:text-primary">
        <!-- Site Navigation Header -->
        <header class="sticky top-0 z-40 w-full border-b border-sidebar-border/70 bg-background/90 backdrop-blur-md">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
                <!-- Brand Crest & Monogram -->
                <Link href="/" class="flex items-center gap-3 group">
                    <div class="h-9 w-9 rounded-lg bg-primary flex items-center justify-center text-primary-foreground font-bold text-base shadow-xs transition-transform group-hover:scale-105">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 2L2 7l10 5 10-5-10-5z" />
                            <path d="M2 17l10 5 10-5" />
                            <path d="M2 12l10 5 10-5" />
                        </svg>
                    </div>
                    <div class="flex flex-col">
                        <span class="font-bold text-base tracking-tight text-foreground leading-tight">Pak-All-Tests</span>
                        <span class="text-[10px] font-mono uppercase tracking-widest text-muted-foreground">National Exam Portal</span>
                    </div>
                </Link>

                <!-- Desktop Nav Links -->
                <nav class="hidden md:flex items-center gap-8 text-sm font-medium text-muted-foreground">
                    <a href="#exams" class="hover:text-foreground transition-colors">Examinations</a>
                    <a href="#simulator" class="hover:text-foreground transition-colors">Test Engine</a>
                    <a href="#architecture" class="hover:text-foreground transition-colors">Platform Architecture</a>
                    <Link href="/admin" class="hover:text-foreground transition-colors">Admin Portal</Link>
                </nav>

                <!-- Auth Action Buttons -->
                <div class="hidden sm:flex items-center gap-3">
                    <template v-if="isAuthenticated">
                        <Link
                            href="/dashboard"
                            class="inline-flex items-center justify-center rounded-lg bg-primary px-4 py-2 text-xs font-semibold text-primary-foreground shadow-xs hover:bg-primary/90 transition-all active:scale-[0.98]"
                        >
                            Candidate Dashboard →
                        </Link>
                    </template>
                    <template v-else>
                        <Link
                            href="/login"
                            class="rounded-lg px-3 py-2 text-xs font-medium text-muted-foreground hover:text-foreground transition-colors"
                        >
                            Sign In
                        </Link>
                        <Link
                            href="/register"
                            class="inline-flex items-center justify-center rounded-lg bg-primary px-4 py-2 text-xs font-semibold text-primary-foreground shadow-xs hover:bg-primary/90 transition-all active:scale-[0.98]"
                        >
                            Enroll Now
                        </Link>
                    </template>
                </div>

                <!-- Mobile Hamburger Button -->
                <button
                    type="button"
                    @click="mobileMenuOpen = !mobileMenuOpen"
                    class="md:hidden p-2 rounded-lg text-muted-foreground hover:text-foreground hover:bg-sidebar-accent transition-colors"
                    aria-label="Toggle Navigation"
                >
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path v-if="!mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                        <path v-else stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Mobile Navigation Drawer -->
            <div v-if="mobileMenuOpen" class="md:hidden border-b border-sidebar-border bg-background px-4 pt-2 pb-6 flex flex-col gap-4">
                <nav class="flex flex-col gap-2 text-sm font-medium">
                    <a href="#exams" @click="mobileMenuOpen = false" class="py-2 text-foreground">Examinations</a>
                    <a href="#simulator" @click="mobileMenuOpen = false" class="py-2 text-foreground">Test Engine</a>
                    <a href="#architecture" @click="mobileMenuOpen = false" class="py-2 text-foreground">Platform Architecture</a>
                    <Link href="/admin" class="py-2 text-foreground">Admin Portal</Link>
                </nav>
                <div class="pt-4 border-t border-sidebar-border flex flex-col gap-2">
                    <template v-if="isAuthenticated">
                        <Link
                            href="/dashboard"
                            class="w-full text-center rounded-lg bg-primary py-2.5 text-xs font-semibold text-primary-foreground"
                        >
                            Candidate Dashboard
                        </Link>
                    </template>
                    <template v-else>
                        <Link
                            href="/login"
                            class="w-full text-center rounded-lg border border-sidebar-border py-2 text-xs font-medium text-foreground"
                        >
                            Sign In
                        </Link>
                        <Link
                            href="/register"
                            class="w-full text-center rounded-lg bg-primary py-2.5 text-xs font-semibold text-primary-foreground"
                        >
                            Enroll Now
                        </Link>
                    </template>
                </div>
            </div>
        </header>

        <main class="flex-1">
            <!-- Hero Section -->
            <section class="relative pt-12 pb-16 md:pt-20 md:pb-24 overflow-hidden border-b border-sidebar-border/60">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
                        <!-- Hero Value Proposition -->
                        <div class="lg:col-span-7 flex flex-col gap-6 text-left">
                            <div class="flex items-center gap-2">
                                <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                <span class="text-xs font-mono uppercase tracking-wider text-muted-foreground">National Exam Preparation Engine</span>
                            </div>

                            <h1 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold tracking-tight text-foreground leading-[1.15]">
                                Master Pakistan's High-Stakes Competitive & Military Examinations.
                            </h1>

                            <p class="text-base sm:text-lg text-muted-foreground leading-relaxed max-w-2xl">
                                Full-length timed initial test simulators, ISSB psychological assessment modules (WAT, SRT), and CSS essay evaluation environments. Driven by a live Server-Driven UI curriculum.
                            </p>

                            <!-- CTAs -->
                            <div class="pt-2 flex flex-wrap items-center gap-4">
                                <Link
                                    v-if="isAuthenticated"
                                    href="/dashboard"
                                    class="inline-flex items-center justify-center rounded-lg bg-primary px-6 py-3 text-sm font-semibold text-primary-foreground shadow-sm hover:bg-primary/90 transition-all active:scale-[0.98]"
                                >
                                    Go to My Dashboard →
                                </Link>
                                <Link
                                    v-else
                                    href="/register"
                                    class="inline-flex items-center justify-center rounded-lg bg-primary px-6 py-3 text-sm font-semibold text-primary-foreground shadow-sm hover:bg-primary/90 transition-all active:scale-[0.98]"
                                >
                                    Start Test Preparation →
                                </Link>

                                <a
                                    href="#simulator"
                                    class="inline-flex items-center justify-center rounded-lg border border-sidebar-border bg-card px-5 py-3 text-sm font-medium text-foreground hover:bg-sidebar-accent transition-colors"
                                >
                                    Live Test Simulator Preview ↓
                                </a>
                            </div>

                            <!-- Capability Highlights -->
                            <div class="pt-6 border-t border-sidebar-border/60 grid grid-cols-3 gap-4 text-left">
                                <div>
                                    <span class="block text-xl font-bold font-mono text-foreground">4-Phase</span>
                                    <span class="text-xs text-muted-foreground">PMA & ISSB Pipeline</span>
                                </div>
                                <div>
                                    <span class="block text-xl font-bold font-mono text-foreground">&lt;1.2ms</span>
                                    <span class="text-xs text-muted-foreground">Grading Execution</span>
                                </div>
                                <div>
                                    <span class="block text-xl font-bold font-mono text-foreground">100%</span>
                                    <span class="text-xs text-muted-foreground">Dynamic SDUI Driven</span>
                                </div>
                            </div>
                        </div>

                        <!-- Live Interactive Simulator Preview Card -->
                        <div class="lg:col-span-5" id="simulator">
                            <div class="rounded-xl border border-sidebar-border bg-card p-5 sm:p-6 shadow-sm flex flex-col gap-5">
                                <!-- Simulator Card Header -->
                                <div class="flex items-center justify-between border-b border-sidebar-border/50 pb-3">
                                    <div class="flex flex-col">
                                        <span class="text-xs font-mono uppercase tracking-wider text-muted-foreground">Live MCQ Simulator</span>
                                        <span class="text-sm font-bold text-foreground">PMA Initial Verbal Intelligence</span>
                                    </div>
                                    <div class="flex items-center gap-2 rounded-lg bg-primary/10 px-3 py-1 font-mono text-xs font-bold text-primary">
                                        <span>⏱️ 19:42</span>
                                    </div>
                                </div>

                                <!-- Question Map Navigator Ribbon -->
                                <div class="flex flex-wrap items-center gap-1.5 border-b border-sidebar-border/40 pb-3">
                                    <span class="text-[11px] font-mono text-muted-foreground mr-1">Q-Map:</span>
                                    <button
                                        v-for="num in [1, 2, 3, 4, 5, 6]"
                                        :key="num"
                                        type="button"
                                        :class="[
                                            'flex h-6 w-6 items-center justify-center rounded text-xs font-mono font-medium transition-colors',
                                            num === 1
                                                ? 'ring-2 ring-primary ring-offset-1 bg-primary text-primary-foreground font-bold'
                                                : num <= 3
                                                    ? 'bg-primary/20 text-primary'
                                                    : 'bg-sidebar-accent text-muted-foreground'
                                        ]"
                                    >
                                        {{ num }}
                                    </button>
                                </div>

                                <!-- Simulated Question Content -->
                                <div class="flex flex-col gap-3">
                                    <span class="text-xs font-semibold text-muted-foreground font-mono">Question 1 of 4 • Analogies</span>
                                    <p class="text-sm font-medium text-foreground leading-relaxed">
                                        Light is to Eye as Sound is to ?
                                    </p>

                                    <!-- Selectable Options -->
                                    <div class="flex flex-col gap-2 pt-1">
                                        <button
                                            v-for="(option, idx) in ['Ear', 'Nose', 'Tongue', 'Hand']"
                                            :key="idx"
                                            type="button"
                                            @click="simAnswer = idx"
                                            :class="[
                                                'flex items-center justify-between rounded-lg border p-3 text-left text-xs font-medium transition-all',
                                                simAnswer === idx
                                                    ? 'border-primary bg-primary/10 font-semibold text-primary'
                                                    : 'border-sidebar-border hover:border-sidebar-border/80 bg-sidebar/20 text-foreground'
                                            ]"
                                        >
                                            <div class="flex items-center gap-2.5">
                                                <span class="flex h-5 w-5 items-center justify-center rounded-sm bg-sidebar-accent text-[10px] font-mono font-bold text-muted-foreground">
                                                    {{ String.fromCharCode(65 + idx) }}
                                                </span>
                                                <span>{{ option }}</span>
                                            </div>
                                            <div v-if="simAnswer === idx" class="h-2 w-2 rounded-full bg-primary"></div>
                                        </button>
                                    </div>
                                </div>

                                <!-- Simulator Action Footer -->
                                <div class="flex items-center justify-between pt-2 border-t border-sidebar-border/50 text-xs">
                                    <span class="text-muted-foreground font-mono text-[11px]">Instant Verified Explanations</span>
                                    <Link
                                        :href="isAuthenticated ? '/d/pma-initial-course' : '/register'"
                                        class="font-semibold text-primary hover:underline inline-flex items-center gap-1"
                                    >
                                        Launch Full Exam Engine →
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Exam Domains Showcase Grid -->
            <section id="exams" class="py-16 md:py-24 border-b border-sidebar-border/60 bg-sidebar/5">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex flex-col gap-3 max-w-2xl mb-12">
                        <span class="text-xs font-mono uppercase tracking-wider text-muted-foreground">Curriculum Directory</span>
                        <h2 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-foreground">
                            Targeted Programs for Pakistan's Leading Examinations
                        </h2>
                        <p class="text-sm sm:text-base text-muted-foreground leading-relaxed">
                            Each domain features custom server-driven flows with tailored steppers, multi-tier tab layouts, and automated scoring.
                        </p>
                    </div>

                    <!-- Category Cards Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div
                            v-for="(cat, index) in examCategories"
                            :key="index"
                            class="flex flex-col justify-between rounded-xl border border-sidebar-border bg-card p-6 shadow-xs hover:border-sidebar-border/90 hover:shadow-md transition-all"
                        >
                            <div>
                                <div class="flex items-center justify-between gap-2 mb-3">
                                    <span class="text-[11px] font-mono uppercase tracking-wider font-semibold text-primary">
                                        {{ cat.badge }}
                                    </span>
                                </div>
                                <h3 class="text-lg font-bold text-foreground mb-2">
                                    {{ cat.title }}
                                </h3>
                                <p class="text-xs text-muted-foreground leading-relaxed mb-5">
                                    {{ cat.description }}
                                </p>

                                <!-- Checklist Features -->
                                <ul class="flex flex-col gap-2.5 pt-4 border-t border-sidebar-border/50 text-xs text-foreground/90 mb-6">
                                    <li v-for="(feat, fIdx) in cat.features" :key="fIdx" class="flex items-start gap-2">
                                        <span class="text-primary font-bold">✓</span>
                                        <span>{{ feat }}</span>
                                    </li>
                                </ul>
                            </div>

                            <div>
                                <Link
                                    v-if="cat.slug && isAuthenticated"
                                    :href="`/d/${cat.slug}`"
                                    class="w-full inline-flex items-center justify-center rounded-lg bg-primary py-2.5 text-xs font-semibold text-primary-foreground shadow-xs hover:bg-primary/90 transition-all active:scale-[0.98]"
                                >
                                    {{ cat.actionLabel }} →
                                </Link>
                                <Link
                                    v-else-if="cat.slug"
                                    href="/register"
                                    class="w-full inline-flex items-center justify-center rounded-lg bg-primary py-2.5 text-xs font-semibold text-primary-foreground shadow-xs hover:bg-primary/90 transition-all active:scale-[0.98]"
                                >
                                    {{ cat.actionLabel }} →
                                </Link>
                                <button
                                    v-else
                                    type="button"
                                    class="w-full inline-flex items-center justify-center rounded-lg border border-sidebar-border bg-sidebar/50 py-2.5 text-xs font-medium text-muted-foreground cursor-not-allowed"
                                >
                                    Curriculum In Deployment
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Platform Architecture & Engineering Principles -->
            <section id="architecture" class="py-16 md:py-24 border-b border-sidebar-border/60">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-start">
                        <div class="lg:col-span-5 flex flex-col gap-4">
                            <span class="text-xs font-mono uppercase tracking-wider text-muted-foreground">Engineering Standard</span>
                            <h2 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-foreground leading-tight">
                                Built for Academic Rigor & Military Precision.
                            </h2>
                            <p class="text-sm text-muted-foreground leading-relaxed">
                                No bloated client state or fragile frontends. Every candidate dashboard, stepper state, and test configuration is dynamically resolved by our Server-Driven UI (SDUI) pipeline in sub-milliseconds.
                            </p>

                            <div class="mt-4 rounded-xl border border-sidebar-border bg-sidebar/30 p-5 flex flex-col gap-3 font-mono text-xs text-muted-foreground">
                                <div class="flex items-center justify-between text-foreground">
                                    <span class="font-bold">Execution Pipeline:</span>
                                    <span class="text-emerald-500">Active</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span>Database Kernel:</span>
                                    <span class="text-foreground">PostgreSQL 16 + ltree</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span>Rendering Engine:</span>
                                    <span class="text-foreground">Inertia.js + Vue 3 SDUI</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span>Cache Layer:</span>
                                    <span class="text-foreground">Redis Model Cache</span>
                                </div>
                            </div>
                        </div>

                        <!-- 4 Architectural Pillars -->
                        <div class="lg:col-span-7 grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <div
                                v-for="cap in capabilities"
                                :key="cap.number"
                                class="flex flex-col gap-2 rounded-xl border border-sidebar-border bg-card p-6"
                            >
                                <span class="text-xs font-mono font-bold text-primary">{{ cap.number }}</span>
                                <h3 class="text-base font-bold text-foreground">{{ cap.title }}</h3>
                                <p class="text-xs text-muted-foreground leading-relaxed">
                                    {{ cap.description }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Final Enrollment CTA Banner -->
            <section class="py-16 md:py-20 bg-sidebar/10">
                <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center flex flex-col items-center gap-6">
                    <span class="text-xs font-mono uppercase tracking-wider text-muted-foreground">Enrollment Open for 2026 / 2027 Batches</span>
                    <h2 class="text-2xl sm:text-4xl font-extrabold tracking-tight text-foreground">
                        Begin Your Official Preparation Journey Today.
                    </h2>
                    <p class="text-sm sm:text-base text-muted-foreground leading-relaxed max-w-xl">
                        Access structured intelligence quizzes, psychological evaluation workspaces, and full-length simulated grand tests immediately.
                    </p>
                    <div class="flex flex-wrap items-center justify-center gap-4 pt-2">
                        <Link
                            v-if="isAuthenticated"
                            href="/dashboard"
                            class="rounded-lg bg-primary px-8 py-3 text-sm font-semibold text-primary-foreground shadow-xs hover:bg-primary/90 transition-all active:scale-[0.98]"
                        >
                            Open Candidate Portal →
                        </Link>
                        <Link
                            v-else
                            href="/register"
                            class="rounded-lg bg-primary px-8 py-3 text-sm font-semibold text-primary-foreground shadow-xs hover:bg-primary/90 transition-all active:scale-[0.98]"
                        >
                            Create Student Account →
                        </Link>
                    </div>
                </div>
            </section>
        </main>

        <!-- Institutional Footer -->
        <footer class="border-t border-sidebar-border/70 bg-background text-muted-foreground text-xs py-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-6">
                <div class="flex items-center gap-3">
                    <div class="h-6 w-6 rounded bg-primary flex items-center justify-center text-primary-foreground font-bold text-xs">
                        P
                    </div>
                    <span class="font-semibold text-foreground">Pak-All-Tests Portal</span>
                    <span class="text-muted-foreground/60">•</span>
                    <span>All Rights Reserved &copy; {{ new Date().getFullYear() }}</span>
                </div>

                <div class="flex flex-wrap items-center gap-6">
                    <Link href="/admin" class="hover:text-foreground transition-colors">Admin Portal</Link>
                    <Link href="/login" class="hover:text-foreground transition-colors">Candidate Sign In</Link>
                    <Link href="/register" class="hover:text-foreground transition-colors">Student Registration</Link>
                </div>
            </div>
        </footer>
    </div>
</template>
