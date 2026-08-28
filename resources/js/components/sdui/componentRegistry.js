import { defineAsyncComponent } from 'vue';
import EmptyStateLayout from './EmptyStateLayout.vue';

/**
 * Registry mapping SDUI type strings from backend JSON schema
 * to Vue component definitions.
 */
export const componentRegistry = {
    // Structural Layouts
    EmptyStateLayout,

    // Sub-Phase 3.3 Structural Layouts
    TabbedLayout: defineAsyncComponent(() => import('./TabbedLayout.vue')),
    SubjectGrid: defineAsyncComponent(() => import('./SubjectGrid.vue')),
    PhaseStepperLayout: defineAsyncComponent(
        () => import('./PhaseStepperLayout.vue'),
    ),
    ResourceList: defineAsyncComponent(() => import('./ResourceList.vue')),
    VerticalStack: defineAsyncComponent(() => import('./VerticalStack.vue')),

    // Sub-Phase 3.4 Interactive Components
    McqPlayer: defineAsyncComponent(() => import('./McqPlayer.vue')),
    PdfViewer: defineAsyncComponent(() => import('./PdfViewer.vue')),
    SubjectiveUpload: defineAsyncComponent(
        () => import('./SubjectiveUpload.vue'),
    ),

    // Sub-Phase 3.5 Progress Analytics Components
    ProgressCard: defineAsyncComponent(() => import('./ProgressCard.vue')),
};
