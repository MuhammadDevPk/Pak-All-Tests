<script setup lang="ts">
import { Form, Head, router } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import PasskeyVerify from '@/components/PasskeyVerify.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TeamInvitationAlert from '@/components/TeamInvitationAlert.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { register } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';
import type { TeamInvitationContext } from '@/types';

defineOptions({
    layout: {
        title: 'Log in to your account',
        description: 'Enter your email and password below to log in',
    },
});

defineProps<{
    status?: string;
    canResetPassword: boolean;
    teamInvitation?: TeamInvitationContext | null;
}>();

const startGuestSession = () => {
    router.post('/guest-login');
};
</script>

<template>
    <Head title="Log in" />

    <div
        v-if="status"
        class="mb-4 text-center text-sm font-medium text-green-600"
    >
        {{ status }}
    </div>

    <TeamInvitationAlert
        v-if="teamInvitation"
        :invitation="teamInvitation"
        action="Log in"
    />

    <PasskeyVerify />

    <Form
        v-bind="store.form()"
        :reset-on-success="['password']"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-6"
    >
        <div class="grid gap-6">
            <div class="grid gap-2">
                <Label for="email">Email address</Label>
                <Input
                    id="email"
                    type="email"
                    name="email"
                    required
                    autofocus
                    :tabindex="1"
                    autocomplete="email"
                    placeholder="email@example.com"
                />
                <InputError :message="errors.email" />
            </div>

            <div class="grid gap-2">
                <div class="flex items-center justify-between">
                    <Label for="password">Password</Label>
                    <TextLink
                        v-if="canResetPassword"
                        :href="request()"
                        class="text-sm"
                        :tabindex="5"
                    >
                        Forgot password?
                    </TextLink>
                </div>
                <PasswordInput
                    id="password"
                    name="password"
                    required
                    :tabindex="2"
                    autocomplete="current-password"
                    placeholder="Password"
                />
                <InputError :message="errors.password" />
            </div>

            <div class="flex items-center justify-between">
                <Label for="remember" class="flex items-center space-x-3">
                    <Checkbox id="remember" name="remember" :tabindex="3" />
                    <span>Remember me</span>
                </Label>
            </div>

            <Button
                type="submit"
                class="mt-4 w-full"
                :tabindex="4"
                :disabled="processing"
                data-test="login-button"
            >
                <Spinner v-if="processing" />
                Log in
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
            data-test="guest-login-button"
            @click="startGuestSession"
        >
            <span>⚡ Instant 1-Day Guest Pass (No Registration)</span>
            <span>→</span>
        </button>

        <div class="text-center text-sm text-muted-foreground">
            Don't have an account?
            <TextLink
                :href="
                    register({
                        query: {
                            invitation: teamInvitation?.code,
                        },
                    })
                "
                :tabindex="5"
                data-test="register-link"
            >
                Sign up
            </TextLink>
        </div>
    </Form>
</template>
