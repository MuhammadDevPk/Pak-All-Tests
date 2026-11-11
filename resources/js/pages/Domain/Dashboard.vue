<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed, provide, defineAsyncComponent } from 'vue';
import AiTutorWidget from '@/components/ai/AiTutorWidget.vue';
import GuestBanner from '@/components/auth/GuestBanner.vue';
import ReferralCard from '@/components/referral/ReferralCard.vue';
import SduiRenderer from '@/components/sdui/SduiRenderer.vue';

const props = defineProps({
    sdui: {
        type: Object,
        required: true,
    },
    referral: {
        type: Object,
        default: null,
    },
    guest: {
        type: Object,
        default: () => ({ is_guest: false }),
    },
    hasAccess: {
        type: Boolean,
        default: true,
    },
});

const isLocked = computed(() => {
    if (
        props.guest?.is_guest &&
        Boolean(
            props.guest?.is_expired ||
            (props.guest?.expires_in_hours !== null &&
                props.guest?.expires_in_hours <= 0),
        )
    ) {
        return true;
    }

    if (props.hasAccess === false) {
        return true;
    }

    return false;
});

const lockedBannerRef = ref(null);

const registerUrl = computed(() => {
    if (typeof window === 'undefined') {
        return '/register';
    }

    const currentPath = window.location.pathname + window.location.search;

    return `/register?redirect=${encodeURIComponent(currentPath)}`;
});

const lockedNotice = computed(() => {
    if (props.guest?.is_guest) {
        return {
            title: '24-Hour Guest Window Completed',
            message:
                'Your 24-hour guest window has completed. Register your free account now to unlock your 7-Day Free Trial and keep all your test progress!',
            actionLabel: 'Register to Unlock (7 Days Free) →',
            actionType: 'register',
        };
    }

    return {
        title: 'Active Subscription Required',
        message:
            'Your access to this course has ended. Invite classmates with your referral link to earn +7 days free access for each friend who registers, or activate your subscription.',
        actionLabel: 'Invite Friends (+7 Days) →',
        actionType: 'referral',
    };
});

const handleLockedContentClick = () => {
    if (props.guest?.is_guest) {
        router.visit(registerUrl.value);

        return;
    }

    document
        .getElementById('referral-section')
        ?.scrollIntoView({ behavior: 'smooth' });
};

const activeModule = ref(null);
const activeQuestions = ref([]);
const activeQuestion = ref(null);
const pageContext = ref(null);
const isLoadingModule = ref(false);

const launchModule = async (moduleId) => {
    if (!moduleId) {
        return;
    }

    if (isLocked.value) {
        handleLockedContentClick();

        return;
    }

    isLoadingModule.value = true;

    try {
        const response = await fetch(`/api/modules/${moduleId}`);
        const data = await response.json();

        if (data.status === 'success') {
            activeModule.value = data.module;
            activeQuestions.value = data.questions || [];
        } else {
            alert(data.message || 'Failed to load module');
        }
    } catch (err) {
        console.error('Error fetching module:', err);
        alert('Network error loading module. Please try again.');
    } finally {
        isLoadingModule.value = false;
    }
};

const closePlayer = () => {
    activeModule.value = null;
    activeQuestions.value = [];
    activeQuestion.value = null;
    pageContext.value = null;
};

const viewFile = (file) => {
    if (!file) {
        return;
    }

    if (isLocked.value) {
        handleLockedContentClick();

        return;
    }

    const fileUrl = file.fileUrl || file.file_url;

    if (!fileUrl) {
        return;
    }

    activeModule.value = {
        id: file.moduleId || 'file-viewer',
        title: file.title || 'Document Viewer',
        display_mode: 'pdf_repo',
        module_type: 'pdf_repo',
        content_meta: {
            file_url: fileUrl,
            description: file.description || '',
            files: file.files || [
                {
                    title: file.title || 'Document',
                    file_url: fileUrl,
                    file_type:
                        file.fileType || file.file_type || 'PDF Document',
                },
            ],
        },
    };
    activeQuestions.value = [];
    activeQuestion.value = null;
    pageContext.value = null;
};

provide('sduiContext', {
    launchModule,
    viewFile,
    isLocked,
});

const McqPlayer = defineAsyncComponent(
    () => import('@/components/sdui/McqPlayer.vue'),
);
const StudyQaPlayer = defineAsyncComponent(
    () => import('@/components/sdui/StudyQaPlayer.vue'),
);
const PdfViewer = defineAsyncComponent(
    () => import('@/components/sdui/PdfViewer.vue'),
);
const SubjectiveUpload = defineAsyncComponent(
    () => import('@/components/sdui/SubjectiveUpload.vue'),
);
</script>

