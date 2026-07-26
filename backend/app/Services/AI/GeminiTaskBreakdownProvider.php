<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiTaskBreakdownProvider implements TaskBreakdownProviderInterface
{
    public function generate(TaskBreakdownRequestData $request): TaskBreakdownResult
    {
        $startTime = microtime(true);
        $apiKey = config('services.ai.gemini.api_key', env('AI_API_KEY'));
        $model = config('services.ai.gemini.model', env('AI_MODEL', 'gemini-1.5-flash'));
        $timeout = (int) env('AI_TIMEOUT_SECONDS', 20);

        if (env('AI_DEMO_FALLBACK_ENABLED', false) && empty($apiKey)) {
            return $this->getDemoFallbackResult($startTime, 'gemini', $model);
        }

        if (empty($apiKey)) {
            $latencyMs = (int) ((microtime(true) - $startTime) * 1000);
            return new TaskBreakdownResult(
                success: false,
                provider: 'gemini',
                model: $model,
                latencyMs: $latencyMs,
                errorCode: 'AI_PROVIDER_UNAVAILABLE',
                errorMessage: 'Gemini API key is not configured.'
            );
        }

        try {
            $prompt = $this->buildPrompt($request->brief, $request->preferences);
            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

            $response = Http::timeout($timeout)->post($url, [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'responseMimeType' => 'application/json',
                    'temperature' => 0.7,
                ]
            ]);

            $latencyMs = (int) ((microtime(true) - $startTime) * 1000);

            if ($response->failed()) {
                Log::warning('Gemini task breakdown failed', [
                    'status' => $response->status(),
                    'error' => $response->body(),
                ]);

                if (env('AI_DEMO_FALLBACK_ENABLED', false)) {
                    return $this->getDemoFallbackResult($startTime, 'gemini', $model);
                }

                return new TaskBreakdownResult(
                    success: false,
                    provider: 'gemini',
                    model: $model,
                    latencyMs: $latencyMs,
                    errorCode: 'AI_PROVIDER_UNAVAILABLE',
                    errorMessage: 'Gemini request failed: ' . $response->status()
                );
            }

            $body = $response->json();
            $content = $body['candidates'][0]['content']['parts'][0]['text'] ?? '{}';
            $parsed = json_decode($content, true);

            $rawTasks = $parsed['tasks'] ?? [];
            $normalizedTasks = (new OpenAITaskBreakdownProvider())->normalizeTasks($rawTasks);

            return new TaskBreakdownResult(
                success: true,
                tasks: $normalizedTasks,
                provider: 'gemini',
                model: $model,
                latencyMs: $latencyMs
            );
        } catch (\Throwable $e) {
            $latencyMs = (int) ((microtime(true) - $startTime) * 1000);
            Log::error('Gemini exception', ['exception' => $e->getMessage()]);

            if (env('AI_DEMO_FALLBACK_ENABLED', false)) {
                return $this->getDemoFallbackResult($startTime, 'gemini', $model);
            }

            return new TaskBreakdownResult(
                success: false,
                provider: 'gemini',
                model: $model,
                latencyMs: $latencyMs,
                errorCode: 'AI_REQUEST_TIMEOUT',
                errorMessage: $e->getMessage()
            );
        }
    }

    private function buildPrompt(string $brief, array $preferences): string
    {
        $maxTasks = $preferences['maximum_tasks'] ?? 15;
        return <<<PROMPT
Given the following client brief:
"{$brief}"

Generate a list of up to {$maxTasks} software development tasks.
Respond with JSON format strictly matching this structure:
{
  "tasks": [
    {
      "title": "Short descriptive title",
      "description": "Detailed implementation note",
      "category": "frontend|backend|design|qa|devops|management|other",
      "estimated_hours": 8.0,
      "priority": "low|medium|high|urgent",
      "acceptance_criteria": ["Criterion 1", "Criterion 2"]
    }
  ]
}
PROMPT;
    }

    private function getDemoFallbackResult(float $startTime, string $provider, string $model): TaskBreakdownResult
    {
        $latencyMs = (int) ((microtime(true) - $startTime) * 1000);
        return new TaskBreakdownResult(
            success: true,
            tasks: (new OpenAITaskBreakdownProvider())->getDemoFallbackResult($startTime, $provider, $model)->tasks,
            provider: $provider,
            model: $model,
            latencyMs: $latencyMs,
            source: 'demo_fallback'
        );
    }
}
