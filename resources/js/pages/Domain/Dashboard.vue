<script setup>
import { Head } from '@inertiajs/vue3';
import { ref, provide, defineAsyncComponent } from 'vue';
import AiTutorWidget from '@/components/ai/AiTutorWidget.vue';
import SduiRenderer from '@/components/sdui/SduiRenderer.vue';

defineProps({
    sdui: {
        type: Object,
        required: true,
    },
});

const activeModule = ref(null);
const activeQuestions = ref([]);
const activeQuestion = ref(null);
const isLoadingModule = ref(false);

const launchModule = async (moduleId) => {
    if (!moduleId) {
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
};

provide('sduiContext', {
    launchModule,
});

const McqPlayer = defineAsyncComponent(
    () => import('@/components/sdui/McqPlayer.vue'),
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
        <!-- Ambient Background Mesh Glow (SenseLab Atmosphere) -->
        <div class="pointer-events-none absolute inset-0 -z-10 overflow-hidden">
            <div
                class="absolute -top-32 -left-32 mesh-aura-cyan opacity-60"
            ></div>
            <div
                class="absolute top-10 -right-32 mesh-aura-warm opacity-40"
            ></div>
        </div>

        <!-- Hero Header & Track Overview (When browsing modules) -->
        <div
            v-if="!activeModule"
            class="relative flex flex-col gap-4 rounded-3xl border border-zinc-200/80 bg-white/80 p-6 sm:p-8 shadow-senselab-card backdrop-blur-md dark:border-zinc-800/80 dark:bg-zinc-950/80"
        >
            <div class="flex flex-wrap items-center gap-2.5">
                <div
                    class="inline-flex items-center gap-2 rounded-full border border-sky-200/90 bg-sky-50/80 px-3.5 py-1 text-xs font-semibold text-sky-800 shadow-xs dark:border-sky-900/60 dark:bg-sky-950/40 dark:text-sky-300"
                >
                    <span
                        class="flex h-2 w-2 rounded-full bg-sky-500 animate-pulse"
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
                    class="text-3xl sm:text-4xl font-extrabold tracking-tight text-senselab-heading"
                >
                    {{ sdui?.meta?.domain?.name }}
                </h1>
                <p
                    class="mt-2 max-w-3xl text-sm sm:text-base leading-relaxed text-brand-muted"
                >
                    Recursive self-improvement preparation workspace. Master
                    concepts through verified high-yield question banks,
                    interactive timed test sessions, and instant Socratic
                    rationale analysis.
                </p>
            </div>

            <!-- Syllabus Status Indicators (Human-readable metadata, zero raw DB paths) -->
            <div
                class="flex flex-wrap items-center gap-3 pt-2 border-t border-zinc-100 dark:border-zinc-800/80 text-xs text-brand-muted"
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

        <!-- Active Module Workspace -->
        <div v-if="activeModule" class="flex flex-1 flex-col gap-4">
            <!-- Sleek Back Navigation Banner -->
            <div
                class="flex items-center justify-between rounded-2xl border border-zinc-200/80 bg-white/90 p-3 sm:p-4 shadow-senselab-card backdrop-blur-md dark:border-zinc-800 dark:bg-zinc-950/80"
            >
                <button
                    type="button"
                    @click="closePlayer"
                    class="group inline-flex items-center gap-2 rounded-full border border-zinc-200/90 bg-white px-4 py-2 text-xs font-semibold text-zinc-800 shadow-xs hover:bg-zinc-50 hover:border-zinc-300 active:scale-95 transition-all dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200"
                >
                    <span
                        class="transition-transform duration-200 group-hover:-translate-x-0.5"
                        >←</span
                    >
                    <span>Back to Course Dashboard</span>
                </button>

                <div
                    class="flex items-center gap-2 text-xs font-medium text-brand-muted"
                >
                    <span
                        class="inline-block h-2 w-2 rounded-full bg-emerald-500 animate-pulse"
                    ></span>
                    <span class="hidden sm:inline">Active Module:</span>
                    <strong class="text-senselab-heading font-semibold">{{
                        activeModule.title
                    }}</strong>
                </div>
            </div>

            <!-- Player Components -->
            <McqPlayer
                v-if="
                    activeModule.module_type === 'mock_test' ||
                    activeModule.module_type === 'preparation'
                "
                :module="activeModule"
                :questions="activeQuestions"
                mode="practice"
                @question-change="(q) => (activeQuestion = q)"
            />
            <PdfViewer
                v-else-if="activeModule.module_type === 'pdf_repo'"
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

        <!-- Layout Rendering (When browsing) -->
        <div v-else class="flex-1">
            <div
                v-if="isLoadingModule"
                class="flex min-h-90 flex-col items-center justify-center gap-3 rounded-3xl border border-zinc-200/80 bg-white/60 p-8 shadow-senselab-card backdrop-blur-md dark:border-zinc-800 dark:bg-zinc-950/60"
            >
                <div
                    class="h-10 w-10 animate-spin rounded-full border-3 border-sky-500 border-t-transparent"
                ></div>
                <p class="text-sm font-semibold text-senselab-heading">
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
        />
    </div>
</template>
