---
trigger: always_on
---

```markdown
# AI Agent Architecture & Socratic Tutor Guidelines

> **Status**: STRICT PRODUCTION ENGINEERING CONTRACT — ALWAYS ACTIVE
> **Version**: 3.0.0
> **Applicability**: All AI Agents, Socratic Mentors, Chatbots, Tool Callers, RAG Pipelines across Pak-All-Tests
> **Stack**: Laravel 11+ • Vue 3 + Inertia.js (Pure JavaScript only) • PostgreSQL 16+ + pgvector • Saloon • SSE

---

## 0. Non-Negotiable Rules

1. NEVER reveal the correct option letter or text of an active MCQ.
2. Pure JavaScript only in Vue AI widgets (`<script setup>`). No TypeScript.
3. Maximum 2 tool-calling rounds per request.
4. Do not invent tools beyond the ones defined in this document.
5. If a tool returns empty/error, tell the user honestly. Never hallucinate syllabus or current-affairs facts.
6. Correct MCQ answer must never appear in any frontend payload or streamed event.

---

## 1. Architectural Mandate & Stack Constraints

1. **Backend**: Laravel 11+ (Service + Repository layer, strict DB transactions, streaming responses).
2. **Frontend**: Vue 3 + Inertia.js using **Pure JavaScript** only. No `.ts` files, no `lang="ts"`.
3. **Database**: PostgreSQL 16+ with `pgvector` (`vector(1536)` HNSW cosine) and `ltree`.
4. **LLM Engine**: Multi-driver (`openai` → `gpt-4o-mini`, `gemini` → `gemini-1.5-flash`, `fake` for tests).
5. **Real-time**: Server-Sent Events (`text/event-stream`).

---

## 2. Intent Routing (Deterministic Priority)

Evaluate in this exact order:

1. **GREETING / SMALL TALK** (“hi”, “salam”, “aoa”, “who are you”)
   → Warm reply as domain mentor. **No tools**.

2. **PROBLEM SOLVING / SOCRATIC** (any question about the active MCQ, “why wrong”, “hint”, “explain”)
   → Socratic pedagogy only. **No tools**.

3. **EXAM STRUCTURE & GENERAL ADVISING** (“what is ISSB?”, “what are WAT and SRT?”, “how to prepare?”)
   → Use institutional knowledge + `search_domain_knowledge` if needed.

4. **KNOWLEDGE LOOKUP / SYLLABUS FACT** (“registration dates”, “medical standards”, “age limit”)
   → Call `search_domain_knowledge`.

**Fallback**: If ambiguous and `active_mcq` is present → treat as Socratic.

---

## 3. Tool Definitions (Exact Schemas + Return Shapes)

### Tool 1: `search_domain_knowledge`

```json
{
  "name": "search_domain_knowledge",
  "description": "Cosine similarity search against verified domain notes, syllabi, past papers, and military guidelines in pgvector.",
  "parameters": {
    "type": "object",
    "properties": {
      "query": {
        "type": "string",
        "description": "Semantic search query"
      },
      "domain_id": {
        "type": "integer",
        "description": "Domain ID to filter"
      },
      "limit": {
        "type": "integer",
        "description": "Max chunks (default 3, max 5)"
      }
    },
    "required": ["query", "domain_id"]
  }
}
```

**Strict return shape the backend must give the LLM:**

```json
{
  "status": "success" | "empty" | "error",
  "results": [
    {
      "content": "string",
      "source": "string",
      "score": 0.92
    }
  ],
  "message": "optional note"
}
```

### Tool 2: `get_mcq_details`

```json
{
  "name": "get_mcq_details",
  "description": "Retrieves statement, options and explanation rubric for a question. Never returns the correct answer to the client.",
  "parameters": {
    "type": "object",
    "properties": {
      "mcq_id": {
        "type": "integer"
      }
    },
    "required": ["mcq_id"]
  }
}
```

**Strict return shape:**

```json
{
  "status": "success" | "empty" | "error",
  "mcq": {
    "id": 123,
    "statement": "...",
    "options": ["A) ...", "B) ..."],
    "explanation_rubric": "conceptual notes only – no correct letter"
  }
}
```

> No other tools are allowed.

---

## 4. Frontend Context Contract (Pure JavaScript)

Endpoint: `POST /api/domains/{domainSlug}/ai-tutor/ask`

```js
{
  message: String,
  context: {
    current_domain_id: Number | null,
    current_domain_name: String | null,
    active_mcq: {
      id: Number,
      statement: String,
      options: Array,               // ["A) in", "B) at", ...]
      user_selected: String | null,
      attempts_made: Number         // >= 0
    } | null,
    student_level: String           // "beginner" | "intermediate" | "advanced"
  },
  chat_history: [
    { role: "user" | "assistant", content: String }
  ]
}
```

**CRITICAL**: Correct answer must never be present in this payload or in any client-side state.

---

## 5. Exact System Prompt (Inject This)

```
You are the elite AI Tutor and institutional mentor for Pak-All-Tests, a Pakistani competitive exam platform (PMA, ISSB, CSS, PPSC, MDCAT, etc.).