<template>
    <Head :title="sdui?.meta?.domain?.name || 'Course Dashboard'" />

    <div class="relative flex h-full flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8">
        <!-- Guest Mode Alert & Conversion Banner -->
        <GuestBanner :guest="guest" />

        <!-- Ambient Background Mesh Glow (SenseLab Atmosphere) -->
        <div class="pointer-events-none absolute inset-0 -z-10 overflow-hidden">
            <div
                class="mesh-aura-cyan absolute -top-32 -left-32 opacity-60"
            ></div>
            <div
                class="mesh-aura-warm absolute top-10 -right-32 opacity-40"
            ></div>
        </div>

        <!-- Hero Header & Track Overview (When browsing modules) -->
        <div
            v-if="!activeModule"
            class="shadow-senselab-card relative flex flex-col gap-4 rounded-3xl border border-zinc-200/80 bg-white/80 p-6 backdrop-blur-md sm:p-8 dark:border-zinc-800/80 dark:bg-zinc-950/80"
        >
            <div class="flex flex-wrap items-center gap-2.5">
                <div
                    class="inline-flex items-center gap-2 rounded-full border border-sky-200/90 bg-sky-50/80 px-3.5 py-1 text-xs font-semibold text-sky-800 shadow-xs dark:border-sky-900/60 dark:bg-sky-950/40 dark:text-sky-300"
                >
                    <span
                        class="flex h-2 w-2 animate-pulse rounded-full bg-sky-500"
                    ></span>
                    <span>Official Preparation Track</span>
                    <span class="text-sky-300 dark:text-sky-700">•</span>
                    <span>National Defense & Competitive Syllabi</span>
                </div>

                <div
                    class="inline-flex items-center gap-1.5 rounded-full border border-zinc-200/80 bg-zinc-100/70 px-3 py-1 text-xs font-medium text-zinc-700 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-300"
                >
                    <span>⚡ Spaced Repetition Ready</span>
                </div>
            </div>

            <div>
                <h1
                    class="text-senselab-heading text-3xl font-extrabold tracking-tight sm:text-4xl"
                >
                    {{ sdui?.meta?.domain?.name }}
                </h1>
                <p
                    class="mt-2 max-w-3xl text-sm leading-relaxed text-brand-muted sm:text-base"
                >
                    Recursive self-improvement preparation workspace. Master
                    concepts through verified high-yield question banks,
                    interactive timed test sessions, and instant Socratic
                    rationale analysis.
                </p>
            </div>

            <!-- Syllabus Status Indicators (Human-readable metadata, zero raw DB paths) -->
            <div
                class="flex flex-wrap items-center gap-3 border-t border-zinc-100 pt-2 text-xs text-brand-muted dark:border-zinc-800/80"
            >
                <div class="flex items-center gap-1.5 font-medium">
                    <span class="text-emerald-500">✓</span>
                    <span>Syllabus Aligned</span>
                </div>
                <span class="text-zinc-300 dark:text-zinc-700">•</span>
                <div class="flex items-center gap-1.5">
                    <span
                        >Target Passing Score:
                        <strong class="text-senselab-heading font-semibold"
                            >60%</strong
                        ></span
                    >
                </div>
                <span class="text-zinc-300 dark:text-zinc-700">•</span>
                <div class="flex items-center gap-1.5">
                    <span
                        >Graduation Rule:
                        <strong class="text-senselab-heading font-semibold"
                            >2-Strike Threshold Mastery</strong
                        ></span
                    >
                </div>
            </div>
        </div>

        <!-- Viral Referral Progress Card -->
        <ReferralCard v-if="!activeModule && referral" :referral="referral" />

        <!-- Active Module Workspace -->
        <div v-if="activeModule" class="flex flex-1 flex-col gap-4">
            <!-- Sleek Back Navigation Banner -->
            <div
                class="shadow-senselab-card flex items-center justify-between gap-2.5 rounded-2xl border border-zinc-200/80 bg-white/90 p-3 backdrop-blur-md sm:gap-4 sm:p-4 dark:border-zinc-800 dark:bg-zinc-950/80"
            >
                <button
                    type="button"
                    @click="closePlayer"
                    class="group inline-flex shrink-0 items-center gap-1.5 rounded-full border border-zinc-200/90 bg-white px-3 py-1.5 text-xs font-semibold whitespace-nowrap text-zinc-800 shadow-xs transition-all hover:border-zinc-300 hover:bg-zinc-50 active:scale-95 sm:gap-2 sm:px-4 sm:py-2 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200"
                >
                    <span
                        class="transition-transform duration-200 group-hover:-translate-x-0.5"
                        >←</span
                    >
                    <span
                        >Back<span class="hidden sm:inline">
                            to Course Dashboard</span
                        ></span
                    >
                </button>

                <div
                    class="flex min-w-0 items-center gap-1.5 text-xs font-medium text-brand-muted"
                >
                    <span
                        class="inline-block h-2 w-2 shrink-0 animate-pulse rounded-full bg-emerald-500"
                    ></span>
                    <span class="hidden sm:inline">Active Module:</span>
                    <strong
                        class="text-senselab-heading truncate font-semibold"
                        >{{ activeModule.title }}</strong
                    >
                </div>
            </div>

            <!-- Player Components -->
            <StudyQaPlayer
                v-if="
                    activeModule.display_mode === 'study_qa' ||
                    activeModule.display_mode === 'reading_guide'
                "
                :module="activeModule"
                :questions="activeQuestions"
                @question-change="
                    (q) => {
                        activeQuestion = q;
                        if (q?.page_context) pageContext = q.page_context;
                    }
                "
                @page-context-change="(ctx) => (pageContext = ctx)"
            />
            <McqPlayer
                v-else-if="
                    activeModule.display_mode === 'mcq_test' ||
                    activeModule.module_type === 'mock_test' ||
                    activeModule.module_type === 'preparation'
                "
                :module="activeModule"
                :questions="activeQuestions"
                mode="practice"
                @question-change="
                    (q) => {
                        activeQuestion = q;
                        if (q?.page_context) pageContext = q.page_context;
                    }
                "
                @page-context-change="(ctx) => (pageContext = ctx)"
            />
            <PdfViewer
                v-else-if="
                    activeModule.display_mode === 'pdf_repo' ||
                    activeModule.module_type === 'pdf_repo'
                "
                :title="activeModule.title"
                :fileUrl="activeModule.content_meta?.file_url"
                :description="activeModule.content_meta?.description"
                :files="activeModule.content_meta?.files || []"
            />
            <SubjectiveUpload
                v-else
                :title="activeModule.title"
                :instructions="
                    activeModule.content_meta?.instructions ||
                    activeModule.content_meta?.description
                "
                :maxWordCount="
                    activeModule.content_meta?.max_word_count || 1000
                "
            />
        </div>

        <!-- Locked Access Alert Banner (Shown above content when guest pass is completed or access is inactive) -->
        <div
            v-if="isLocked && !activeModule"
            ref="lockedBannerRef"
            class="shadow-senselab-card flex flex-wrap items-center justify-between gap-3.5 rounded-2xl border border-rose-300/80 bg-gradient-to-r from-rose-500/10 via-amber-500/5 to-rose-500/10 p-4 backdrop-blur-md sm:px-6 dark:border-rose-500/40 dark:from-rose-950/40 dark:via-zinc-950/60 dark:to-rose-950/30"
        >
            <div class="flex items-center gap-3">
                <span
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-rose-500/15 text-xl font-bold text-rose-600 dark:bg-rose-500/20 dark:text-rose-400"
                >
                    🔒
                </span>
                <div>
                    <div class="flex items-center gap-2">
                        <h4
                            class="text-xs font-bold tracking-wide text-rose-800 uppercase dark:text-rose-300"
                        >
                            {{ lockedNotice.title }}
                        </h4>
                        <span
                            class="rounded-full bg-rose-100 px-2 py-0.5 text-[10px] font-semibold text-rose-800 dark:bg-rose-900/60 dark:text-rose-200"
                        >
                            Content Locked
                        </span>
                    </div>
                    <p class="mt-0.5 text-xs text-zinc-700 dark:text-zinc-300">
                        {{ lockedNotice.message }}
                    </p>
                </div>
            </div>

            <Link
                v-if="lockedNotice.actionType === 'register'"
                :href="registerUrl"
                class="inline-flex cursor-pointer items-center justify-center gap-1.5 rounded-xl bg-rose-600 px-4 py-2 text-xs font-semibold text-white shadow-xs transition hover:bg-rose-500 active:scale-95"
            >
                <span>{{ lockedNotice.actionLabel }}</span>
            </Link>
            <button
                v-else
                type="button"
                @click="handleLockedContentClick"
                class="inline-flex cursor-pointer items-center justify-center gap-1.5 rounded-xl bg-rose-600 px-4 py-2 text-xs font-semibold text-white shadow-xs transition hover:bg-rose-500 active:scale-95"
            >
                <span>{{ lockedNotice.actionLabel }}</span>
            </button>
        </div>

        <!-- Layout Rendering (When browsing) -->
        <div v-if="!activeModule" class="flex-1">
            <div
                v-if="isLoadingModule"
                class="shadow-senselab-card flex min-h-90 flex-col items-center justify-center gap-3 rounded-3xl border border-zinc-200/80 bg-white/60 p-8 backdrop-blur-md dark:border-zinc-800 dark:bg-zinc-950/60"
            >
                <div
                    class="h-10 w-10 animate-spin rounded-full border-3 border-sky-500 border-t-transparent"
                ></div>
                <p class="text-senselab-heading text-sm font-semibold">
                    Loading module curriculum...
                </p>
                <p class="text-xs text-brand-muted">
                    Preparing high-yield questions and rationale keys
                </p>
            </div>
            <SduiRenderer v-else-if="sdui?.layout" :node="sdui.layout" />
        </div>

        <!-- AI Socratic Tutor Floating Action Button & Sliding Drawer -->
        <AiTutorWidget
            v-if="sdui?.meta?.domain"
            :domain="sdui.meta.domain"
            :active-module="activeModule"
            :active-question="activeQuestion"
            :page-context="pageContext"
        />
    </div>
</template>
