<script setup>
import { ref, computed } from 'vue';
import SduiRenderer from './SduiRenderer.vue';

const props = defineProps({
    defaultTab: {
        type: String,
        default: '',
    },
    tabs: {
        type: Array,
        required: true,
        default: () => [],
    },
});

const activeTabId = ref(props.defaultTab || props.tabs[0]?.id || '');

const activeTabContent = computed(() => {
    const matchedTab = props.tabs.find((tab) => tab.id === activeTabId.value);

    return matchedTab?.content || null;
});

const selectTab = (id) => {
    activeTabId.value = id;
};
</script>

<template>
    <div class="flex flex-col gap-6">
        <!-- Navigation Tab Header -->
        <div
            class="flex scrollbar-none overflow-x-auto border-b border-sidebar-border/70 dark:border-sidebar-border"
        >
            <nav class="-mb-px flex space-x-6">
                <button
                    v-for="tab in tabs"
                    :key="tab.id"
                    type="button"
                    @click="selectTab(tab.id)"
                    :class="[
                        'group inline-flex items-center gap-2 border-b-2 px-1 py-3 text-sm font-medium whitespace-nowrap transition-colors',
                        activeTabId === tab.id
                            ? 'border-primary font-semibold text-primary'
                            : 'border-transparent text-muted-foreground hover:border-sidebar-border hover:text-foreground',
                    ]"
                >
                    <span>{{ tab.label }}</span>
                    <span
                        v-if="tab.badge"
                        :class="[
                            'rounded-full px-2 py-0.5 text-xs font-semibold',
                            activeTabId === tab.id
                                ? 'bg-primary/10 text-primary'
                                : 'bg-sidebar-accent text-muted-foreground',
                        ]"
                    >
                        {{ tab.badge }}
                    </span>
                </button>
            </nav>
        </div>

        <!-- Render Active Tab's SDUI Sub-Tree -->
        <div class="transition-opacity duration-150">
            <SduiRenderer v-if="activeTabContent" :node="activeTabContent" />
        </div>
    </div>
</template>
