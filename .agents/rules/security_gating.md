# Security Gating & Route Protection Standards

## 1. Active Status Validation (Gating)
We implement two-tier gating checks to protect inactive resource routes from bypass attempts:
1. **Frontend Hydration**: The SDUI payload hydrator only queries and populates modules where `is_active = true`.
2. **API Endpoint Verification**: Controllers resolving individual module actions (such as fetching MCQ test lists or checking syllabus PDFs) MUST explicitly check that the module and its parent domain are active.

```php
if (!$module->is_active || !$module->domain()->where('is_active', true)->exists()) {
    abort(404, 'This module is not currently available.');
}
```

---

## 2. Subscription Restrictions
Any route displaying premium content or serving resource files must verify subscription states against:
* **Active Status**: `status = 'active'`
* **Expiration Gating**: Checks that `expires_at` is either null (lifetime access) or in the future:
```php
$user->subscriptions()
    ->where('domain_id', $domainId)
    ->where('status', 'active')
    ->where(function ($query) {
        $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
    });
```

---

## 3. Redirection Security
All links rendering target URLs on Vue pages that redirect to external destinations (such as cloud storage or third-party PDF viewers) MUST enforce browser security parameters:
* Always use `window.open(url, '_blank', 'noopener,noreferrer')` to prevent reverse-tab-nabbing and credentials leaks.
