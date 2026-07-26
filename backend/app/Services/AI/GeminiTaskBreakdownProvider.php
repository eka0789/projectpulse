<?php

namespace App\Services\AI;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiTaskBreakdownProvider implements TaskBreakdownProviderInterface
{
    public function __construct(
        private readonly TaskBreakdownNormalizer $normalizer
    ) {}

    public function generate(TaskBreakdownRequestData $request): TaskBreakdownResult
    {
        $startedAt = microtime(true);
        $apiKey = config('services.ai.gemini.api_key');
        $model = (string) config('services.ai.gemini.model');

        Log::info('AI task generation started', [
            'provider' => 'gemini',
            'model' => $model,
            'brief_hash' => hash('sha256', $request->brief),
        ]);

        if (blank($apiKey)) {
            return config('services.ai.demo_fallback_enabled')
                ? $this->demoResult($request, $startedAt, $model)
                : $this->failure(
                    $startedAt,
                    $model,
                    'AI_PROVIDER_UNAVAILABLE',
                    'The AI provider is not configured.'
                );
        }

        try {
            $response = Http::acceptJson()
                ->withQueryParameters(['key' => $apiKey])
                ->timeout((int) config('services.ai.timeout_seconds'))
                ->retry(2, 250, throw: false)
                ->post(
                    "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent",
                    [
                        'contents' => [[
                            'parts' => [['text' => $this->buildPrompt($request)]],
                        ]],
                        'generationConfig' => [
                            'responseMimeType' => 'application/json',
                            'temperature' => 0.3,
                        ],
                    ]
                );

            if ($response->failed()) {
                Log::warning('AI provider request failed', [
                    'provider' => 'gemini',
                    'status' => $response->status(),
                ]);

                return config('services.ai.demo_fallback_enabled')
                    ? $this->demoResult($request, $startedAt, $model)
                    : $this->failure(
                        $startedAt,
                        $model,
                        'AI_PROVIDER_UNAVAILABLE',
                        'The AI provider is temporarily unavailable.'
                    );
            }

            $content = $response->json('candidates.0.content.parts.0.text');
            $parsed = is_string($content) ? json_decode($content, true) : null;
            $tasks = is_array($parsed)
                ? $this->normalizer->normalize(
                    is_array($parsed['tasks'] ?? null) ? $parsed['tasks'] : [],
                    $request->maximumTasks()
                )
                : [];

            if ($tasks === []) {
                return $this->failure(
                    $startedAt,
                    $model,
                    'AI_INVALID_RESPONSE',
                    'The AI provider returned an invalid task list.'
                );
            }

            return new TaskBreakdownResult(
                success: true,
                tasks: $tasks,
                provider: 'gemini',
                model: $model,
                latencyMs: $this->latency($startedAt)
            );
        } catch (ConnectionException) {
            return $this->failure(
                $startedAt,
                $model,
                'AI_REQUEST_TIMEOUT',
                'The AI request timed out.'
            );
        } catch (\Throwable $exception) {
            Log::error('Unexpected AI provider exception', [
                'provider' => 'gemini',
                'exception_class' => $exception::class,
            ]);

            return $this->failure(
                $startedAt,
                $model,
                'AI_PROVIDER_UNAVAILABLE',
                'The AI provider is temporarily unavailable.'
            );
        }
    }

    private function buildPrompt(TaskBreakdownRequestData $request): string
    {
        $preferences = json_encode($request->preferences, JSON_THROW_ON_ERROR);

        return <<<PROMPT
Client brief:
{$request->brief}

Preferences: {$preferences}
Return JSON with a tasks array containing at most {$request->maximumTasks()} tasks.
Each task must have title, description, category, estimated_hours, priority,
and acceptance_criteria.
PROMPT;
    }

    private function demoResult(
        TaskBreakdownRequestData $request,
        float $startedAt,
        string $model
    ): TaskBreakdownResult {
        return new TaskBreakdownResult(
            success: true,
            tasks: $this->normalizer->normalize(
                $this->normalizer->demoTasks(),
                $request->maximumTasks()
            ),
            provider: 'gemini',
            model: $model,
            latencyMs: $this->latency($startedAt),
            source: 'demo_fallback'
        );
    }

    private function failure(
        float $startedAt,
        string $model,
        string $code,
        string $message
    ): TaskBreakdownResult {
        return new TaskBreakdownResult(
            success: false,
            provider: 'gemini',
            model: $model,
            latencyMs: $this->latency($startedAt),
            errorCode: $code,
            errorMessage: $message
        );
    }

    private function latency(float $startedAt): int
    {
        return (int) ((microtime(true) - $startedAt) * 1000);
    }
}
