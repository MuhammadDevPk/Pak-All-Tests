<script setup>
import { computed, inject } from 'vue';

const props = defineProps({
    columns: {
        type: [Number, String],
        default: 3,
    },
    items: {
        type: Array,
        required: true,
        default: () => [],
    },
});

const gridClass = computed(() => {
    const cols = Number(props.columns);

    if (cols === 1) {
        return 'grid-cols-1';
    }

    if (cols === 2) {
        return 'grid-cols-1 md:grid-cols-2';
    }

    if (cols === 4) {
        return 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-4';
    }

    return 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 xl:grid-cols-3';
});

const sduiContext = inject('sduiContext', null);

const startItem = (item) => {
    const moduleId = item.moduleId || item.module?.id;

    if (moduleId && sduiContext) {
        sduiContext.launchModule(moduleId);
    }
};

const getFeatureChip = (item) => {
    const mode =
        item.displayMode || item.display_mode || item.module?.display_mode;

    if (mode === 'study_qa') {
        return 'Interactive Study & Q&A';
    }

    if (
        mode === 'reading_guide' ||
        mode === 'pdf_repo' ||
        item.type === 'pdf_repo'
    ) {
        return 'Official Guide & PDF';
    }

    if (mode === 'essay' || item.type === 'subjective') {
        return 'Subjective Prompt';
    }

    return 'Threshold Mastery';
};

const getSubjectMeta = (title) => {
    const t = (title || '').toLowerCase();

    if (t.includes('islam') || t.includes('quran') || t.includes('hadith')) {
        return {
            icon: '🕌',
            themeBg: 'bg-emerald-500/10 dark:bg-emerald-950/40',
            themeBorder: 'border-emerald-500/30 dark:border-emerald-800/40',
            themeText: 'text-emerald-600 dark:text-emerald-400',
            badgeBg: 'bg-emerald-50 dark:bg-emerald-950/60',
            badgeText: 'text-emerald-700 dark:text-emerald-300',
            tag: 'Core Faith & Sharia',
        };
    }

    if (
        t.includes('history') ||
        t.includes('movement') ||
        t.includes('pakistan')
    ) {
        return {
            icon: '📜',
            themeBg: 'bg-amber-500/10 dark:bg-amber-950/40',
            themeBorder: 'border-amber-500/30 dark:border-amber-800/40',
            themeText: 'text-amber-600 dark:text-amber-400',
            badgeBg: 'bg-amber-50 dark:bg-amber-950/60',
            badgeText: 'text-amber-700 dark:text-amber-300',
            tag: 'Freedom Movement Archive',
        };
    }

    if (t.includes('geography') || t.includes('world') || t.includes('map')) {
        return {
            icon: '🌍',
            themeBg: 'bg-sky-500/10 dark:bg-sky-950/40',
            themeBorder: 'border-sky-500/30 dark:border-sky-800/40',
            themeText: 'text-sky-600 dark:text-sky-400',
            badgeBg: 'bg-sky-50 dark:bg-sky-950/60',
            badgeText: 'text-sky-700 dark:text-sky-300',
            tag: 'Physical & Cartography',
        };
    }

    if (
        t.includes('general knowledge') ||
        t.includes('science') ||
        t.includes('gk')
    ) {
        return {
            icon: '🔬',
            themeBg: 'bg-purple-500/10 dark:bg-purple-950/40',
            themeBorder: 'border-purple-500/30 dark:border-purple-800/40',
            themeText: 'text-purple-600 dark:text-purple-400',
            badgeBg: 'bg-purple-50 dark:bg-purple-950/60',
            badgeText: 'text-purple-700 dark:text-purple-300',
            tag: 'Scientific Analytical',
        };
    }

    if (
        t.includes('current affairs') ||
        t.includes('defense') ||
        t.includes('security') ||
        t.includes('military')
    ) {
        return {
            icon: '🛡️',
            themeBg: 'bg-blue-500/10 dark:bg-blue-950/40',
            themeBorder: 'border-blue-500/30 dark:border-blue-800/40',
            themeText: 'text-blue-600 dark:text-blue-400',
            badgeBg: 'bg-blue-50 dark:bg-blue-950/60',
            badgeText: 'text-blue-700 dark:text-blue-300',
            tag: 'Strategic & Geo-politics',
        };
    }

    if (
        t.includes('physical') ||
        t.includes('fitness') ||
        t.includes('running')
    ) {
        return {
            icon: '🏃‍♂️',
            themeBg: 'bg-orange-500/10 dark:bg-orange-950/40',
            themeBorder: 'border-orange-500/30 dark:border-orange-800/40',
            themeText: 'text-orange-600 dark:text-orange-400',
            badgeBg: 'bg-orange-50 dark:bg-orange-950/60',
            badgeText: 'text-orange-700 dark:text-orange-300',
            tag: 'Physical Standards & Q&A',
        };
    }

    if (t.includes('medical') || t.includes('health') || t.includes('doctor')) {
        return {
            icon: '🩺',
            themeBg: 'bg-rose-500/10 dark:bg-rose-950/40',
            themeBorder: 'border-rose-500/30 dark:border-rose-800/40',
            themeText: 'text-rose-600 dark:text-rose-400',
            badgeBg: 'bg-rose-50 dark:bg-rose-950/60',
            badgeText: 'text-rose-700 dark:text-rose-300',
            tag: 'Medical Standards & Q&A',
        };
    }

    if (t.includes('interview') || t.includes('viva') || t.includes('panel')) {
        return {
            icon: '👔',
            themeBg: 'bg-indigo-500/10 dark:bg-indigo-950/40',
            themeBorder: 'border-indigo-500/30 dark:border-indigo-800/40',
            themeText: 'text-indigo-600 dark:text-indigo-400',
            badgeBg: 'bg-indigo-50 dark:bg-indigo-950/60',
            badgeText: 'text-indigo-700 dark:text-indigo-300',
            tag: 'Interview Prep & Confidence',
        };
    }

    return {
        icon: '📚',
        themeBg: 'bg-zinc-500/10 dark:bg-zinc-800/50',
        themeBorder: 'border-zinc-300 dark:border-zinc-700',
        themeText: 'text-zinc-700 dark:text-zinc-300',
        badgeBg: 'bg-zinc-100 dark:bg-zinc-800',
        badgeText: 'text-zinc-700 dark:text-zinc-300',
        tag: 'Academic Syllabus',
    };
};
</script>

