# Project Progress & Status

## Completed Milestones

### 1. Foundational Infrastructure (Phase 1)
- Relational schemas configured (PostgreSQL, custom type domains with `ltree` paths, JSONB storage for dynamic schemas).
- Seeders built.

### 2. Administrative Engine (Phase 2)
- Filament resources built for domain management, content modules, question banks, and flow mappings.
- Custom JSON schemas designed to map UI structures.

### 3. Server-Driven UI (SDUI) Engine (Phase 3)
- **`SduiRenderer.vue`**: Universal parser that recursively maps backend schema objects to frontend components.
- **Structural Layouts**: `TabbedLayout.vue`, `PhaseStepperLayout.vue`, `SubjectGrid.vue`, `ResourceList.vue`, and `VerticalStack.vue`.
- **Interactive players**:
  - `McqPlayer.vue` (Timer-based exam engine with navigation pills, practice modes, and feedback).
  - `PdfViewer.vue` (Iframe viewer with fullscreen capabilities).
  - `SubjectiveUpload.vue` (Drag-and-drop submission tool for descriptive exams).
  - `ProgressCard.vue` (SVG circular visual progress ring displaying accuracy/completion rates).
- **Backend Pipelines**:
  - `DomainDashboardController.php` (Sub-millisecond query execution, eager-loaded relation mappings, and subscription checks).
  - `SduiPayloadTransformer.php` (Eagerly hydrates module nodes).
  - `McqExecutionController.php` (Secure grading endpoint verifying correct choices, passing score thresholds, and analytics summaries).
- **Quality Audited**: Verified template unwrapping syntax (no `.value` in templates) and resolved N+1 database queries.
- **Filament UI Upgraded**: Replaced restrictive flat `KeyValue` fields with pretty-printed, syntax-validated JSON `Textarea` editors across `DomainFlowForm.php`, `QuestionBankForm.php`, and `ContentModuleForm.php` to enable nesting of layout trees, question options arrays, and rules.
- **UI Design Guidelines Added**: Created [`.agents/rules/ui_design_guidelines.md`](file:///.agents/rules/ui_design_guidelines.md) enforcing senior product design principles, banning AI UI tropes (eyebrow pills, generic gradients, over-cardification, uniform radii), and mandating multi-device responsive standards (mobile, tablet, laptop, ultrawide).

---

### 4. Payment Verification & Delivery (Phase 4)
- **Sub-Phase 4.1 (Completed)**: 
  - Created `SubscriptionReceiptController.php` supporting receipt uploads (PNG, JPG, PDF up to 10MB) to public storage disk and state transitions.
  - Redesigned `PendingOrInactive.vue` with Pakistani payment gateways (EasyPaisa, JazzCash, Meezan Bank IBFT), one-click copy, drag-and-drop receipt upload, live image previews, and distinct status state handling (`pending`, `rejected`, `none`).
  - Automated test suite `SubscriptionReceiptTest.php` passing 100% (5 tests, 30 assertions).
- **Sub-Phase 4.2 (Completed)**:
  - Upgraded `UserSubscriptionForm.php` with a read-only, high-resolution receipt inspection preview and PDF download action.
  - Implemented one-click **Approve** and **Reject** review actions with reason modals and notifications in `UserSubscriptionsTable.php` and `EditUserSubscription.php`.
  - Added filter tabs (`Pending Verification`, `All`, `Active`, `Rejected`) and live navigation count badges in `ListUserSubscriptions.php` and `UserSubscriptionResource.php`.
  - Automated test suite `FilamentUserSubscriptionReviewTest.php` passing 100% (2 tests, 29 assertions).

- **Sub-Phase 4.3 (Completed)**:
  - Created `SubscriptionManager` service managing state machine transitions (`activate`, `reject`, `expireLapsedSubscriptions`, `hasActiveAccess`).
  - Added Eloquent scopes (`active`, `pending`, `rejected`, `expired`) and boolean status helpers to `UserSubscription` and `User`.
  - Created `ExpireLapsedSubscriptionsCommand` (`subscriptions:expire-lapsed`) registered in `routes/console.php` for daily scheduled batch expiration.
  - Standardized subscription gating across `DomainDashboardController`, `ContentModuleController`, and `McqExecutionController`.
  - Automated test suite `SubscriptionStateMachineTest.php` passing 100% (11 tests, 86 assertions across all subscription suites).

---

## Active Roadmap (Next Phase)

### Phase 4: Payment Verification & Delivery
- **Sub-Phase 4.4**: Queue Event notifications (Meta WhatsApp API templates and transactional emails).
- **Sub-Phase 4.5**: EasyPaisa / JazzCash Webhook receiver & signature verification.

### Phase 5: Specialized AI Chatbots & Sub-Agents
- Vectorization pipeline with pgvector.
- AI Tutor floating action button chat interface.
- WhatsApp inbound tutoring webhook handlers.
