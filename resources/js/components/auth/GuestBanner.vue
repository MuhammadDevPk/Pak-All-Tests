<script setup>
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({
    guest: {
        type: Object,
        default: () => ({ is_guest: false }),
    },
});

const isModalOpen = ref(false);

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const openModal = () => {
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
    form.reset();
    form.clearErrors();
};

const submitConversion = () => {
    form.post('/convert-guest', {
        preserveScroll: true,
        onSuccess: () => {
            closeModal();
        },
    });
};
</script>

<template>
    <div v-if="guest?.is_guest" class="w-full">
        <!-- Sticky Guest Access Bar -->
        <div
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
                            24-Hour Guest Pass Active
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
                        <strong>7-Day Free Trial</strong>.
                    </p>
                </div>
            </div>

            <button
                type="button"
                class="inline-flex items-center justify-center gap-1.5 rounded-xl bg-amber-500 px-4 py-2 text-xs font-semibold text-white shadow-xs transition hover:bg-amber-600 focus:ring-2 focus:ring-amber-500/40 focus:outline-hidden dark:bg-amber-600 dark:hover:bg-amber-500"
                @click="openModal"
            >
                <span>Save Progress & Unlock 7 Days</span>
                <span>→</span>
            </button>
        </div>

        <!-- Conversion Modal -->
        <div
            v-if="isModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center bg-zinc-950/60 p-4 backdrop-blur-sm"
            @click.self="closeModal"
        >
            <div
                class="relative w-full max-w-md overflow-hidden rounded-3xl border border-zinc-200 bg-white p-6 shadow-2xl dark:border-zinc-800 dark:bg-zinc-900"
            >
                <div
                    class="flex items-center justify-between border-b border-zinc-100 pb-3 dark:border-zinc-800"
                >
                    <div>
                        <h3
                            class="text-lg font-bold text-zinc-900 dark:text-white"
                        >
                            Keep Your Test Progress
                        </h3>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">
                            Create your free account to unlock your 7-Day Free
                            Trial.
                        </p>
                    </div>
                    <button
                        type="button"
                        class="rounded-lg p-1.5 text-zinc-400 hover:bg-zinc-100 hover:text-zinc-700 dark:hover:bg-zinc-800 dark:hover:text-zinc-200"
                        @click="closeModal"
                    >
                        ✕
                    </button>
                </div>

                <form class="mt-4 space-y-4" @submit.prevent="submitConversion">
                    <div>
                        <label
                            class="block text-xs font-medium text-zinc-700 dark:text-zinc-300"
                        >
                            Full Name
                        </label>
                        <input
                            v-model="form.name"
                            type="text"
                            required
                            placeholder="e.g. Ali Ahmed"
                            class="mt-1 block w-full rounded-xl border border-zinc-200 bg-zinc-50 px-3.5 py-2 text-sm text-zinc-900 focus:border-sky-500 focus:bg-white focus:ring-2 focus:ring-sky-500/20 focus:outline-hidden dark:border-zinc-800 dark:bg-zinc-950 dark:text-white"
                        />
                        <p
                            v-if="form.errors.name"
                            class="mt-1 text-xs text-red-500"
                        >
                            {{ form.errors.name }}
                        </p>
                    </div>

                    <div>
                        <label
                            class="block text-xs font-medium text-zinc-700 dark:text-zinc-300"
                        >
                            Email Address
                        </label>
                        <input
                            v-model="form.email"
                            type="email"
                            required
                            placeholder="name@example.com"
                            class="mt-1 block w-full rounded-xl border border-zinc-200 bg-zinc-50 px-3.5 py-2 text-sm text-zinc-900 focus:border-sky-500 focus:bg-white focus:ring-2 focus:ring-sky-500/20 focus:outline-hidden dark:border-zinc-800 dark:bg-zinc-950 dark:text-white"
                        />
                        <p
                            v-if="form.errors.email"
                            class="mt-1 text-xs text-red-500"
                        >
                            {{ form.errors.email }}
                        </p>
                    </div>

                    <div>
                        <label
                            class="block text-xs font-medium text-zinc-700 dark:text-zinc-300"
                        >
                            Create Password
                        </label>
                        <input
                            v-model="form.password"
                            type="password"
                            required
                            minlength="8"
                            placeholder="Minimum 8 characters"
                            class="mt-1 block w-full rounded-xl border border-zinc-200 bg-zinc-50 px-3.5 py-2 text-sm text-zinc-900 focus:border-sky-500 focus:bg-white focus:ring-2 focus:ring-sky-500/20 focus:outline-hidden dark:border-zinc-800 dark:bg-zinc-950 dark:text-white"
                        />
                        <p
                            v-if="form.errors.password"
                            class="mt-1 text-xs text-red-500"
                        >
                            {{ form.errors.password }}
                        </p>
                    </div>

                    <div>
                        <label
                            class="block text-xs font-medium text-zinc-700 dark:text-zinc-300"
                        >
                            Confirm Password
                        </label>
                        <input
                            v-model="form.password_confirmation"
                            type="password"
                            required
                            placeholder="Re-enter password"
                            class="mt-1 block w-full rounded-xl border border-zinc-200 bg-zinc-50 px-3.5 py-2 text-sm text-zinc-900 focus:border-sky-500 focus:bg-white focus:ring-2 focus:ring-sky-500/20 focus:outline-hidden dark:border-zinc-800 dark:bg-zinc-950 dark:text-white"
                        />
                    </div>

                    <div class="flex items-center justify-end gap-2.5 pt-2">
                        <button
                            type="button"
                            class="rounded-xl px-4 py-2 text-xs font-medium text-zinc-600 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-800"
                            @click="closeModal"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="inline-flex items-center justify-center rounded-xl bg-sky-600 px-5 py-2 text-xs font-semibold text-white shadow-xs transition hover:bg-sky-500 disabled:opacity-50 dark:bg-sky-500 dark:hover:bg-sky-400"
                        >
                            <span v-if="form.processing">Saving...</span>
                            <span v-else>Save & Activate 7-Day Trial</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
