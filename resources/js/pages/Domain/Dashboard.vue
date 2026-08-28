<script setup>
import { ref, provide, defineAsyncComponent } from 'vue';
import { Head } from '@inertiajs/vue3';
import SduiRenderer from '@/components/sdui/SduiRenderer.vue';

const props = defineProps({
    sdui: {
        type: Object,
        required: true,
    },
});

const activeModule = ref(null);
const activeQuestions = ref([]);
const isLoadingModule = ref(false);

const launchModule = async (moduleId) => {
    if (!moduleId) return;
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
};

provide('sduiContext', {
    launchModule,
});

const McqPlayer = defineAsyncComponent(() => import('@/components/sdui/McqPlayer.vue'));
const PdfViewer = defineAsyncComponent(() => import('@/components/sdui/PdfViewer.vue'));
const SubjectiveUpload = defineAsyncComponent(() => import('@/components/sdui/SubjectiveUpload.vue'));
</script>

<template>
    <Head :title="sdui?.meta?.domain?.name || 'Dashboard'" />
    <div class="flex h-full flex-1 flex-col gap-4 p-4 md:p-6">
        <div class="flex flex-col gap-1 border-b border-sidebar-border/70 pb-4 dark:border-sidebar-border">
            <h1 class="text-2xl font-bold tracking-tight text-foreground">
                {{ sdui?.meta?.domain?.name }}
            </h1>
            <p class="text-xs text-muted-foreground">
                Path: <span>{{ sdui?.meta?.domain?.path }}</span>
            </p>
        </div>

        <div v-if="activeModule" class="mt-2 flex-1 flex flex-col gap-4">
            <button
                type="button"
                @click="closePlayer"
                class="inline-flex items-center gap-1.5 text-sm font-semibold text-primary hover:text-primary/95 hover:underline"
            >
                ← Back to Dashboard
            </button>

            <McqPlayer
                v-if="activeModule.module_type === 'mock_test' || activeModule.module_type === 'preparation'"
                :module="activeModule"
                :questions="activeQuestions"
                mode="practice"
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
                :instructions="activeModule.content_meta?.instructions || activeModule.content_meta?.description"
                :maxWordCount="activeModule.content_meta?.max_word_count || 1000"
            />
        </div>
        <div v-else class="mt-2 flex-1">
            <div v-if="isLoadingModule" class="flex min-h-[300px] items-center justify-center text-sm text-muted-foreground animate-pulse">
                Loading module content...
            </div>
            <SduiRenderer v-else-if="sdui?.layout" :node="sdui.layout" />
        </div>
    </div>
</template>