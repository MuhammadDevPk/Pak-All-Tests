<script setup>
defineProps({
    title: {
        type: String,
        default: 'Section Under Construction',
    },
    message: {
        type: String,
        default:
            'The learning material for this domain is currently being populated.',
    },
});
</script>

<template>
    <div
        class="flex min-h-[400px] flex-col items-center justify-center rounded-xl border border-dashed border-sidebar-border/70 bg-sidebar/10 p-8 text-center dark:bg-sidebar/5"
    >
        <div class="mb-4 rounded-full bg-primary/10 p-4 text-primary">
            <svg
                class="h-8 w-8"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="1.5"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"
                />
            </svg>
        </div>
        <h3 class="mb-1 text-lg font-semibold text-foreground">{{ title }}</h3>
        <p class="max-w-md text-sm text-muted-foreground">{{ message }}</p>
    </div>
</template>
