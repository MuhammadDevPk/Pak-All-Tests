<script setup>
import { ref } from 'vue';

const props = defineProps({
    referral: {
        type: Object,
        default: () => ({
            referral_code: '',
            referral_link: '',
            referrals_count: 0,
            days_earned: 0,
            milestone_target: 5,
            is_milestone_reached: false,
            remaining_for_milestone: 5,
            milestone_reward_label: '30 Days Full Access',
        }),
    },
});

const isCopied = ref(false);

const copyLink = async () => {
    if (!props.referral?.referral_link) {
        return;
    }

    try {
        await navigator.clipboard.writeText(props.referral.referral_link);
        isCopied.value = true;
        setTimeout(() => {
            isCopied.value = false;
        }, 2000);
    } catch {
        const input = document.getElementById('referral-link-input');

        if (input) {
            input.select();
            document.execCommand('copy');
            isCopied.value = true;
            setTimeout(() => {
                isCopied.value = false;
            }, 2000);
        }
    }
};

const shareWhatsApp = () => {
    if (!props.referral?.referral_link) {
        return;
    }

    const message = `Join me on Pak-All-Tests! Practice authentic past-paper MCQs, take timed exams, and get instant Socratic AI mentorship: ${props.referral.referral_link}`;
    const url = `https://api.whatsapp.com/send?text=${encodeURIComponent(message)}`;
    window.open(url, '_blank');
};
</script>

