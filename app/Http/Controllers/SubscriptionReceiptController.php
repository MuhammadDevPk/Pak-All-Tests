<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use App\Models\UserSubscription;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class SubscriptionReceiptController extends Controller
{
    /**
     * Handle receipt upload and submit subscription for admin verification.
     */
    public function __invoke(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'domain_id' => ['required', 'integer', Rule::exists('domains', 'id')->where('is_active', true)],
            'payment_method' => ['required', 'string', Rule::in(['easypaisa', 'jazzcash', 'bank_transfer'])],
            'transaction_id' => ['required', 'string', 'max:100'],
            'receipt_file' => ['required', 'file', 'mimes:jpeg,png,jpg,pdf,webp', 'max:10240'],
        ]);

        $user = $request->user();
        $domain = Domain::findOrFail($validated['domain_id']);

        // Store file securely in public storage disk
        $path = $request->file('receipt_file')->store('receipts', 'public');

        // Find existing subscription or create new
        $subscription = UserSubscription::firstOrNew([
            'user_id' => $user->id,
            'domain_id' => $domain->id,
        ]);

        $subscription->status = 'pending';
        $subscription->payment_method = $validated['payment_method'];
        $subscription->transaction_id = trim($validated['transaction_id']);
        $subscription->receipt_url = $path;
        $subscription->rejection_reason = null; // Clear prior rejection reasons on new submission
        $subscription->starts_at = null;
        $subscription->expires_at = null;
        $subscription->save();

        return back()->with('success', 'Your payment receipt has been submitted successfully and is under review.');
    }
}
