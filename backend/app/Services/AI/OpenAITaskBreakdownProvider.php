<?php

namespace App\Services\AI;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAITaskBreakdownProvider implements TaskBreakdownProviderInterface
{
    public function __construct(
        private readonly TaskBreakdownNormalizer $normalizer
    ) {}

    public function generate(TaskBreakdownRequestData $request): TaskBreakdownResult
    {
        $startedAt = microtime(true);
        $apiKey = config('services.ai.openai.api_key');
        $model = (string) config('services.ai.openai.model');

        Log::info('AI task generation started', [
            'provider' => 'openai',
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
                ->withToken($apiKey)
                ->timeout((int) config('services.ai.timeout_seconds'))
                ->retry(2, 250, throw: false)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $model,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'Return only valid JSON containing a tasks array for a software delivery plan.',
                        ],
                        ['role' => 'user', 'content' => $this->buildPrompt($request)],
                    ],
                    'response_format' => ['type' => 'json_object'],
                    'temperature' => 0.3,
                ]);

            if ($response->failed()) {
                Log::warning('AI provider request failed', [
                    'provider' => 'openai',
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

            $content = $response->json('choices.0.message.content');
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
                provider: 'openai',
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
                'provider' => 'openai',
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
Generate at most {$request->maximumTasks()} tasks. Each task must include title,
description, category, estimated_hours, priority, and acceptance_criteria.
Allowed categories: frontend, backend, design, qa, devops, management, other.
Allowed priorities: low, medium, high, urgent.
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
            provider: 'openai',
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
            provider: 'openai',
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