<template>
    <div
        class="shadow-senselab-card relative overflow-hidden rounded-3xl border border-sky-200/80 bg-gradient-to-br from-white via-sky-50/30 to-white p-6 backdrop-blur-md sm:p-8 dark:border-sky-950/60 dark:from-zinc-950/90 dark:via-sky-950/20 dark:to-zinc-950/90"
    >
        <!-- Header -->
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-2.5">
                <span
                    class="flex h-10 w-10 items-center justify-center rounded-2xl bg-sky-500/10 text-xl font-bold text-sky-600 dark:bg-sky-500/20 dark:text-sky-400"
                >
                    🎁
                </span>
                <div>
                    <h2
                        class="text-lg font-extrabold tracking-tight text-zinc-900 sm:text-xl dark:text-white"
                    >
                        Invite Friends & Unlock Extended Access
                    </h2>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">
                        Earn <strong>+7 Days</strong> for every friend who
                        registers. Reach 5 friends to unlock
                        <strong>30 Days Full Course Access</strong>!
                    </p>
                </div>
            </div>

            <!-- Milestone Badge -->
            <div
                v-if="referral.is_milestone_reached"
                class="inline-flex items-center gap-1.5 rounded-full border border-emerald-300 bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700 dark:border-emerald-800/80 dark:bg-emerald-950/50 dark:text-emerald-300"
            >
                <span>★ 30-Day Milestone Unlocked!</span>
            </div>
            <div
                v-else
                class="inline-flex items-center gap-1.5 rounded-full border border-sky-300 bg-sky-50 px-3 py-1 text-xs font-semibold text-sky-700 dark:border-sky-800/80 dark:bg-sky-950/50 dark:text-sky-300"
            >
                <span
                    >{{ referral.remaining_for_milestone }} more to unlock
                    30-Day Goal</span
                >
            </div>
        </div>

        <!-- Progress Stepper -->
        <div
            class="mt-6 rounded-2xl border border-zinc-200/80 bg-white/70 p-4 dark:border-zinc-800 dark:bg-zinc-900/60"
        >
            <div
                class="flex items-center justify-between text-xs font-medium text-zinc-700 dark:text-zinc-300"
            >
                <span>Referral Progress</span>
                <span class="font-bold text-sky-600 dark:text-sky-400">
                    {{ referral.referrals_count }} / 5 Friends Joined •
                    {{ referral.days_earned }} Days Earned
                </span>
            </div>

            <!-- Progress Bar -->
            <div
                class="mt-2.5 h-2.5 w-full overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800"
            >
                <div
                    class="h-full rounded-full bg-gradient-to-r from-sky-500 to-emerald-500 transition-all duration-500"
                    :style="{
                        width: `${Math.min(100, (referral.referrals_count / 5) * 100)}%`,
                    }"
                ></div>
            </div>

            <!-- 5 Steps -->
            <div
                class="mt-3 grid grid-cols-5 gap-1.5 text-center text-[10px] font-semibold"
            >
                <div
                    v-for="step in 5"
                    :key="step"
                    class="rounded-lg py-1 transition"
                    :class="[
                        referral.referrals_count >= step
                            ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300'
                            : 'bg-zinc-50 text-zinc-400 dark:bg-zinc-800/40 dark:text-zinc-500',
                    ]"
                >
                    <span v-if="step < 5">+7 Days</span>
                    <span v-else class="text-sky-600 dark:text-sky-400"
                        >★ 30 Days</span
                    >
                </div>
            </div>
        </div>

        <!-- Share Links -->
        <div class="mt-5 flex flex-col gap-3 sm:flex-row sm:items-center">
            <div class="relative flex-1">
                <input
                    id="referral-link-input"
                    type="text"
                    readonly
                    :value="referral.referral_link"
                    class="w-full rounded-xl border border-zinc-200 bg-white px-3.5 py-2.5 font-mono text-xs text-zinc-800 select-all focus:border-sky-500 focus:outline-hidden dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-200"
                />
            </div>

            <div class="flex items-center gap-2">
                <button
                    type="button"
                    class="inline-flex items-center justify-center gap-1.5 rounded-xl border border-zinc-200 bg-white px-4 py-2.5 text-xs font-semibold text-zinc-700 shadow-xs transition hover:bg-zinc-50 active:scale-95 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:bg-zinc-800"
                    @click="copyLink"
                >
                    <span
                        v-if="isCopied"
                        class="text-emerald-600 dark:text-emerald-400"
                        >✓ Copied!</span
                    >
                    <span v-else>📋 Copy Link</span>
                </button>

                <button
                    type="button"
                    class="inline-flex items-center justify-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2.5 text-xs font-semibold text-white shadow-xs transition hover:bg-emerald-500 active:scale-95 dark:bg-emerald-500 dark:hover:bg-emerald-400"
                    @click="shareWhatsApp"
                >
                    <span>💬 Share via WhatsApp</span>
                </button>
            </div>
        </div>

        <!-- Referred Candidates Activity List -->
        <div
            v-if="referral.recent_referrals && referral.recent_referrals.length > 0"
            class="mt-4 border-t border-sky-100 pt-3 dark:border-sky-950/80"
        >
            <div class="mb-2 flex items-center justify-between text-[11px]">
                <span class="font-semibold text-zinc-700 dark:text-zinc-300">
                    Referred Friends Joined ({{ referral.recent_referrals.length }})
                </span>
                <span class="text-zinc-400 dark:text-zinc-500">
                    +{{ referral.days_earned }} Days Total Unlocked
                </span>
            </div>
            <div class="flex flex-wrap gap-1.5">
                <div
                    v-for="friend in referral.recent_referrals"
                    :key="friend.id"
                    class="inline-flex items-center gap-1.5 rounded-full border border-emerald-200/80 bg-emerald-50/80 px-2.5 py-1 text-[11px] font-medium text-emerald-800 dark:border-emerald-900/60 dark:bg-emerald-950/40 dark:text-emerald-300"
                >
                    <span class="flex h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                    <span class="font-semibold">{{ friend.name }}</span>
                    <span class="text-[10px] opacity-75">({{ friend.joined_at }})</span>
                    <span class="rounded-sm bg-emerald-200/60 px-1 text-[9px] font-bold text-emerald-900 dark:bg-emerald-800/80 dark:text-emerald-100">
                        +{{ friend.reward_days }}d
                    </span>
                </div>
            </div>
        </div>
    </div>
</template>
