<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const platform = computed(() => page.props.platform || {});
const guestHours = computed(
    () => platform.value?.trials?.guest_pass_hours || 24,
);
const trialDays = computed(() => platform.value?.trials?.free_trial_days || 7);

const props = defineProps({
    guest: {
        type: Object,
        default: () => ({ is_guest: false }),
    },
});

const isExpired = computed(() => {
    if (!props.guest?.is_guest) {
        return false;
    }

    if (props.guest.is_expired) {
        return true;
    }

    if (
        props.guest.expires_in_hours !== null &&
        props.guest.expires_in_hours <= 0
    ) {
        if (props.guest.expires_at) {
            return new Date(props.guest.expires_at) <= new Date();
        }

        return true;
    }

    return false;
});

const registerUrl = computed(() => {
    if (typeof window === 'undefined') {
        return '/register';
    }

    const currentPath = window.location.pathname + window.location.search;

    return `/register?redirect=${encodeURIComponent(currentPath)}`;
});
</script>

<template>
    <div v-if="guest?.is_guest" class="w-full">
        <!-- Sticky Guest Access Bar -->
        <div
            v-if="isExpired"
            class="relative flex flex-wrap items-center justify-between gap-3 overflow-hidden rounded-2xl border border-rose-400/80 bg-gradient-to-r from-rose-500/15 via-amber-500/10 to-rose-500/15 p-3.5 backdrop-blur-md sm:px-6 dark:border-rose-500/40 dark:from-rose-950/50 dark:via-zinc-950/70 dark:to-rose-950/40"
        >
            <div class="flex items-center gap-3">
                <span
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-rose-500/15 text-lg font-bold text-rose-600 dark:bg-rose-500/20 dark:text-rose-400"
                >
                    ⚠️
                </span>
                <div>
                    <div class="flex items-center gap-2">
                        <span
                            class="text-xs font-bold tracking-wide text-rose-800 uppercase dark:text-rose-300"
                        >
                            24-Hour Guest Window Completed
                        </span>
                        <span
                            class="rounded-full bg-rose-100 px-2 py-0.5 text-[10px] font-semibold text-rose-800 dark:bg-rose-900/60 dark:text-rose-200"
                        >
                            Access Ended
                        </span>
                    </div>
                    <p class="mt-0.5 text-xs text-zinc-700 dark:text-zinc-300">
                        Your 24-hour guest account window has completed.
                        Register your free account now to keep access to your
                        exam dashboard, test history, and unlock a
                        <strong>{{ trialDays }}-Day Free Trial</strong>.
                    </p>
                </div>
            </div>

            <Link
                :href="registerUrl"
                class="inline-flex items-center justify-center gap-1.5 rounded-xl bg-rose-600 px-4 py-2 text-xs font-semibold text-white shadow-xs transition hover:bg-rose-500 focus:ring-2 focus:ring-rose-500/40 focus:outline-hidden dark:bg-rose-600 dark:hover:bg-rose-500"
            >
                <span>Register to Keep Access ({{ trialDays }} Days Free)</span>
                <span>→</span>
            </Link>
        </div>

        <div
            v-else
            class="relative flex flex-wrap items-center justify-between gap-3 overflow-hidden rounded-2xl border border-amber-300/80 bg-gradient-to-r from-amber-500/10 via-amber-500/5 to-amber-500/10 p-3.5 backdrop-blur-md sm:px-6 dark:border-amber-500/30 dark:from-amber-950/40 dark:via-zinc-950/60 dark:to-amber-950/30"
        >
            <div class="flex items-center gap-3">
                <span
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-amber-500/15 text-lg font-bold text-amber-600 dark:bg-amber-500/20 dark:text-amber-400"
                >
                    ⚡
                </span>
                <div>
                    <div class="flex items-center gap-2">
                        <span
                            class="text-xs font-bold tracking-wide text-amber-800 uppercase dark:text-amber-300"
                        >
                            {{ guestHours }}-Hour Guest Pass Active
                        </span>
                        <span
                            v-if="guest.expires_in_hours !== null"
                            class="rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-semibold text-amber-800 dark:bg-amber-900/60 dark:text-amber-200"
                        >
                            {{ guest.expires_in_hours }}h left
                        </span>
                    </div>
                    <p class="mt-0.5 text-xs text-zinc-600 dark:text-zinc-400">
                        Create a free permanent account to save your test
                        records, bookmark weak MCQs, and unlock a
                        <strong>{{ trialDays }}-Day Free Trial</strong>.
                    </p>
                </div>
            </div>

            <Link
                :href="registerUrl"
                class="inline-flex items-center justify-center gap-1.5 rounded-xl bg-amber-500 px-4 py-2 text-xs font-semibold text-white shadow-xs transition hover:bg-amber-600 focus:ring-2 focus:ring-amber-500/40 focus:outline-hidden dark:bg-amber-600 dark:hover:bg-amber-500"
            >
                <span>Save Progress & Unlock {{ trialDays }} Days</span>
                <span>→</span>
            </Link>
        </div>
    </div>
</template>
