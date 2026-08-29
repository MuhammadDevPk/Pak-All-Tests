# 💳 User Subscriptions & Payment Gating

This standard documents the database schema, subscription state machine, access gating workflows, and future commercial integrations for candidate course access.

---

## 1. Database Schema
* **Table**: `user_subscriptions`
* **Primary Key**: `id` (bigint, auto-increment)
* **user_id**: Constrained link to `users.id` (cascade on delete).
* **domain_id**: Constrained link to `domains.id` (cascade on delete).
* **status**: Subscription state string (`pending`, `active`, `rejected`, `expired`).
* **receipt_url**: Storage path pointing to the payment receipt screenshot or PDF (`storage/app/public/receipts/...`).
* **payment_method**: Gateway shorthand (`easypaisa`, `jazzcash`, `bank_transfer`).
* **transaction_id**: Transaction ID string extracted from SMS/receipt (indexed).
* **rejection_reason**: Rejection explanation text displayed to candidate if rejected by admin.
* **starts_at** / **expires_at**: Subscription validity timestamps.

---

## 2. Subscription State Machine & `SubscriptionManager` Service
All subscription transitions are handled by [`app/Services/SubscriptionManager.php`](file:///Users/muhammad/Personal/Projects/Personal%20Projects/Pak-All-Tests/app/Services/SubscriptionManager.php):
1. **`pending`**: Candidate uploads proof via [`PendingOrInactive.vue`](file:///Users/muhammad/Personal/Projects/Personal%20Projects/Pak-All-Tests/resources/js/pages/Subscription/PendingOrInactive.vue). Access is gated.
2. **`active`**: Admin verifies TRX ID & receipt proof via Filament review queue, then approves. Status transitions to `active`, `starts_at = now()`, `expires_at = now()->addYear()`, and rejection notes are cleared. Domain dashboard and test modules unlock immediately.
3. **`rejected`**: Admin rejects proof, recording a `rejection_reason`. Candidate is shown the rejection alert and prompted to re-upload proof.
4. **`expired`**: Timestamp exceeds `expires_at`. Auto-transitioned via `php artisan subscriptions:expire-lapsed` scheduled daily in [`routes/console.php`](file:///Users/muhammad/Personal/Projects/Personal%20Projects/Pak-All-Tests/routes/console.php).

---

## 3. Access Gating Architecture
Gating is enforced at the controller level via `SubscriptionManager`:
* **Domain Dashboard** (`GET /d/{domainSlug}`): Redirects to `Subscription/PendingOrInactive` if not active.
* **Content Module Fetch** (`GET /api/modules/{module}`): Returns 403 Forbidden if not active.
* **MCQ Execution & Grading** (`POST /api/modules/{module}/submit`): Returns 403 Forbidden if not active.

---

## 4. Post-MVP Commercial Expansion Roadmap (Deferred for Scale)

The following modules are deliberately staged for post-MVP deployment once commercial business registration and merchant accounts are acquired:

### A. Meta WhatsApp API & Transactional Email Notifications (Sub-Phase 4.4)
* **Trigger**: When `SubscriptionManager::activate()` or `reject()` executes.
* **Channels**:
  * **WhatsApp Cloud API**: Dispatching pre-approved Meta message templates to candidate's Pakistani mobile number (`+92...`) confirming course activation or rejection rationale.
  * **Transactional Mail**: Sending branded HTML email receipts via Laravel Mailables & Queues.

### B. EasyPaisa / JazzCash Direct Merchant IPN Webhooks (Sub-Phase 4.5)
* **Trigger**: Candidate pays directly through merchant checkout modal or mobile USSD push.
* **Webhook Endpoint**: `POST /api/webhooks/easypaisa` and `POST /api/webhooks/jazzcash`.
* **Security**: HMAC-SHA256 signature verification and IP whitelisting.
* **Action**: Calls `SubscriptionManager::activate()` instantly upon valid payment confirmation with zero human review needed.
