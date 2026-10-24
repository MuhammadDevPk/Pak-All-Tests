<script setup lang="ts">
import { Form, Head, router } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TeamInvitationAlert from '@/components/TeamInvitationAlert.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { login } from '@/routes';
import { store } from '@/routes/register';
import type { TeamInvitationContext } from '@/types';

const props = defineProps<{
    passwordRules: string;
    teamInvitation?: TeamInvitationContext | null;
    referralCode?: string | null;
}>();

defineOptions({
    layout: {
        title: 'Create an account',
        description: 'Enter your details below to create your account',
    },
});

const startGuestSession = () => {
    router.post('/guest-login');
};
</script>

<template>
    <Head title="Register" />

    <TeamInvitationAlert
        v-if="teamInvitation"
        :invitation="teamInvitation"
        action="Register"
    />

    <Form
        v-bind="store.form()"
        :reset-on-success="['password', 'password_confirmation']"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-6"
    >
        <div class="grid gap-6">
            <div class="grid gap-2">
                <Label for="name">Name</Label>
                <Input
                    id="name"
                    type="text"
                    required
                    autofocus
                    :tabindex="1"
                    autocomplete="name"
                    name="name"
                    placeholder="Full name"
                />
                <InputError :message="errors.name" />
            </div>

            <div class="grid gap-2">
                <Label for="email">Email address</Label>
                <Input
                    id="email"
                    type="email"
                    required
                    :tabindex="2"
                    autocomplete="email"
                    name="email"
                    placeholder="email@example.com"
                />
                <InputError :message="errors.email" />
            </div>

            <div class="grid gap-2">
                <Label for="password">Password</Label>
                <PasswordInput
                    id="password"
                    required
                    :tabindex="3"
                    autocomplete="new-password"
                    name="password"
                    placeholder="Password"
                    :passwordrules="passwordRules"
                />
                <InputError :message="errors.password" />
            </div>

            <div class="grid gap-2">
                <Label for="password_confirmation">Confirm password</Label>
                <PasswordInput
                    id="password_confirmation"
                    required
                    :tabindex="4"
                    autocomplete="new-password"
                    name="password_confirmation"
                    placeholder="Confirm password"
                    :passwordrules="passwordRules"
                />
                <InputError :message="errors.password_confirmation" />
            </div>

            <div class="grid gap-2">
                <div class="flex items-center justify-between">
                    <Label for="ref">Referral Code</Label>
                    <span class="text-xs text-muted-foreground">Optional</span>
                </div>
                <Input
                    id="ref"
                    type="text"
                    :tabindex="5"
                    autocomplete="off"
                    name="ref"
                    :default-value="props.referralCode || ''"
                    placeholder="e.g. PAK-HNYK3T"
                    class="font-mono tracking-wider uppercase placeholder:font-sans placeholder:tracking-normal placeholder:normal-case"
                />
                <p v-if="!errors.ref" class="text-[11px] text-muted-foreground">
                    Have an invitation code from a friend? Enter it to link
                    accounts.
                </p>
                <InputError :message="errors.ref" />
            </div>

            <Button
                type="submit"
                class="mt-2 w-full"
                tabindex="6"
                :disabled="processing"
                data-test="register-user-button"
            >
                <Spinner v-if="processing" />
                Create account
            </Button>
        </div>

        <!-- 1-Click Instant Guest Access -->
        <div class="relative flex items-center justify-center">
            <div class="absolute inset-0 flex items-center">
                <span
                    class="w-full border-t border-zinc-200 dark:border-zinc-800"
                ></span>
            </div>
            <span
                class="relative bg-white px-3 text-[11px] tracking-wider text-zinc-500 uppercase dark:bg-zinc-900 dark:text-zinc-400"
            >
                Or explore without sign up
            </span>
        </div>

        <button
            type="button"
            class="flex w-full items-center justify-center gap-2 rounded-xl border border-amber-300/80 bg-amber-500/10 py-2.5 text-center text-xs font-semibold text-amber-800 transition hover:bg-amber-500/20 active:scale-98 dark:border-amber-500/30 dark:bg-amber-950/40 dark:text-amber-300 dark:hover:bg-amber-950/60"
            data-test="guest-register-button"
            @click="startGuestSession"
        >
            <span>⚡ Instant 1-Day Guest Pass (No Registration)</span>
            <span>→</span>
        </button>

        <div class="text-center text-sm text-muted-foreground">
            Already have an account?
            <TextLink
                :href="
                    teamInvitation
                        ? login.url({
                              query: {
                                  invitation: teamInvitation.code,
                              },
                          })
                        : login()
                "
                class="underline underline-offset-4"
                :tabindex="7"
                data-test="team-invitation-login-link"
            >
                Log in
            </TextLink>
        </div>
    </Form>
</template>
