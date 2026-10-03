<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

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
});

// Payment Accounts Data
const paymentAccounts = {
    easypaisa: {
        name: 'EasyPaisa Mobile Account',
        accountTitle: 'Pak-All-Tests Academy',
        accountNumber: '0345-8899112',
        instructions: 'Send test enrollment fee to the EasyPaisa account above. Save the Transaction ID (TRX ID) from the confirmation SMS.'
    },
    jazzcash: {
        name: 'JazzCash Wallet',
        accountTitle: 'Pak-All-Tests Official',
        accountNumber: '0300-5544332',
        instructions: 'Transfer to the JazzCash mobile number above. Take a screenshot of the digital receipt upon successful payment.'
    },
    bank_transfer: {
        name: 'Meezan Bank Limited',
        accountTitle: 'Pak-All-Tests National Portal',
        accountNumber: '0102-0105489201',
        iban: 'PK45MEZN0001020105489201',
        instructions: 'Make an IBFT transfer to Meezan Bank. Attach the digital receipt screenshot or PDF bank statement.'
    }
};

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

    <div class="min-h-screen bg-background text-foreground flex flex-col justify-between p-4 sm:p-6 lg:p-10 selection:bg-primary/20 selection:text-primary">
        <!-- Top Header Navigation -->
        <header class="max-w-5xl mx-auto w-full flex items-center justify-between pb-6 border-b border-sidebar-border/70">
            <Link href="/" class="flex items-center gap-2.5">
                <div class="h-8 w-8 rounded-lg bg-primary flex items-center justify-center text-primary-foreground font-bold text-sm">
                    P
                </div>
                <span class="font-bold text-sm tracking-tight text-foreground">Pak-All-Tests Portal</span>
            </Link>
            <div class="flex items-center gap-3 text-xs">
                <Link href="/" class="text-muted-foreground hover:text-foreground transition-colors">
                    ← Back to Home
                </Link>
            </div>
        </header>

        <!-- Main Enrollment Card Layout -->
        <main class="max-w-4xl mx-auto w-full my-8">
            <!-- STATE 1: Pending Review -->
            <div
                v-if="subscriptionStatus === 'pending'"
                class="rounded-xl border border-sidebar-border bg-card p-6 sm:p-10 shadow-xs flex flex-col gap-6"
            >
                <div class="flex items-start gap-4">
                    <div class="h-10 w-10 shrink-0 rounded-lg bg-amber-500/10 text-amber-600 dark:text-amber-400 flex items-center justify-center text-xl font-bold font-mono">
                        ⏱️
                    </div>
                    <div class="flex flex-col gap-1">
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-mono uppercase tracking-wider font-semibold text-amber-600 dark:text-amber-400">
                                Verification In Progress
                            </span>
                        </div>
                        <h1 class="text-xl sm:text-2xl font-extrabold tracking-tight text-foreground">
                            Receipt Under Administrative Review
                        </h1>
                        <p class="text-xs sm:text-sm text-muted-foreground leading-relaxed">
                            Your payment submission for <span class="font-semibold text-foreground">{{ domain.name }}</span> has been received and queued in the verification review desk.
                        </p>
                    </div>
                </div>

                <!-- Transaction Details Card -->
                <div class="rounded-lg border border-sidebar-border/60 bg-sidebar/30 p-5 grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs font-mono">
                    <div class="flex flex-col gap-1">
                        <span class="text-muted-foreground uppercase text-[10px]">Payment Method:</span>
                        <span class="font-bold uppercase text-foreground">{{ subscription?.payment_method || 'EasyPaisa' }}</span>
                    </div>
                    <div class="flex flex-col gap-1">
                        <span class="text-muted-foreground uppercase text-[10px]">Transaction ID (TRX):</span>
                        <span class="font-bold text-primary">{{ subscription?.transaction_id || 'N/A' }}</span>
                    </div>
                    <div class="flex flex-col gap-1">
                        <span class="text-muted-foreground uppercase text-[10px]">Submitted:</span>
                        <span class="text-foreground">{{ subscription?.created_at || 'Just now' }}</span>
                    </div>
                </div>

                <div class="rounded-lg bg-primary/5 border border-primary/20 p-4 text-xs text-foreground/90 leading-relaxed">
                    💡 Our verification officers process payments round-the-clock (typically within 1 to 3 hours). Your dashboard will automatically unlock as soon as the bank reference is matched.
                </div>

                <div class="flex flex-wrap items-center justify-between gap-4 pt-4 border-t border-sidebar-border/60">
                    <Link
                        href="/"
                        class="rounded-lg border border-sidebar-border bg-card px-4 py-2.5 text-xs font-semibold text-foreground hover:bg-sidebar-accent transition-colors"
                    >
                        Browse Other Programs
                    </Link>
                    <a
                        :href="`/d/${domain.slug}`"
                        class="rounded-lg bg-primary px-5 py-2.5 text-xs font-semibold text-primary-foreground hover:bg-primary/90 transition-all active:scale-[0.98]"
                    >
                        Refresh Dashboard Status ⟳
                    </a>
                </div>
            </div>

            <!-- STATE 2: Checkout & Receipt Upload Form (none, rejected, expired) -->
            <div
                v-else
                class="flex flex-col gap-8"
            >
                <!-- Title & Domain Overview -->
                <div class="flex flex-col gap-2">
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-mono uppercase tracking-wider text-primary font-bold">Enrollment Checkout</span>
                        <span class="text-xs text-muted-foreground">•</span>
                        <span class="text-xs text-muted-foreground">Self-Service Bank Verification</span>
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-foreground">
                        Unlock Full Access to {{ domain.name }}
                    </h1>
                    <p class="text-xs sm:text-sm text-muted-foreground leading-relaxed">
                        Complete your payment using any Pakistani gateway below, then upload your transaction receipt for automated administrative activation.
                    </p>
                </div>

                <!-- Rejection Notice Banner (if previously rejected) -->
                <div
                    v-if="subscriptionStatus === 'rejected'"
                    class="rounded-xl border border-destructive/30 bg-destructive/10 p-5 flex items-start gap-3.5 text-destructive"
                >
                    <span class="text-lg">⚠️</span>
                    <div class="flex flex-col gap-1 text-xs">
                        <span class="font-bold text-sm">Previous Submission Requires Correction</span>
                        <p class="leading-relaxed text-destructive/90">
                            <strong>Reason from Admin:</strong> {{ subscription?.rejection_reason || 'The transaction ID could not be matched with bank statements. Please re-upload a clear receipt screenshot.' }}
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                    <!-- Left: Payment Instructions & Account Details -->
                    <div class="lg:col-span-5 flex flex-col gap-4">
                        <div class="rounded-xl border border-sidebar-border bg-card p-5 sm:p-6 shadow-xs flex flex-col gap-5">
                            <span class="text-xs font-mono uppercase tracking-wider text-muted-foreground font-semibold">
                                Step 1: Select Payment Gateway
                            </span>

                            <!-- Gateway Selector Pills -->
                            <div class="grid grid-cols-3 gap-2">
                                <button
                                    type="button"
                                    @click="selectGateway('easypaisa')"
                                    :class="[
                                        'flex flex-col items-center justify-center p-2.5 rounded-lg border text-xs font-medium transition-all',
                                        selectedGateway === 'easypaisa'
                                            ? 'border-primary bg-primary/10 text-primary font-bold shadow-xs'
                                            : 'border-sidebar-border/70 hover:border-sidebar-border text-muted-foreground'
                                    ]"
                                >
                                    <span>EasyPaisa</span>
                                </button>
                                <button
                                    type="button"
                                    @click="selectGateway('jazzcash')"
                                    :class="[
                                        'flex flex-col items-center justify-center p-2.5 rounded-lg border text-xs font-medium transition-all',
                                        selectedGateway === 'jazzcash'
                                            ? 'border-primary bg-primary/10 text-primary font-bold shadow-xs'
                                            : 'border-sidebar-border/70 hover:border-sidebar-border text-muted-foreground'
                                    ]"
                                >
                                    <span>JazzCash</span>
                                </button>
                                <button
                                    type="button"
                                    @click="selectGateway('bank_transfer')"
                                    :class="[
                                        'flex flex-col items-center justify-center p-2.5 rounded-lg border text-xs font-medium transition-all',
                                        selectedGateway === 'bank_transfer'
                                            ? 'border-primary bg-primary/10 text-primary font-bold shadow-xs'
                                            : 'border-sidebar-border/70 hover:border-sidebar-border text-muted-foreground'
                                    ]"
                                >
                                    <span>IBFT Bank</span>
                                </button>
                            </div>

                            <!-- Selected Account Details -->
                            <div class="rounded-lg border border-sidebar-border/60 bg-sidebar/30 p-4 flex flex-col gap-3.5 text-xs">
                                <div class="flex flex-col gap-1">
                                    <span class="text-[10px] font-mono uppercase text-muted-foreground">Account Name:</span>
                                    <span class="font-bold text-foreground text-sm">{{ paymentAccounts[selectedGateway].accountTitle }}</span>
                                </div>

                                <div class="flex flex-col gap-1">
                                    <span class="text-[10px] font-mono uppercase text-muted-foreground">Account / Mobile Number:</span>
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="font-mono text-base font-extrabold text-primary">
                                            {{ paymentAccounts[selectedGateway].accountNumber }}
                                        </span>
                                        <button
                                            type="button"
                                            @click="copyToClipboard(paymentAccounts[selectedGateway].accountNumber, 'acc')"
                                            class="rounded px-2 py-1 text-[11px] font-medium border border-sidebar-border bg-card hover:bg-sidebar-accent transition-colors"
                                        >
                                            {{ copiedField === 'acc' ? '✓ Copied' : 'Copy' }}
                                        </button>
                                    </div>
                                </div>

                                <div v-if="paymentAccounts[selectedGateway].iban" class="flex flex-col gap-1 pt-2 border-t border-sidebar-border/50">
                                    <span class="text-[10px] font-mono uppercase text-muted-foreground">IBAN:</span>
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="font-mono text-[11px] text-foreground break-all">
                                            {{ paymentAccounts[selectedGateway].iban }}
                                        </span>
                                        <button
                                            type="button"
                                            @click="copyToClipboard(paymentAccounts[selectedGateway].iban, 'iban')"
                                            class="rounded px-2 py-1 text-[11px] font-medium border border-sidebar-border bg-card hover:bg-sidebar-accent transition-colors shrink-0"
                                        >
                                            {{ copiedField === 'iban' ? '✓ Copied' : 'Copy' }}
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <p class="text-[11px] text-muted-foreground leading-relaxed">
                                {{ paymentAccounts[selectedGateway].instructions }}
                            </p>
                        </div>
                    </div>

                    <!-- Right: Submission Form -->
                    <div class="lg:col-span-7 flex flex-col gap-4">
                        <form
                            @submit.prevent="submitReceipt"
                            class="rounded-xl border border-sidebar-border bg-card p-5 sm:p-6 shadow-xs flex flex-col gap-5"
                        >
                            <span class="text-xs font-mono uppercase tracking-wider text-muted-foreground font-semibold">
                                Step 2: Upload Proof & Submit
                            </span>

                            <!-- Transaction ID Input -->
                            <div class="flex flex-col gap-1.5">
                                <label for="transaction_id" class="text-xs font-bold text-foreground flex items-center justify-between">
                                    <span>Transaction ID (TRX ID) *</span>
                                    <span class="text-[10px] font-mono text-muted-foreground font-normal">Found in bank SMS / receipt</span>
                                </label>
                                <input
                                    id="transaction_id"
                                    v-model="form.transaction_id"
                                    type="text"
                                    placeholder="e.g. 29481029381 or TRX-88910"
                                    class="w-full rounded-lg border border-sidebar-border/70 bg-sidebar/30 px-3.5 py-2.5 text-xs font-mono text-foreground placeholder:text-muted-foreground focus:outline-hidden focus:ring-2 focus:ring-primary"
                                    required
                                />
                                <span v-if="form.errors.transaction_id" class="text-[11px] text-destructive font-medium">
                                    {{ form.errors.transaction_id }}
                                </span>
                            </div>

                            <!-- Drag-and-Drop File Uploader -->
                            <div class="flex flex-col gap-1.5">
                                <label class="text-xs font-bold text-foreground">
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
                                                : 'border-sidebar-border hover:border-sidebar-border/80 bg-sidebar/20'
                                    ]"
                                >
                                    <!-- Selected File State -->
                                    <div v-if="form.receipt_file" class="flex flex-col items-center gap-2">
                                        <div v-if="previewUrl" class="h-28 w-auto max-w-full rounded border border-sidebar-border overflow-hidden mb-1">
                                            <img :src="previewUrl" alt="Receipt preview" class="h-full w-full object-contain" />
                                        </div>
                                        <span class="text-xs font-bold text-foreground font-mono break-all">{{ form.receipt_file.name }}</span>
                                        <span class="text-[10px] text-muted-foreground font-mono">
                                            {{ (form.receipt_file.size / 1024 / 1024).toFixed(2) }} MB
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
                                    <div v-else class="flex flex-col items-center gap-2">
                                        <div class="h-10 w-10 rounded-full bg-sidebar-accent flex items-center justify-center text-muted-foreground text-lg">
                                            📄
                                        </div>
                                        <span class="text-xs font-medium text-foreground">
                                            Drag & drop receipt screenshot here, or
                                        </span>
                                        <label class="cursor-pointer rounded-md bg-primary px-3.5 py-1.5 text-xs font-semibold text-primary-foreground hover:bg-primary/90 transition-colors shadow-xs">
                                            Browse Files
                                            <input
                                                ref="fileInput"
                                                type="file"
                                                accept="image/png,image/jpeg,image/jpg,image/webp,application/pdf"
                                                @change="handleFileChange"
                                                class="hidden"
                                            />
                                        </label>
                                        <span class="text-[10px] text-muted-foreground mt-1">
                                            Supports PNG, JPG, JPEG, PDF up to 10 MB
                                        </span>
                                    </div>
                                </div>
                                <span v-if="form.errors.receipt_file" class="text-[11px] text-destructive font-medium">
                                    {{ form.errors.receipt_file }}
                                </span>
                            </div>

                            <!-- Submit Action -->
                            <div class="pt-2">
                                <button
                                    type="submit"
                                    :disabled="form.processing || !form.receipt_file || !form.transaction_id"
                                    class="w-full rounded-lg bg-primary py-3 text-xs font-bold uppercase tracking-wider text-primary-foreground shadow-sm hover:bg-primary/90 transition-all active:scale-[0.98] disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    {{ form.processing ? 'Uploading Receipt...' : 'Submit Receipt for Verification →' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="max-w-5xl mx-auto w-full pt-6 border-t border-sidebar-border/70 text-center text-xs text-muted-foreground">
            Pak-All-Tests Verification Support Desk • Queries: support@paktests.pk
        </footer>
    </div>
</template>
