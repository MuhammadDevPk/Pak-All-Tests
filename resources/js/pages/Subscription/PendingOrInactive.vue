<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import ReferralCard from '@/components/referral/ReferralCard.vue';

const page = usePage();
const platform = computed(() => page.props.platform || {});

const props = defineProps({
    domain: {
        type: Object,
        required: true,
    },
    subscription: {
        type: Object,
        default: null,
    },
    subscriptionStatus: {
        type: String,
        default: 'none',
    },
    isSuspended: {
        type: Boolean,
        default: false,
    },
    suspensionReason: {
        type: String,
        default: null,
    },
    referral: {
        type: Object,
        default: null,
    },
});

// Payment Accounts Data (centralized with fallbacks)
const defaultPaymentAccounts = {
    easypaisa: {
        name: 'EasyPaisa Mobile Account',
        accountTitle: 'Pak-All-Tests Academy',
        accountNumber: '0345-8899112',
        instructions:
            'Send test enrollment fee to the EasyPaisa account above. Save the Transaction ID (TRX ID) from the confirmation SMS.',
    },
    jazzcash: {
        name: 'JazzCash Wallet',
        accountTitle: 'Pak-All-Tests Official',
        accountNumber: '0300-5544332',
        instructions:
            'Transfer to the JazzCash mobile number above. Take a screenshot of the digital receipt upon successful payment.',
    },
    bank_transfer: {
        name: 'Meezan Bank Limited',
        accountTitle: 'Pak-All-Tests National Portal',
        accountNumber: '0102-0105489201',
        iban: 'PK45MEZN0001020105489201',
        instructions:
            'Make an IBFT transfer to Meezan Bank. Attach the digital receipt screenshot or PDF bank statement.',
    },
};

const paymentAccounts = computed(
    () => platform.value.payment_accounts || defaultPaymentAccounts,
);

const selectedGateway = ref('easypaisa');
const copiedField = ref(null);
const fileInput = ref(null);
const previewUrl = ref(null);
const isDragging = ref(false);

const form = useForm({
    domain_id: props.domain.id,
    payment_method: 'easypaisa',
    transaction_id: '',
    receipt_file: null,
});

const selectGateway = (gateway) => {
    selectedGateway.value = gateway;
    form.payment_method = gateway;
};

const copyToClipboard = (text, fieldId) => {
    navigator.clipboard.writeText(text);
    copiedField.value = fieldId;
    setTimeout(() => {
        copiedField.value = null;
    }, 2000);
};

const handleFileChange = (e) => {
    const file = e.target.files[0];

    if (file) {
        setReceiptFile(file);
    }
};

const handleDrop = (e) => {
    isDragging.value = false;
    const file = e.dataTransfer?.files[0];

    if (file) {
        setReceiptFile(file);
    }
};

const setReceiptFile = (file) => {
    form.receipt_file = file;

    if (file.type.startsWith('image/')) {
        previewUrl.value = URL.createObjectURL(file);
    } else {
        previewUrl.value = null;
    }
};

const removeFile = () => {
    form.receipt_file = null;
    previewUrl.value = null;

    if (fileInput.value) {
        fileInput.value.value = '';
    }
};

const submitReceipt = () => {
    form.post('/subscription/receipt', {
        forceFormData: true,
        preserveScroll: true,
    });
};
</script>

