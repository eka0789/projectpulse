# ProjectPulse AI Task Breakdown Integration

## Overview

ProjectPulse features an AI Task Breakdown assistant that converts client briefs into actionable, categorized project tasks.

---

## Provider Abstraction Architecture

```php
interface TaskBreakdownProviderInterface
{
    public function generate(TaskBreakdownRequestData $request): TaskBreakdownResult;
}
```

Implementations:
- `OpenAITaskBreakdownProvider`: Connects to OpenAI API using structured JSON output prompts.
- `GeminiTaskBreakdownProvider`: Connects to Google Gemini REST API using structured JSON responses.

Configuration via `.env`:
```env
AI_PROVIDER=openai
AI_MODEL=gpt-4o-mini
AI_API_KEY=your-api-key-here
AI_TIMEOUT_SECONDS=20
AI_MAX_RETRIES=2
AI_MAX_TASKS=20
AI_DEMO_FALLBACK_ENABLED=false
```

---

## Safety & Resiliency Guarantees

1. **Schema Validation & Normalization**:
   - AI raw response is validated against strict JSON schema.
   - Category is normalized into one of `frontend`, `backend`, `design`, `qa`, `devops`, `management`, `other`.
   - Priority is mapped to `low`, `medium`, `high`, `urgent`.
   - Estimated hours are bounded between 0.5 and 80 hours.
2. **Audit Logging**:
   - Every AI request attempt is stored in `ai_task_generations` with SHA256 brief hash, latency, status (`success`, `failed`, `timeout`), provider, model, and error message. No sensitive API keys are logged.
3. **Non-Blocking User Experience**:
   - If AI fails or times out, the backend returns HTTP 503 / HTTP 200 (with `AI_PROVIDER_UNAVAILABLE` error payload).
   - Project creation is never aborted due to AI failure. Admin can review, modify, or manually add tasks.
