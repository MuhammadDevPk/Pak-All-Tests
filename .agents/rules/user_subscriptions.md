# 💳 User Subscriptions & Payment Gating

This standard documents the database schema, subscription state machine, and payment gating workflows for candidates.

---

## 1. Database Schema
* **Table**: `user_subscriptions`
* **Primary Key**: `id` (bigint, auto-increment)
* **user_id**: Constrained link to `users.id` (cascade on delete).
* **domain_id**: Constrained link to `domains.id` (cascade on delete).
* **status**: Subscription status state string (default: `pending`).
* **receipt_url**: Storage path pointing to the EasyPaisa/JazzCash transaction receipt screenshot.
* **payment_method**: Payment gateway shorthand (e.g. `easypaisa`, `jazzcash`, `bank_transfer`).
* **transaction_id**: Transaction ID string extracted from SMS/receipt (indexed).
* **rejection_reason**: Rejection explanation text displayed to candidate if rejected by admin.
* **starts_at** / **expires_at**: Subscription validity timestamps.

---

## 2. Subscription State Machine
Subscriptions follow a strict transition pattern:
1. **`pending`**: User uploads their payment receipt. Access to the domain dashboard is gated.
2. **`active`**: Admin reviews the transaction ID and receipt, then approves the subscription. Expiry dates are set. Dashboard access is permitted.
3. **`expired`**: The current date/time exceeds `expires_at`. Dashboard access is revoked.
4. **`rejected`**: Admin rejects the transaction, documenting a `rejection_reason`. Candidate is prompted to re-submit payment proof.

---

## 3. Access Gating Middlewares
Access to domain dashboards (`/d/{domainSlug}`) checks the candidate's active subscription:
* **Controller check**:
```php
$subscription = $user->subscriptions()
    ->where('domain_id', $domain->id)
    ->where('status', 'active')
    ->where(function ($query) {
        $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
    })
    ->first();

if (!$subscription) {
    return Inertia::render('Subscription/PendingOrInactive', [
        'domain' => $domain->only(['id', 'name', 'slug']),
        'subscriptionStatus' => $user->subscriptions()->where('domain_id', $domain->id)->first()?->status ?? 'none',
    ]);
}
```
* If no active subscription is found, candidates are gracefully redirected to the `Subscription/PendingOrInactive` Inertia landing screen displaying their current status (`pending`, `rejected`, or `none`).