<template>
    <Head :title="`Access Enrollment — ${domain.name}`" />

    <div
        class="flex min-h-screen flex-col justify-between bg-background p-4 text-foreground selection:bg-primary/20 selection:text-primary sm:p-6 lg:p-10"
    >
        <!-- Top Header Navigation -->
        <header
            class="mx-auto flex w-full max-w-5xl items-center justify-between border-b border-sidebar-border/70 pb-6"
        >
            <Link href="/" class="flex items-center gap-2.5">
                <div
                    class="flex h-8 w-8 items-center justify-center rounded-lg bg-primary text-sm font-bold text-primary-foreground"
                >
                    P
                </div>
                <span
                    class="text-sm font-bold tracking-tight text-foreground"
                    >{{ platform.portal_name || 'Pak-All-Tests Portal' }}</span
                >
            </Link>
            <div class="flex items-center gap-3 text-xs">
                <Link
                    href="/"
                    class="text-muted-foreground transition-colors hover:text-foreground"
                >
                    ← Back to Home
                </Link>
            </div>
        </header>

        <!-- Main Enrollment Card Layout -->
        <main class="mx-auto my-8 w-full max-w-4xl">
            <!-- STATE 0: Suspended Account -->
            <div
                v-if="isSuspended"
                class="flex flex-col gap-6 rounded-xl border border-destructive/40 bg-card p-6 shadow-xs sm:p-10"
            >
                <div class="flex items-start gap-4">
                    <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-destructive/10 font-mono text-xl font-bold text-destructive"
                    >
                        ⛔
                    </div>
                    <div class="flex flex-col gap-1">
                        <div class="flex items-center gap-2">
                            <span
                                class="font-mono text-xs font-semibold tracking-wider text-destructive uppercase"
                            >
                                Account Suspended
                            </span>
                        </div>
                        <h1
                            class="text-xl font-extrabold tracking-tight text-foreground sm:text-2xl"
                        >
                            Platform Access Restricted
                        </h1>
                        <p
                            class="text-xs leading-relaxed text-muted-foreground sm:text-sm"
                        >
                            Your candidate account has been restricted by
                            platform administration.
                            <span
                                v-if="suspensionReason"
                                class="mt-1 block font-semibold text-destructive"
                            >
                                Reason: {{ suspensionReason }}
                            </span>
                        </p>
                    </div>
                </div>

                <div
                    class="rounded-lg border border-destructive/20 bg-destructive/5 p-4 text-xs leading-relaxed text-foreground/90"
                >
                    If you believe this suspension is an error, please reach out
                    to the administrative support team via WhatsApp or the
                    portal helpdesk with your registered email address.
                </div>
            </div>

            <!-- STATE 1: Pending Review -->
            <div
                v-else-if="subscriptionStatus === 'pending'"
                class="flex flex-col gap-6 rounded-xl border border-sidebar-border bg-card p-6 shadow-xs sm:p-10"
            >
                <div class="flex items-start gap-4">
                    <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-amber-500/10 font-mono text-xl font-bold text-amber-600 dark:text-amber-400"
                    >
                        ⏱️
                    </div>
                    <div class="flex flex-col gap-1">
                        <div class="flex items-center gap-2">
                            <span
                                class="font-mono text-xs font-semibold tracking-wider text-amber-600 uppercase dark:text-amber-400"
                            >
                                Verification In Progress
                            </span>
                        </div>
                        <h1
                            class="text-xl font-extrabold tracking-tight text-foreground sm:text-2xl"
                        >
                            Receipt Under Administrative Review
                        </h1>
                        <p
                            class="text-xs leading-relaxed text-muted-foreground sm:text-sm"
                        >
                            Your payment submission for
                            <span class="font-semibold text-foreground">{{
                                domain.name
                            }}</span>
                            has been received and queued in the verification
                            review desk.
                        </p>
                    </div>
                </div>

                <!-- Transaction Details Card -->
                <div
                    class="grid grid-cols-1 gap-4 rounded-lg border border-sidebar-border/60 bg-sidebar/30 p-5 font-mono text-xs sm:grid-cols-3"
                >
                    <div class="flex flex-col gap-1">
                        <span
                            class="text-[10px] text-muted-foreground uppercase"
                            >Payment Method:</span
                        >
                        <span class="font-bold text-foreground uppercase">{{
                            subscription?.payment_method || 'EasyPaisa'
                        }}</span>
                    </div>
                    <div class="flex flex-col gap-1">
                        <span
                            class="text-[10px] text-muted-foreground uppercase"
                            >Transaction ID (TRX):</span
                        >
                        <span class="font-bold text-primary">{{
                            subscription?.transaction_id || 'N/A'
                        }}</span>
                    </div>
                    <div class="flex flex-col gap-1">
                        <span
                            class="text-[10px] text-muted-foreground uppercase"
                            >Submitted:</span
                        >
                        <span class="text-foreground">{{
                            subscription?.created_at || 'Just now'
                        }}</span>
                    </div>
                </div>

                <div
                    class="rounded-lg border border-primary/20 bg-primary/5 p-4 text-xs leading-relaxed text-foreground/90"
                >
                    💡 Our verification officers process payments
                    round-the-clock (typically within 1 to 3 hours). Your
                    dashboard will automatically unlock as soon as the bank
                    reference is matched.
                </div>

                <!-- Instant Access via Referral Alternative (while waiting) -->
                <div v-if="referral" class="flex flex-col gap-2 pt-2">
                    <div class="flex items-center gap-2">
                        <span
                            class="inline-flex items-center rounded-full bg-emerald-500/10 px-2.5 py-0.5 text-xs font-semibold text-emerald-600 dark:text-emerald-400"
                        >
                            Instant Access Alternative
                        </span>
                        <span class="text-xs font-semibold text-foreground">
                            Don't want to wait? Invite friends to unlock
                            immediately!
                        </span>
                    </div>
                    <ReferralCard :referral="referral" />
                </div>

                <div
                    class="flex flex-wrap items-center justify-between gap-4 border-t border-sidebar-border/60 pt-4"
                >
                    <Link
                        href="/"
                        class="rounded-lg border border-sidebar-border bg-card px-4 py-2.5 text-xs font-semibold text-foreground transition-colors hover:bg-sidebar-accent"
                    >
                        Browse Other Programs
                    </Link>
                    <a
                        :href="`/d/${domain.slug}`"
                        class="rounded-lg bg-primary px-5 py-2.5 text-xs font-semibold text-primary-foreground transition-all hover:bg-primary/90 active:scale-[0.98]"
                    >
                        Refresh Dashboard Status ⟳
                    </a>
                </div>
            </div>

            <!-- STATE 2: Checkout & Receipt Upload Form (none, rejected, expired) -->
            <div v-else class="flex flex-col gap-8">
                <!-- Title & Domain Overview -->
                <div class="flex flex-col gap-2">
                    <div class="flex items-center gap-2">
                        <span
                            class="font-mono text-xs font-bold tracking-wider text-primary uppercase"
                            >Enrollment Options</span
                        >
                        <span class="text-xs text-muted-foreground">•</span>
                        <span class="text-xs text-muted-foreground"
                            >Free Referral or Direct Payment</span
                        >
                    </div>
                    <h1
                        class="text-2xl font-extrabold tracking-tight text-foreground sm:text-3xl"
                    >
                        Unlock Full Access to {{ domain.name }}
                    </h1>
                    <p
                        class="text-xs leading-relaxed text-muted-foreground sm:text-sm"
                    >
                        Choose how you would like to continue: earn free trial
                        extensions by inviting classmates, or submit your
                        enrollment fee below.
                    </p>
                </div>

                <!-- OPTION A: Invite Friends (100% Free) -->
                <div v-if="referral" class="flex flex-col gap-2">
                    <div class="flex items-center gap-2">
                        <span
                            class="inline-flex items-center rounded-full bg-emerald-500/10 px-2.5 py-0.5 text-xs font-semibold text-emerald-600 dark:text-emerald-400"
                        >
                            Option 1 • 100% Free
                        </span>
                        <span class="text-xs font-semibold text-foreground">
                            Invite friends & get instant access (+7 days per
                            friend, 30 days for 5 friends)
                        </span>
                    </div>
                    <ReferralCard :referral="referral" />
                </div>

                <!-- Separator: Or Pay via Payment Gateways -->
                <div class="relative my-2 flex items-center justify-center">
                    <div class="absolute inset-0 flex items-center">
                        <div
                            class="w-full border-t border-sidebar-border"
                        ></div>
                    </div>
                    <div
                        class="relative bg-background px-4 text-xs font-semibold text-muted-foreground uppercase"
                    >
                        Or Option 2: Self-Service Payment
                    </div>
                </div>

                <!-- Rejection Notice Banner (if previously rejected) -->
                <div
                    v-if="subscriptionStatus === 'rejected'"
                    class="flex items-start gap-3.5 rounded-xl border border-destructive/30 bg-destructive/10 p-5 text-destructive"
                >
                    <span class="text-lg">⚠️</span>
                    <div class="flex flex-col gap-1 text-xs">
                        <span class="text-sm font-bold"
                            >Previous Submission Requires Correction</span
                        >
                        <p class="leading-relaxed text-destructive/90">
                            <strong>Reason from Admin:</strong>
                            {{
                                subscription?.rejection_reason ||
                                'The transaction ID could not be matched with bank statements. Please re-upload a clear receipt screenshot.'
                            }}
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-1 items-start gap-8 lg:grid-cols-12">
                    <!-- Left: Payment Instructions & Account Details -->
                    <div class="flex flex-col gap-4 lg:col-span-5">
                        <div
                            class="flex flex-col gap-5 rounded-xl border border-sidebar-border bg-card p-5 shadow-xs sm:p-6"
                        >
                            <span
                                class="font-mono text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                            >
                                Step 1: Select Payment Gateway
                            </span>

                            <!-- Gateway Selector Pills -->
                            <div class="grid grid-cols-3 gap-2">
                                <button
                                    type="button"
                                    @click="selectGateway('easypaisa')"
                                    :class="[
                                        'flex flex-col items-center justify-center rounded-lg border p-2.5 text-xs font-medium transition-all',
                                        selectedGateway === 'easypaisa'
                                            ? 'border-primary bg-primary/10 font-bold text-primary shadow-xs'
                                            : 'border-sidebar-border/70 text-muted-foreground hover:border-sidebar-border',
                                    ]"
                                >
                                    <span>EasyPaisa</span>
                                </button>
                                <button
                                    type="button"
                                    @click="selectGateway('jazzcash')"
                                    :class="[
                                        'flex flex-col items-center justify-center rounded-lg border p-2.5 text-xs font-medium transition-all',
                                        selectedGateway === 'jazzcash'
                                            ? 'border-primary bg-primary/10 font-bold text-primary shadow-xs'
                                            : 'border-sidebar-border/70 text-muted-foreground hover:border-sidebar-border',
                                    ]"
                                >
                                    <span>JazzCash</span>
                                </button>
                                <button
                                    type="button"
                                    @click="selectGateway('bank_transfer')"
                                    :class="[
                                        'flex flex-col items-center justify-center rounded-lg border p-2.5 text-xs font-medium transition-all',
                                        selectedGateway === 'bank_transfer'
                                            ? 'border-primary bg-primary/10 font-bold text-primary shadow-xs'
                                            : 'border-sidebar-border/70 text-muted-foreground hover:border-sidebar-border',
                                    ]"
                                >
                                    <span>IBFT Bank</span>
                                </button>
                            </div>

                            <!-- Selected Account Details -->
                            <div
                                class="flex flex-col gap-3.5 rounded-lg border border-sidebar-border/60 bg-sidebar/30 p-4 text-xs"
                            >
                                <div class="flex flex-col gap-1">
                                    <span
                                        class="font-mono text-[10px] text-muted-foreground uppercase"
                                        >Account Name:</span
                                    >
                                    <span
                                        class="text-sm font-bold text-foreground"
                                        >{{
                                            paymentAccounts[selectedGateway]
                                                .accountTitle
                                        }}</span
                                    >
                                </div>

                                <div class="flex flex-col gap-1">
                                    <span
                                        class="font-mono text-[10px] text-muted-foreground uppercase"
                                        >Account / Mobile Number:</span
                                    >
                                    <div
                                        class="flex items-center justify-between gap-2"
                                    >
                                        <span
                                            class="font-mono text-base font-extrabold text-primary"
                                        >
                                            {{
                                                paymentAccounts[selectedGateway]
                                                    .accountNumber
                                            }}
                                        </span>
                                        <button
                                            type="button"
                                            @click="
                                                copyToClipboard(
                                                    paymentAccounts[
                                                        selectedGateway
                                                    ].accountNumber,
                                                    'acc',
                                                )
                                            "
                                            class="rounded border border-sidebar-border bg-card px-2 py-1 text-[11px] font-medium transition-colors hover:bg-sidebar-accent"
                                        >
                                            {{
                                                copiedField === 'acc'
                                                    ? '✓ Copied'
                                                    : 'Copy'
                                            }}
                                        </button>
                                    </div>
                                </div>

                                <div
                                    v-if="paymentAccounts[selectedGateway].iban"
                                    class="flex flex-col gap-1 border-t border-sidebar-border/50 pt-2"
                                >
                                    <span
                                        class="font-mono text-[10px] text-muted-foreground uppercase"
                                        >IBAN:</span
                                    >
                                    <div
                                        class="flex items-center justify-between gap-2"
                                    >
                                        <span
                                            class="font-mono text-[11px] break-all text-foreground"
                                        >
                                            {{
                                                paymentAccounts[selectedGateway]
                                                    .iban
                                            }}
                                        </span>
                                        <button
                                            type="button"
                                            @click="
                                                copyToClipboard(
                                                    paymentAccounts[
                                                        selectedGateway
                                                    ].iban,
                                                    'iban',
                                                )
                                            "
                                            class="shrink-0 rounded border border-sidebar-border bg-card px-2 py-1 text-[11px] font-medium transition-colors hover:bg-sidebar-accent"
                                        >
                                            {{
                                                copiedField === 'iban'
                                                    ? '✓ Copied'
                                                    : 'Copy'
                                            }}
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <p
                                class="text-[11px] leading-relaxed text-muted-foreground"
                            >
                                {{
                                    paymentAccounts[selectedGateway]
                                        .instructions
                                }}
                            </p>
                        </div>
                    </div>

                    <!-- Right: Submission Form -->
                    <div class="flex flex-col gap-4 lg:col-span-7">
                        <form
                            @submit.prevent="submitReceipt"
                            class="flex flex-col gap-5 rounded-xl border border-sidebar-border bg-card p-5 shadow-xs sm:p-6"
                        >
                            <span
                                class="font-mono text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                            >
                                Step 2: Upload Proof & Submit
                            </span>

                            <!-- Transaction ID Input -->
                            <div class="flex flex-col gap-1.5">
                                <label
                                    for="transaction_id"
                                    class="flex items-center justify-between text-xs font-bold text-foreground"
                                >
                                    <span>Transaction ID (TRX ID) *</span>
                                    <span
                                        class="font-mono text-[10px] font-normal text-muted-foreground"
                                        >Found in bank SMS / receipt</span
                                    >
                                </label>
                                <input
                                    id="transaction_id"
                                    v-model="form.transaction_id"
                                    type="text"
                                    placeholder="e.g. 29481029381 or TRX-88910"
                                    class="w-full rounded-lg border border-sidebar-border/70 bg-sidebar/30 px-3.5 py-2.5 font-mono text-xs text-foreground placeholder:text-muted-foreground focus:ring-2 focus:ring-primary focus:outline-hidden"
                                    required
                                />
                                <span
                                    v-if="form.errors.transaction_id"
                                    class="text-[11px] font-medium text-destructive"
                                >
                                    {{ form.errors.transaction_id }}
                                </span>
                            </div>

                            <!-- Drag-and-Drop File Uploader -->
                            <div class="flex flex-col gap-1.5">
                                <label
                                    class="text-xs font-bold text-foreground"
                                >
                                    Payment Screenshot / PDF Receipt *
                                </label>

                                <div
                                    @dragover.prevent="isDragging = true"
                                    @dragleave.prevent="isDragging = false"
                                    @drop.prevent="handleDrop"
                                    :class="[
                                        'flex flex-col items-center justify-center rounded-lg border-2 border-dashed p-6 text-center transition-all',
                                        isDragging
                                            ? 'border-primary bg-primary/10'
                                            : form.receipt_file
                                              ? 'border-emerald-500/40 bg-emerald-500/5'
                                              : 'border-sidebar-border bg-sidebar/20 hover:border-sidebar-border/80',
                                    ]"
                                >
                                    <!-- Selected File State -->
                                    <div
                                        v-if="form.receipt_file"
                                        class="flex flex-col items-center gap-2"
                                    >
                                        <div
                                            v-if="previewUrl"
                                            class="mb-1 h-28 w-auto max-w-full overflow-hidden rounded border border-sidebar-border"
                                        >
                                            <img
                                                :src="previewUrl"
                                                alt="Receipt preview"
                                                class="h-full w-full object-contain"
                                            />
                                        </div>
                                        <span
                                            class="font-mono text-xs font-bold break-all text-foreground"
                                            >{{ form.receipt_file.name }}</span
                                        >
                                        <span
                                            class="font-mono text-[10px] text-muted-foreground"
                                        >
                                            {{
                                                (
                                                    form.receipt_file.size /
                                                    1024 /
                                                    1024
                                                ).toFixed(2)
                                            }}
                                            MB
                                        </span>
                                        <button
                                            type="button"
                                            @click="removeFile"
                                            class="mt-1 text-[11px] font-medium text-destructive hover:underline"
                                        >
                                            Remove & Choose Another
                                        </button>
                                    </div>

                                    <!-- Empty Uploader State -->
                                    <div
                                        v-else
                                        class="flex flex-col items-center gap-2"
                                    >
                                        <div
                                            class="flex h-10 w-10 items-center justify-center rounded-full bg-sidebar-accent text-lg text-muted-foreground"
                                        >
                                            📄
                                        </div>
                                        <span
                                            class="text-xs font-medium text-foreground"
                                        >
                                            Drag & drop receipt screenshot here,
                                            or
                                        </span>
                                        <label
                                            class="cursor-pointer rounded-md bg-primary px-3.5 py-1.5 text-xs font-semibold text-primary-foreground shadow-xs transition-colors hover:bg-primary/90"
                                        >
                                            Browse Files
                                            <input
                                                ref="fileInput"
                                                type="file"
                                                accept="image/png,image/jpeg,image/jpg,image/webp,application/pdf"
                                                @change="handleFileChange"
                                                class="hidden"
                                            />
                                        </label>
                                        <span
                                            class="mt-1 text-[10px] text-muted-foreground"
                                        >
                                            Supports PNG, JPG, JPEG, PDF up to
                                            10 MB
                                        </span>
                                    </div>
                                </div>
                                <span
                                    v-if="form.errors.receipt_file"
                                    class="text-[11px] font-medium text-destructive"
                                >
                                    {{ form.errors.receipt_file }}
                                </span>
                            </div>

                            <!-- Submit Action -->
                            <div class="pt-2">
                                <button
                                    type="submit"
                                    :disabled="
                                        form.processing ||
                                        !form.receipt_file ||
                                        !form.transaction_id
                                    "
                                    class="w-full rounded-lg bg-primary py-3 text-xs font-bold tracking-wider text-primary-foreground uppercase shadow-sm transition-all hover:bg-primary/90 active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    {{
                                        form.processing
                                            ? 'Uploading Receipt...'
                                            : 'Submit Receipt for Verification →'
                                    }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer
            class="mx-auto w-full max-w-5xl border-t border-sidebar-border/70 pt-6 text-center text-xs text-muted-foreground"
        >
            {{ platform.portal_name || 'Pak-All-Tests' }} Verification Support
            Desk • Queries:
            {{ platform.support_email || 'support@paktests.pk' }}
        </footer>
    </div>
</template>
