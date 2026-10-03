# FEATURES_BACKLOG.md — Product Roadmap & Strategic Feature Pipeline

> **Repository**: [Pak-All-Tests](file:///) (Competitive Examination & Intelligence Testing Engine)  
> **Status**: ACTIVE PRODUCT BACKLOG & ARCHITECTURAL SPECIFICATIONS  
> **Last Updated**: 2026-10-04  
> **Governing Standards**: [AGENTS.md](file:///AGENTS.md) • [.agent/ARCHITECTURE.md](file:///.agent/ARCHITECTURE.md) • [.agent/rules/ui_design.md](file:///.agent/rules/ui_design.md)

---

## 🧭 Executive Quick-Scan Matrix

This high-density summary table provides an immediate 30-second overview of all strategic product features in the pipeline. Click any **Feature ID** or **Feature Name** to jump directly to its complete architectural specification below.

| Feature ID | Feature Name | Core Value & Strategic Demand | Primary User Persona | Priority | Target Milestone |
| :--- | :--- | :--- | :--- | :---: | :---: |
| [**FEAT-001**](#feat-001--public-test-showcase-peer-learning-marketplace--social-achievement-cards) | **Public Test Showcase & Achievement Cards** | Candidate-controlled public test sharing, community mistake analysis, peer kudos, and viral WhatsApp/LinkedIn achievement image cards. | Aspirants (PMA/CSS), Visitors | `P1 — High` | Release 2.4 |
| [**FEAT-002**](#feat-002--spaced-repetition-scheduler--daily-weak-mcqs-drill) | **Spaced Repetition Scheduler & Daily Weak Drill** | Automated spaced revision intervals (1-day, 3-day, 7-day) for 2-strike weak questions with morning WhatsApp/browser reminders. | Repeat Examinees, Weak Scorers | `P1 — High` | Release 2.5 |
| [**FEAT-003**](#feat-003--real-time-examination-terminal-mode-pma--issb-desktop-client-replica) | **Military & Civil Terminal Exam Simulator** | 1:1 pixel-perfect fullscreen exam client mimicking official PMA Rawalpindi / FPSC testing lab computer interfaces with no back-clicks. | Candidates in final 14-day prep | `P2 — Med` | Release 2.6 |
| [**FEAT-004**](#feat-004--issb-situational-reaction--audio-voice-mentor) | **ISSB Audio & Situation Reaction Tutor** | Spoken voice interaction and 30-second Situation Reaction Test (SRT) timers with Socratic psychology grading. | ISSB / SSB Board Aspirants | `P2 — Med` | Release 2.7 |
| [**FEAT-005**](#feat-005--head-to-head-mcq-battle-arena--group-study-rooms) | **Head-to-Head Live MCQ Arena** | Real-time synchronized 1v1 and group speed-rounds over WebSockets/Reverb with live scoreboards. | Competitive study cohorts | `P3 — Low` | Release 3.0 |
| [**FEAT-006**](#feat-006--verified-pdf-performance-transcript--institutional-credentials) | **Verified PDF Progress Transcript & Verification QR** | Official cryptographically signed PDF progress transcripts showing syllabus mastery, pass rate, and public verification link. | Aspirants, Parents, Academies | `P3 — Low` | Release 3.1 |

---

## 🏗️ Detailed Feature Specifications

---

### [FEAT-001] — Public Test Showcase, Peer Learning Marketplace & Social Achievement Cards

> **Priority**: `P1 — High`  
> **Status**: `Ready for Refinement`  
> **Lead Architect**: Autonomous Governance (Muse / DeepMind Pair)  
> **Dependencies**: `McqExecutionController`, `UserWeakQuestion`, `SubscriptionManager`, `DomainDashboard`

#### 1. Strategic Intent & Candidate Value
Aspirants studying for competitive examinations (PMA Long Course, ISSB, CSS, MDCAT) lack transparent benchmarks to see how top performers tackle real questions, where peers stumble, and how actual exam times correlate with scores. Furthermore, candidates who score high (e.g. 90%+) have no seamless, branded mechanism to share their achievements on WhatsApp study groups, Instagram stories, or LinkedIn to build social proof and attract organic platform referrals.

This feature introduces:
1. **Granular Candidate Sovereignty**: Complete opt-in privacy toggles per test attempt. Tests remain 100% private by default; candidates explicitly choose which attempts to publish.
2. **Public Test Showcase & Peer Marketplace**: A community hub where visitors and enrolled aspirants can explore published attempts, study high-scoring breakdowns, review common mistakes, and exchange appreciation (kudos).
3. **Viral Social Achievement Card Generator**: An on-the-fly client-side/server-side image generator producing military-grade certificate cards optimized for mobile messaging apps.

```
[ Completed Test Attempt ]
            │
            ▼
    [ Opt-in Privacy Dialog ] ──▶ (Default: Private)
            │
      Candidate clicks: "Publish to Showcase"
            │
            ├──▶ [ Public Showcase Feed ] ───▶ Enrolled Candidates & Visitors
            │       • Domain & Subject Filter      • Inspect Mistake Breakdown
            │       • Time Taken & Score Tier      • Send Kudos / Appreciation Badge
            │
            └──▶ [ Social Share Card Engine ] ──▶ WhatsApp / LinkedIn Image
                    • Render Branded Visual Card
                    • Dynamic QR code verification link
```

#### 2. Candidate User Journey & UX Flow

1. **Post-Submission Privacy Gating**:
   - Immediately upon submitting a test or viewing a historical attempt, the candidate sees an interactive privacy toggle banner:
     - `🔒 Private Attempt (Only visible to you)` vs `🌐 Public on Community Showcase`.
   - A short explainer: *"Publishing allows fellow aspirants to learn from your question breakdown and results. Personal details like your email and password remain strictly confidential."*
2. **Public Feed Discovery (`/showcase` or `/community/tests`)**:
   - Filter by Domain (e.g., PMA Initial, CSS, MDCAT).
   - Filter by Subject (e.g., Islamic Studies, Pakistan Affairs, General Science).
   - Filter by Score Bracket (e.g., `Top Scorers (85%+)`, `Balanced Revision`, `Recent`).
   - Cards display: Candidate Avatar/Initials, Candidate Name (or customizable alias), Date, Score %, Pass/Fail status, Duration, and Kudos count.
3. **Attempt Inspection View (`/showcase/attempts/{uuid}`)**:
   - Visitors view the exact question feed, options chosen, correct solutions, and solution explanations.
   - Forfeited questions (revealed answers) are marked with the amber `⚠️ Revealed` badge.
   - Any sensitive candidate identifiers (email, phone, subscription details) are mathematically excluded from the API response.
4. **Appreciation & Kudos Reactions**:
   - Authenticated visitors can click reaction pills:
     - `👏 Outstanding (Kudos)`
     - `💡 Inspiring Pace`
     - `🎯 Great Revision Notes`
   - One reaction per user per attempt (idempotent).
5. **Achievement Social Card Generator**:
   - Candidate clicks **"📸 Share Achievement Card"**.
   - A modal renders a high-definition card preview (1080x1350 for stories or 1200x630 for landscape feed):
     - Gold/Emerald military insignia or academic crest.
     - Pak-All-Tests verified badge.
     - Candidate Name, Subject, Score Percentage, Passing Status, Accuracy, and Time.
     - Dynamic verification QR code pointing directly to `https://pakalltests.com/showcase/verify/{hash}`.
   - One-tap actions: `Download PNG`, `Copy Link`, `Share to WhatsApp`.

#### 3. Database Schema Blueprint

```sql
-- Migration: create_public_test_attempts_table
CREATE TABLE public_test_attempts (
    id BIGSERIAL PRIMARY KEY,
    uuid UUID NOT NULL UNIQUE DEFAULT gen_random_uuid(),
    user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    domain_id BIGINT NOT NULL REFERENCES domains(id) ON DELETE CASCADE,
    content_module_id BIGINT NOT NULL REFERENCES content_modules(id) ON DELETE CASCADE,
    
    is_public BOOLEAN NOT NULL DEFAULT FALSE,
    display_name VARCHAR(100) NOT NULL, -- Candidate name or pseudonym
    
    total_questions INT NOT NULL,
    answered_count INT NOT NULL,
    correct_count INT NOT NULL,
    score_percentage NUMERIC(5, 2) NOT NULL,
    passing_score NUMERIC(5, 2) NOT NULL DEFAULT 60.00,
    is_passed BOOLEAN NOT NULL,
    duration_seconds INT NOT NULL DEFAULT 0,
    
    -- Scrubbed snapshot of responses for public replay (zero correct-key leakage)
    submission_data JSONB NOT NULL,
    
    kudos_count INT NOT NULL DEFAULT 0,
    view_count INT NOT NULL DEFAULT 0,
    
    share_hash VARCHAR(64) UNIQUE,
    published_at TIMESTAMPTZ NULL,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_public_attempts_feed ON public_test_attempts (domain_id, content_module_id, is_public, score_percentage DESC);
CREATE INDEX idx_public_attempts_user ON public_test_attempts (user_id, is_public);

-- Migration: create_test_attempt_kudos_table (Idempotent peer reactions)
CREATE TABLE test_attempt_kudos (
    id BIGSERIAL PRIMARY KEY,
    public_attempt_id BIGINT NOT NULL REFERENCES public_test_attempts(id) ON DELETE CASCADE,
    user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    reaction_type VARCHAR(30) NOT NULL DEFAULT 'kudos', -- 'kudos', 'inspiring', 'helpful'
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    
    UNIQUE (public_attempt_id, user_id, reaction_type)
);
```

#### 4. Security, Zero-Leakage & Privacy Rules
- **Opt-In Only**: Column `is_public` defaults to `false`. No test is ever public without user affirmation.
- **Answer Key Protection**: The stored `submission_data` JSONB contains question text, choices, user's selected choice, and explanation. It strictly scrubs system prompts, internal question IDs, and admin meta.
- **PII Scrubbing**: Candidate email, phone numbers, TRX references, and team memberships are never exposed in public endpoints.
- **Rate-Limiting**: The Kudos reaction route is rate-limited (`30 requests/minute`) to prevent vote spamming.

---

### [FEAT-002] — Spaced Repetition Scheduler & Daily Weak MCQs Drill

> **Priority**: `P1 — High`  
> **Status**: `Under Design`  
> **Dependencies**: `UserWeakQuestion`, `2-Strike Threshold Mastery`, `NotificationManager`

#### 1. Strategic Intent & Candidate Value
The current 2-strike mastery engine isolates weak questions effectively. However, research into cognitive retention (Ebbinghaus Forgetting Curve) shows that students master questions in the short term but forget them after 3 to 7 days unless prompted for spaced review.

This feature schedules automatic reminder queues for active weak MCQs at intervals:
- **Interval 1**: 24 hours after first reveal or failure.
- **Interval 2**: 72 hours (3 days) after first correct attempt.
- **Interval 3**: 7 days after reaching 2/2 mastery (the "Final Retention Check").

#### 2. Key Capabilities
- **Daily 10-Minute Morning Drill**: Automatically compiles a dynamic mini-quiz containing exactly 10 questions scheduled for revision today.
- **Browser Push & WhatsApp Alerts**: Sends an opt-in summary: *"Good morning Candidate! You have 8 PMA Islamic Studies questions due for retention review today. Tap to practice (5 mins)."*
- **Retention Degradation Shield**: If a student fails a previously mastered question during the 7-day retention check, it safely reverts back to 1-strike (`consecutive_correct = 1`) rather than completely wiping progress.

---

### [FEAT-003] — Real-Time Examination Terminal Mode (PMA & ISSB Desktop Client Replica)

> **Priority**: `P2 — Med`  
> **Status**: `Under Design`  
> **Dependencies**: `McqPlayer.vue`, Fullscreen Browser API

#### 1. Strategic Intent & Candidate Value
Official Army Selection & Recruitment Centres (AS&RC) across Pakistan administer initial tests on dedicated desktop computers with specific legacy visual constraints: high-contrast navy/gray banners, strict non-resizable frames, disabled keyboard shortcuts, and strict countdown timers. Candidates frequently fail not because of syllabus ignorance, but due to interface shock and panic.

#### 2. Key Capabilities
- **Terminal Mode Toggle**: In `McqPlayer`, candidates can switch from modern responsive UI to **"AS&RC Simulation Terminal"**.
- **Security Lockout Emulation**: Enters fullscreen, suppresses browser back/forward gestures, disables F5/refresh, and forces mouse-driven selection.
- **Audio Metronome Option**: Subtle ticking sound during the final 60 seconds to build mental resilience under pressure.
- **Post-Exam Instant Status Sheet**: Emulates the official green screen *"CONGRATULATIONS: You have Qualified Academic & Intelligence Tests"* or red guidance screen.

---

### [FEAT-004] — ISSB Situational Reaction & Audio Voice Mentor

> **Priority**: `P2 — Med`  
> **Status**: `Proposed`  
> **Dependencies**: `AiTutorService`, Web Speech API / Whisper API

#### 1. Strategic Intent & Candidate Value
For ISSB (Inter Services Selection Board) candidates, psychological assessments and the Deputy President interview cannot be trained through MCQs alone. Candidates must rapidly articulate English responses to open-ended situational dilemmas (e.g. *"You are commanding a patrol and your communication set breaks down..."*).

#### 2. Key Capabilities
- **30-Second Situational Reaction Test (SRT)**: Candidate reads a crisis prompt and must record an audio answer or type within 30 seconds before auto-advancing.
- **Voice Socratic Analysis**: `AiTutorService` evaluates the response along 4 core leadership attributes (Decisiveness, Integrity, Emotional Stability, Resourcefulness).
- **Audio Feedback**: Text-to-speech feedback coaching the aspirant on tone, clarity, and grammatical precision.

---

### [FEAT-005] — Head-to-Head MCQ Battle Arena & Group Study Rooms

> **Priority**: `P3 — Low`  
> **Status**: `Future Concept`  
> **Dependencies**: Laravel Reverb / WebSockets, Redis Presence Channels

#### 1. Strategic Intent & Candidate Value
Aspirants study in informal WhatsApp cohorts and coaching academies. Adding live gamified 1v1 challenges accelerates engagement and study velocity.

#### 2. Key Capabilities
- **Synchronous 10-Question Duels**: Two candidates receive identical questions simultaneously.
- **Real-Time Speed vs Accuracy Scoring**: Bonus points awarded for fast correct answers.
- **Cohort Leaderboards**: Weekly rankings per academy, city, or domain.

---

### [FEAT-006] — Verified PDF Performance Transcript & Institutional Credentials

> **Priority**: `P3 — Low`  
> **Status**: `Future Concept`  
> **Dependencies**: `Barryvdh\DomPDF` / `Browsershot`, Cryptographic HMAC Signatures

#### 1. Strategic Intent & Candidate Value
Students and parents often request an official printable certificate or progress transcript to show to academy instructors or families before their official test date.

#### 2. Key Capabilities
- **Comprehensive Academic Transcript**: Detailed PDF with syllabus breakdown, accuracy percentages per subject, weak spots resolved, and total questions attempted.
- **Cryptographic Verification QR**: Each transcript has a tamper-proof SHA-256 digital stamp and QR code leading to a verification page on `pakalltests.com`.

---

## 📌 Implementation Readiness Criteria (Definition of Ready)

Before moving any feature from this backlog to active code development:
1. Formulate a dedicated implementation plan artifact (`<plan_name>.md`).
2. Run `/grill-me` alignment on ambiguous interaction states or data schemas.
3. Verify zero N+1 database queries, sub-200ms transaction compliance, and strict dark mode fidelity.
4. Establish Pest feature tests with happy, unauthorized, and edge-path assertions before marking complete.
