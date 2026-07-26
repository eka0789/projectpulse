# AI task-breakdown integration

`TaskBreakdownProviderInterface` isolates provider transport from `TaskBreakdownService`. The service currently supports OpenAI and Gemini selected by `AI_PROVIDER`.

## Flow

1. Admin submits a bounded client brief and generation preferences.
2. The service creates a pending `ai_task_generations` audit row.
3. The selected provider requests JSON with a timeout and bounded retry.
4. `TaskBreakdownNormalizer` validates arrays, truncates text, maps enums, bounds effort, limits task count, and assigns temporary IDs.
5. The API returns editable suggestions and the real audit generation ID.
6. The admin edits/removes/adds suggestions in the web review dialog.
7. Only `POST /projects/{project}/tasks/bulk` persists the reviewed list in a transaction.

AI failure never rolls back project creation or disables manual task CRUD.

## Configuration

```env
AI_PROVIDER=openai
AI_TIMEOUT_SECONDS=20
AI_DEMO_FALLBACK_ENABLED=false
OPENAI_API_KEY=
OPENAI_MODEL=gpt-4o-mini
GEMINI_API_KEY=
GEMINI_MODEL=gemini-2.0-flash
```

`AI_DEMO_FALLBACK_ENABLED=true` produces deterministic reviewable suggestions when credentials are absent. It is intended for offline demos, not to pretend a provider call occurred; the response includes its source.

## Failure contract

Provider configuration, timeout, transport, malformed JSON, and invalid response failures update the audit row and return a generic 503 API envelope. Secrets and raw HTTP exception bodies are not returned or logged.