<template>
    <div :class="['grid gap-5', gridClass]">
        <div
            v-for="(item, index) in items"
            :key="item.id || index"
            class="group shadow-senselab-card hover:shadow-senselab-elevated relative flex flex-col justify-between rounded-3xl border border-zinc-200/90 bg-white/95 p-6 backdrop-blur-md transition-all duration-300 hover:-translate-y-1 hover:border-sky-500/40 dark:border-zinc-800 dark:bg-zinc-950/90"
        >
            <div>
                <!-- Top Header: Themed Icon Box + Question Count Pill Badge -->
                <div class="flex items-center justify-between gap-3">
                    <div
                        :class="[
                            'flex h-12 w-12 items-center justify-center rounded-2xl border text-xl shadow-xs transition-transform duration-200 group-hover:scale-105',
                            getSubjectMeta(item.title || item.module?.title)
                                .themeBg,
                            getSubjectMeta(item.title || item.module?.title)
                                .themeBorder,
                        ]"
                    >
                        {{
                            getSubjectMeta(item.title || item.module?.title)
                                .icon
                        }}
                    </div>

                    <div
                        class="inline-flex items-center gap-1.5 rounded-full border border-zinc-200/80 bg-zinc-50/80 px-3 py-1 text-xs font-semibold text-zinc-700 shadow-2xs dark:border-zinc-800 dark:bg-zinc-900/80 dark:text-zinc-300"
                    >
                        <span
                            class="h-1.5 w-1.5 animate-pulse rounded-full bg-emerald-500"
                        ></span>
                        <span>{{
                            item.description ||
                            item.module?.content_meta?.description ||
                            'Curriculum Bank'
                        }}</span>
                    </div>
                </div>

                <!-- Subject Title & Tags -->
                <div class="mt-4">
                    <h3
                        class="text-senselab-heading text-lg leading-snug font-bold tracking-tight transition-colors group-hover:text-sky-600"
                    >
                        {{ item.title || item.module?.title }}
                    </h3>

                    <!-- Syllabus Capability Chips -->
                    <div class="mt-2.5 flex flex-wrap items-center gap-1.5">
                        <span
                            class="rounded-lg bg-zinc-100/80 px-2.5 py-0.5 text-[11px] font-medium text-zinc-600 dark:bg-zinc-800/80 dark:text-zinc-400"
                        >
                            {{
                                getSubjectMeta(item.title || item.module?.title)
                                    .tag
                            }}
                        </span>
                        <span
                            class="rounded-lg bg-zinc-100/80 px-2.5 py-0.5 text-[11px] font-medium text-zinc-600 dark:bg-zinc-800/80 dark:text-zinc-400"
                        >
                            {{ getFeatureChip(item) }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Bottom Card Strip: Duration & SenseLab Gradient Button -->
            <div
                class="mt-6 flex items-center justify-between border-t border-zinc-100 pt-4 dark:border-zinc-800/80"
            >
                <span
                    class="inline-flex items-center gap-1.5 text-xs font-medium text-brand-muted"
                >
                    <span>⏱️</span>
                    <span>{{
                        item.durationMinutes ||
                        item.module?.content_meta?.duration_minutes
                            ? `${item.durationMinutes || item.module.content_meta.duration_minutes} mins`
                            : 'Self-Paced'
                    }}</span>
                </span>

                <button
                    @click="startItem(item)"
                    type="button"
                    class="inline-flex cursor-pointer items-center gap-2 rounded-full px-5 py-2.5 text-xs font-semibold text-white shadow-xs transition-all duration-200 hover:scale-[1.03] hover:shadow-md active:scale-95"
                    style="background: var(--brand-gradient)"
                >
                    <span>{{ item.actionLabel || 'Start Practice' }}</span>
                    <span
                        class="transition-transform duration-200 group-hover:translate-x-0.5"
                        >➔</span
                    >
                </button>
            </div>
        </div>
    </div>
</template>