Language: Reply in clear English. Fully understand and may reply in Roman Urdu when the user writes in it.

### INTENT RULES
1. Greetings / small talk → warm mentor reply. No tools.
2. Questions about the active MCQ → Socratic method only. No tools.
3. Exam structure / advising → use your deep institutional knowledge + search_domain_knowledge if needed.
4. Syllabus facts / dates / standards → call search_domain_knowledge.
5. Maximum 2 tool rounds.

### SOCRATIC RULES (NEVER BREAK)
- NEVER state or imply the correct option letter or text.
- Level 1 (attempts_made 0-1): Give a Pattern Clue only.
- Level 2 (attempts_made 2): Distractor elimination + step logic.
- Level 3 (attempts_made >= 3): Stronger conceptual/formula hint, but still require the student to choose.
- If the user asks something unrelated, answer helpfully then gently return to the test.

### TOOL RULES
- Call tools only when the intent requires it.
- If a tool returns status "empty" or "error", tell the user honestly. Never invent facts.

### OUTPUT
Clean Markdown. Concise, authoritative, encouraging, disciplined.
```

---

## 6. Backend Execution Loop (Laravel Service)

`App\Services\AiTutorService` must follow this exact flow:

1. Validate payload + check `SubscriptionManager::hasActiveAccess`.
2. Build messages:
   - System prompt (section 5)
   - Hidden system message containing the correct option of `active_mcq` (only for the model)
   - Chat history (last 8–12 turns)
   - Current user message
3. Call LLM with the two tool schemas (max_tokens: 800, temperature ≈ 0.4).
4. If text → stream via SSE.
5. If tool_call:
   - Emit `tool_start`
   - Execute tool (3 s timeout)
   - Emit `tool_end`
   - Append tool result
   - Call LLM again (round 2 maximum)
6. Stream final answer.
7. Persist messages.
8. On error → emit `error` event.

Hard limit: 2 tool rounds. After that force a text response.

---

## 7. SSE Protocol (Exact Events)

```
Content-Type: text/event-stream
Cache-Control: no-cache, no-transform
Connection: keep-alive
X-Accel-Buffering: no
```

| Event        | Data example                                              | Frontend action                  |
|--------------|-----------------------------------------------------------|----------------------------------|
| `tool_start` | `{"tool":"search_domain_knowledge"}`                      | Show “Searching…” indicator     |
| `tool_end`   | `{"tool":"search_domain_knowledge","status":"success"}`   | Hide indicator                  |
| `chunk`      | `{"text":"partial markdown..."}`                          | Append to streaming bubble      |
| `done`       | `{}`                                                      | Close stream, re-enable input   |
| `error`      | `{"message":"Rate limit exceeded"}`                       | Show toast, close stream        |

- Every `data:` line = valid JSON.
- Escape newlines inside text (`\n` → `\\n`).
- Heartbeat comment every 15 s if idle.
- Always end with `event: done`.

---

## 8. Socratic Examples (Mandatory)

**BAD**
> You selected A. The correct answer is B.

**GOOD – Level 1**
> You chose A. Look at the relationship between force and acceleration when mass is constant. If force increases, what must happen to acceleration? Does option A respect that rule?

**GOOD – Level 3 (attempts_made >= 3)**
> You’ve tried this several times. Remember: Acceleration = Force ÷ Mass. Eliminate any option that would make acceleration decrease when force increases. Which remaining option fits?

---

## 9. Deep Domain Institutional Knowledge

### PMA Long Course Initial Test
- Location: Army Selection and Recruitment Centers (AS&RC).
- Phases:
  1. Verbal Intelligence Test: 84 MCQs in 30 minutes (analogies, number series, coding, odd-man-out).
  2. Non-Verbal Intelligence Test: 64 MCQs in 30 minutes (matrix reasoning, pattern completion, rotations).
  3. Academic Test: 50 MCQs in 30 minutes (English grammar, Mathematics, Pakistan Studies, Islamic Studies, General Knowledge).
  4. Physical Fitness Test: 1.6 km run in 8 minutes, 3 chin-ups, 15 push-ups, 20 sit-ups, 7'4" ditch crossing.
  5. Medical Examination & Initial Interview by AS&RC board prior to ISSB nomination.

### ISSB (Inter-Services Selection Board)
- 4-Day Evaluation (Kohat, Gujranwala, Malir, Quetta):
  - WAT (Word Association Test): 60 English words flashed for 10 seconds each; spontaneous, positive, natural sentences assessing subconscious Officer Like Qualities (OLQs).
  - SRT (Situation Reaction Test): 10 real-world high-stakes crisis scenarios in 10 minutes assessing practical common sense, decision speed, and courage.
  - TAT (Thematic Apperception Test): Picture stories and pointer stories.
  - GTO Tasks: Group Discussion, Lecturette, Progressive Group Task (PGT), Half Group Task (HGT), Command Task, Individual Obstacles, Final Group Task (FGT).
  - Deputy President Interview: Personal background, general knowledge, national issues, and rapid stress questions.

### CSS (Central Superior Services - FPSC)
- MPT: MCQ Preliminary Test of 200 marks (qualifying threshold 33%).
- Written Examination: 1200 marks (6 Compulsory subjects [English Essay, English Precis & Composition, General Science & Ability, Current Affairs, Pakistan Affairs, Islamic Studies] 600 marks + 6 Optionals 600 marks).
- Medical, Psychological Assessment & Viva Voce: 300 marks.

### MDCAT (PMDC)
- 200 MCQs covering Biology, Chemistry, Physics, English, and Logical Reasoning based on PMDC curriculum.

---

## 10. Security, Cost & Observability

- Rate limit: `throttle:15,1` (or stricter) + subscription check → 403 if inactive.
- CSRF: meta tag + `X-CSRF-TOKEN` on every request.
- Logging: user_id, domain_id, intent, tools called, latency, token usage.
- `max_tokens: 800`, max 2 tool rounds.
- Prompt-injection defense: reject or force Socratic mode on override attempts.
- SSE buffer flush only when `!app()->runningUnitTests()`.
- `phpunit.xml` forces `AI_CHAT_DRIVER=fake` and `AI_EMBEDDING_DRIVER=fake`.

---

## 11. Engineering Checklist (Must Pass)

- [ ] Pure JavaScript only in Vue AI components
- [ ] Correct MCQ answer never reaches frontend or streamed events
- [ ] Subscription gating returns 403
- [ ] CSRF protected
- [ ] Messages ordered correctly (id DESC then reverse)
- [ ] SSE events match the table in section 7
- [ ] Tool return shapes exactly as defined
- [ ] Max 2 tool rounds enforced
- [ ] Socratic ladder respects `attempts_made`
- [ ] Deterministic offline tests with fake drivers
- [ ] No hallucinated facts when tools return empty/error

---

**End of Contract**
Any implementation that deviates from the schemas, events, prompts, or pedagogical rules above is a bug.
```