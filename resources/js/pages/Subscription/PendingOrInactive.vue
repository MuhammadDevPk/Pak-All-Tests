<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

defineProps<{
    domain: {
        id: number;
        name: string;
        slug: string;
    };
    subscriptionStatus: string;
}>();
</script>

<template>
    <Head title="Subscription Required" />

    <div
        class="flex min-h-screen flex-col items-center justify-center bg-background px-4 py-12 sm:px-6 lg:px-8"
    >
        <div
            class="w-full max-w-md space-y-8 rounded-2xl border border-sidebar-border/70 bg-card p-8 shadow-xl dark:bg-card/50"
        >
            <div class="text-center">
                <!-- Icon -->
                <div
                    class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-primary/10 text-primary"
                >
                    <svg
                        class="h-6 w-6"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="1.5"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"
                        />
                    </svg>
                </div>

                <h2
                    class="mt-6 text-2xl font-bold tracking-tight text-foreground"
                >
                    Access Restricted
                </h2>
                <p class="mt-2 text-sm text-muted-foreground">
                    An active subscription is required to access the
                    <span class="font-semibold text-foreground">{{
                        domain.name
                    }}</span>
                    preparation dashboard.
                </p>
            </div>

            <div class="mt-8 space-y-6">
                <div
                    class="rounded-lg border border-sidebar-border/50 bg-sidebar p-4 text-sm text-foreground/80"
                >
                    <p class="mb-1 font-semibold text-foreground">
                        Current Status:
                    </p>
                    <span
                        v-if="subscriptionStatus === 'none'"
                        class="inline-flex items-center rounded-full bg-yellow-500/10 px-2 py-1 text-xs font-medium text-yellow-500 ring-1 ring-yellow-500/20 ring-inset"
                    >
                        No Active Subscription
                    </span>
                    <span
                        v-else
                        class="inline-flex items-center rounded-full bg-blue-500/10 px-2 py-1 text-xs font-medium text-blue-500 ring-1 ring-blue-500/20 ring-inset"
                    >
                        {{ subscriptionStatus }}
                    </span>
                </div>

                <div class="flex flex-col gap-3">
                    <Link
                        href="/"
                        class="flex w-full justify-center rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground shadow transition-colors hover:bg-primary/90"
                    >
                        Go to Homepage
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>
