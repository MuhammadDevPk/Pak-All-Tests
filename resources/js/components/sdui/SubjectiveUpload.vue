<script setup>
import { ref } from 'vue';

defineProps({
    title: {
        type: String,
        required: true,
    },
    instructions: {
        type: String,
        required: false,
    },
    maxWordCount: {
        type: [Number, String],
        required: true,
    },
});

const selectedFile = ref(null);
const isDragging = ref(false);
const wordCountInput = ref('');
const submissionStatus = ref('idle'); // 'idle' | 'uploading' | 'submitted'

const handleFileSelect = (event) => {
    const file = event.target.files?.[0];

    if (file) {
        selectedFile.value = file;
    }
};

const handleDrop = (event) => {
    isDragging.value = false;
    const file = event.dataTransfer?.files[0];

    if (file) {
        selectedFile.value = file;
    }
};

const submitAssignment = () => {
    if (!selectedFile.value) {
        return;
    }

    submissionStatus.value = 'uploading';

    // Simulate async submission pipeline
    setTimeout(() => {
        submissionStatus.value = 'submitted';
    }, 1200);
};
</script>

<template>
    <div
        class="flex flex-col gap-6 rounded-xl border border-sidebar-border/70 bg-card p-6 shadow-xs dark:bg-card/50"
    >
        <div>
            <h2 class="text-base font-bold text-foreground">{{ title }}</h2>
            <p class="mt-1 text-xs leading-relaxed text-muted-foreground">
                {{ instructions }}
            </p>
        </div>

        <div
            v-if="submissionStatus === 'submitted'"
            class="rounded-xl border border-emerald-500/30 bg-emerald-500/10 p-4 text-emerald-600 dark:text-emerald-400"
        >
            <div class="flex items-center gap-3">
                <span class="text-lg">✓</span>
                <div class="flex flex-col">
                    <span class="text-sm font-semibold"
                        >Essay Submitted Successfully!</span
                    >
                    <span class="text-xs"
                        >Your instructor will evaluate your paper within 24
                        hours.</span
                    >
                </div>
            </div>
        </div>

        <div v-else class="flex flex-col gap-4">
            <!-- Word Count Input -->
            <div class="flex flex-col gap-1.5">
                <label class="text-xs font-semibold text-foreground"
                    >Self-Reported Word Count</label
                >
                <input
                    v-model="wordCountInput"
                    type="number"
                    placeholder="e.g. 2150"
                    class="w-full max-w-xs rounded-lg border border-sidebar-border/70 bg-sidebar px-3 py-2 text-sm text-foreground focus:ring-2 focus:ring-primary focus:outline-hidden"
                />
                <span class="text-[11px] text-muted-foreground"
                    >Target Word Limit: {{ maxWordCount }} words</span
                >
            </div>
            <!-- Drag and Drop Box -->
            <div
                @dragover.prevent="isDragging = true"
                @dragleave.prevent="isDragging = false"
                @drop.prevent="handleDrop"
                :class="[
                    'flex flex-col items-center justify-center rounded-xl border-2 border-dashed p-8 text-center transition-all',
                    isDragging
                        ? 'border-primary bg-primary/10'
                        : selectedFile
                          ? 'border-emerald-500/50 bg-emerald-500/5'
                          : 'border-sidebar-border/70 bg-sidebar/20 hover:border-sidebar-border',
                ]"
            >
                <div class="mb-3 rounded-full bg-primary/10 p-3 text-primary">
                    <svg
                        class="h-6 w-6"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="1.5"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M12 16.5V9.75m0 0l3 3m-3-3l-3 3M6.75 19.5a4.5 4.5 0 01-1.41-8.775 5.25 5.25 0 0110.233-2.33 3 3 0 013.758 3.848A3.752 3.752 0 0118 19.5H6.75z"
                        />
                    </svg>
                </div>

                <div v-if="selectedFile" class="flex flex-col items-center">
                    <span class="text-sm font-semibold text-foreground">{{
                        selectedFile.name
                    }}</span>
                    <span class="text-xs text-muted-foreground"
                        >{{
                            (selectedFile.size / 1024 / 1024).toFixed(2)
                        }}
                        MB</span
                    >
                    <button
                        type="button"
                        @click="selectedFile = null"
                        class="mt-2 text-xs font-medium text-destructive hover:underline"
                    >
                        Remove File
                    </button>
                </div>

                <div v-else class="flex flex-col items-center">
                    <p class="text-sm font-medium text-foreground">
                        Drag and drop your PDF or image here
                    </p>
                    <p class="mt-1 text-xs text-muted-foreground">
                        Supports PDF, PNG, JPG up to 25MB
                    </p>
                    <label
                        class="mt-4 cursor-pointer rounded-lg bg-primary px-4 py-2 text-xs font-semibold text-primary-foreground transition-colors hover:bg-primary/90"
                    >
                        Browse File
                        <input
                            type="file"
                            accept=".pdf,.png,.jpg,.jpeg"
                            @change="handleFileSelect"
                            class="hidden"
                        />
                    </label>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="flex justify-end pt-2">
                <button
                    type="button"
                    @click="submitAssignment"
                    :disabled="
                        !selectedFile || submissionStatus === 'uploading'
                    "
                    class="rounded-lg bg-emerald-600 px-6 py-2.5 text-xs font-semibold text-white transition-colors hover:bg-emerald-700 disabled:opacity-40"
                >
                    {{
                        submissionStatus === 'uploading'
                            ? 'Uploading...'
                            : 'Submit Essay for Grading'
                    }}
                </button>
            </div>
        </div>
    </div>
</template>
