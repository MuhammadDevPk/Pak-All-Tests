<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    title: {
        type: String,
        default: 'Document Viewer',
    },
    fileUrl: {
        type: String,
        default: '',
    },
    description: {
        type: String,
        default: '',
    },
    files: {
        type: Array,
        default: () => [],
    },
});

const isFullscreen = ref(false);
const activeFileIndex = ref(0);

const normalizePdfUrl = (url) => {
    if (!url) {
        return '';
    }

    // Auto-convert Google Drive view links to embeddable preview links
    if (url.includes('drive.google.com') && url.includes('/view')) {
        return url.replace(/\/view(\?.*)?$/, '/preview');
    }

    return url;
};

// Compute effective list of files (supporting both multi-file array and legacy single fileUrl)
const effectiveFiles = computed(() => {
    if (props.files && props.files.length > 0) {
        return props.files.filter(
            (f) => f && (f.file_url || f.fileUrl || f.title),
        );
    }

    if (props.fileUrl) {
        return [
            {
                title: props.title || 'Document',
                file_url: props.fileUrl,
                file_type: 'PDF Document',
            },
        ];
    }

    return [];
});

const activeFile = computed(() => {
    if (effectiveFiles.value.length === 0) {
        return null;
    }

    const index = Math.min(
        activeFileIndex.value,
        effectiveFiles.value.length - 1,
    );

    return effectiveFiles.value[index] || null;
});

const currentFileUrl = computed(() => {
    const file = activeFile.value;

    if (!file) {
        return props.fileUrl || '';
    }

    return file.file_url || file.fileUrl || '';
});

const currentEmbedUrl = computed(() => normalizePdfUrl(currentFileUrl.value));

const toggleFullscreen = () => {
    isFullscreen.value = !isFullscreen.value;
};
</script>

<template>
    <div
        :class="[
            'flex flex-col gap-3 rounded-xl border border-sidebar-border/70 bg-card p-4 transition-all dark:bg-card/50',
            isFullscreen
                ? 'fixed inset-4 z-50 overflow-hidden bg-card shadow-2xl'
                : '',
        ]"
    >
        <!-- Header: Group / Module Title & Actions -->
        <div
            class="flex flex-wrap items-center justify-between gap-2 border-b border-sidebar-border/50 pb-3"
        >
            <div class="flex flex-col">
                <div class="flex items-center gap-2">
                    <h3 class="text-base font-bold text-foreground">
                        {{ title }}
                    </h3>
                    <span
                        v-if="effectiveFiles.length > 1"
                        class="rounded-full bg-primary/10 px-2 py-0.5 text-xs font-semibold text-primary"
                    >
                        {{ effectiveFiles.length }} Files
                    </span>
                </div>
                <p v-if="description" class="text-xs text-muted-foreground">
                    {{ description }}
                </p>
            </div>

            <div class="flex items-center gap-2">
                <button
                    type="button"
                    @click="toggleFullscreen"
                    class="rounded-lg border border-sidebar-border/70 bg-sidebar px-3 py-1.5 text-xs font-medium text-foreground transition-colors hover:bg-sidebar-accent"
                >
                    {{
                        isFullscreen ? 'Exit Fullscreen ↙' : 'Fullscreen Mode ↗'
                    }}
                </button>
                <a
                    v-if="currentFileUrl"
                    :href="currentFileUrl"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="inline-flex items-center gap-1 rounded-lg bg-primary px-3 py-1.5 text-xs font-medium text-primary-foreground transition-colors hover:bg-primary/90"
                >
                    Open in New Tab ↗
                </a>
            </div>
        </div>

        <!-- Multi-File Tab Switcher (When module has multiple files) -->
        <div v-if="effectiveFiles.length > 1" class="flex flex-wrap gap-2 py-1">
            <button
                v-for="(file, idx) in effectiveFiles"
                :key="idx"
                type="button"
                @click="activeFileIndex = idx"
                :class="[
                    'flex items-center gap-2 rounded-lg px-3 py-1.5 text-xs font-medium transition-all',
                    activeFileIndex === idx
                        ? 'bg-primary text-primary-foreground shadow-sm'
                        : 'border border-sidebar-border/60 bg-sidebar text-muted-foreground hover:bg-sidebar-accent hover:text-foreground',
                ]"
            >
                <svg
                    class="h-3.5 w-3.5"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                    />
                </svg>
                <span>{{ file.title || `Document ${idx + 1}` }}</span>
                <span
                    v-if="file.file_size || file.fileSize"
                    :class="[
                        'py-0.2 rounded px-1.5 text-[10px]',
                        activeFileIndex === idx
                            ? 'bg-primary-foreground/20 text-primary-foreground'
                            : 'bg-sidebar-accent text-muted-foreground',
                    ]"
                >
                    {{ file.file_size || file.fileSize }}
                </span>
            </button>
        </div>

        <!-- Active Individual File Label (if distinct from parent title) -->
        <div
            v-if="activeFile && effectiveFiles.length > 1"
            class="flex items-center justify-between px-1 text-xs text-muted-foreground"
        >
            <span
                >Viewing:
                <strong class="text-foreground">{{
                    activeFile.title
                }}</strong></span
            >
            <span v-if="activeFile.file_type || activeFile.fileType">{{
                activeFile.file_type || activeFile.fileType
            }}</span>
        </div>

        <!-- Embedded Viewer Iframe -->
        <div
            :class="[
                'w-full overflow-hidden rounded-lg border border-sidebar-border/50 bg-sidebar/20',
                isFullscreen ? 'min-h-[500px] flex-1' : 'h-[650px]',
            ]"
        >
            <iframe
                v-if="currentEmbedUrl"
                :src="currentEmbedUrl"
                class="h-full w-full border-none"
                title="Document Viewer"
                allow="autoplay"
            />
            <div
                v-else
                class="flex h-full items-center justify-center text-xs text-muted-foreground"
            >
                No PDF or Document URL configured.
            </div>
        </div>
    </div>
</template>
