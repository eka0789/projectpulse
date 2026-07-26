<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAITaskBreakdownProvider implements TaskBreakdownProviderInterface
{
    public function generate(TaskBreakdownRequestData $request): TaskBreakdownResult
    {
        $startTime = microtime(true);
        $apiKey = config('services.ai.openai.api_key', env('AI_API_KEY'));
        $model = config('services.ai.openai.model', env('AI_MODEL', 'gpt-4o-mini'));
        $timeout = (int) env('AI_TIMEOUT_SECONDS', 20);

        if (env('AI_DEMO_FALLBACK_ENABLED', false) && empty($apiKey)) {
            return $this->getDemoFallbackResult($startTime, 'openai', $model);
        }

        if (empty($apiKey)) {
            $latencyMs = (int) ((microtime(true) - $startTime) * 1000);
            return new TaskBreakdownResult(
                success: false,
                provider: 'openai',
                model: $model,
                latencyMs: $latencyMs,
                errorCode: 'AI_PROVIDER_UNAVAILABLE',
                errorMessage: 'AI provider API key is not configured.'
            );
        }

        try {
            $prompt = $this->buildPrompt($request->brief, $request->preferences);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->timeout($timeout)->post('https://api.openai.com/v1/chat/completions', [
                'model' => $model,
                'messages' => [
                    ['role' => 'system', 'content' => 'You are an expert technical project manager. Analyze the client brief and respond strictly with JSON containing an array of project tasks.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'response_format' => ['type' => 'json_object'],
                'temperature' => 0.7,
            ]);

            $latencyMs = (int) ((microtime(true) - $startTime) * 1000);

            if ($response->failed()) {
                Log::warning('OpenAI task breakdown failed', [
                    'status' => $response->status(),
                    'error' => $response->body(),
                ]);

                if (env('AI_DEMO_FALLBACK_ENABLED', false)) {
                    return $this->getDemoFallbackResult($startTime, 'openai', $model);
                }

                return new TaskBreakdownResult(
                    success: false,
                    provider: 'openai',
                    model: $model,
                    latencyMs: $latencyMs,
                    errorCode: 'AI_PROVIDER_UNAVAILABLE',
                    errorMessage: 'OpenAI request failed: ' . $response->status()
                );
            }

            $body = $response->json();
            $content = $body['choices'][0]['message']['content'] ?? '{}';
            $parsed = json_decode($content, true);

            $rawTasks = $parsed['tasks'] ?? [];
            $normalizedTasks = $this->normalizeTasks($rawTasks);

            return new TaskBreakdownResult(
                success: true,
                tasks: $normalizedTasks,
                provider: 'openai',
                model: $model,
                latencyMs: $latencyMs
            );
        } catch (\Throwable $e) {
            $latencyMs = (int) ((microtime(true) - $startTime) * 1000);
            Log::error('OpenAI exception', ['exception' => $e->getMessage()]);

            if (env('AI_DEMO_FALLBACK_ENABLED', false)) {
                return $this->getDemoFallbackResult($startTime, 'openai', $model);
            }

            return new TaskBreakdownResult(
                success: false,
                provider: 'openai',
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

    private function normalizeTasks(array $rawTasks): array
    {
        $validCategories = ['frontend', 'backend', 'design', 'qa', 'devops', 'management', 'other'];
        $validPriorities = ['low', 'medium', 'high', 'urgent'];
        $items = [];
        $index = 1;

        foreach ($rawTasks as $raw) {
            if (empty($raw['title'])) continue;

            $category = strtolower($raw['category'] ?? 'other');
            if (!in_array($category, $validCategories)) $category = 'other';

            $priority = strtolower($raw['priority'] ?? 'medium');
            if (!in_array($priority, $validPriorities)) $priority = 'medium';

            $hours = (float) ($raw['estimated_hours'] ?? 4.0);
            if ($hours < 0.5) $hours = 0.5;
            if ($hours > 80) $hours = 80;

            $items[] = (new TaskBreakdownItemData(
                temporaryId: 'ai-task-' . $index++,
                title: substr(trim($raw['title']), 0, 255),
                description: $raw['description'] ?? null,
                category: $category,
                estimatedHours: $hours,
                priority: $priority,
                acceptanceCriteria: is_array($raw['acceptance_criteria'] ?? null) ? $raw['acceptance_criteria'] : []
            ))->toArray();
        }

        return $items;
    }

    private function getDemoFallbackResult(float $startTime, string $provider, string $model): TaskBreakdownResult
    {
        $latencyMs = (int) ((microtime(true) - $startTime) * 1000);
        $fallbackTasks = [
            [
                'temporary_id' => 'demo-task-1',
                'title' => 'Design & Wireframe Application Architecture',
                'description' => 'Create user interface layouts, database schema definitions, and system wireframes.',
                'category' => 'design',
                'estimated_hours' => 8.0,
                'priority' => 'high',
                'acceptance_criteria' => ['Wireframes approved by client', 'UI design tokens established']
            ],
            [
                'temporary_id' => 'demo-task-2',
                'title' => 'Implement Database Migrations & Models',
                'description' => 'Set up database tables for clients, projects, tasks, time logs, and authentication.',
                'category' => 'backend',
                'estimated_hours' => 10.0,
                'priority' => 'high',
                'acceptance_criteria' => ['Migrations execute without error', 'Eloquent relations tested']
            ],
            [
                'temporary_id' => 'demo-task-3',
                'title' => 'Build Authentication API & Role Controls',
                'description' => 'Implement Sanctum token login, registration, logout, and policy authorization.',
                'category' => 'backend',
                'estimated_hours' => 6.0,
                'priority' => 'urgent',
                'acceptance_criteria' => ['Sanctum token generation verified', 'Admin vs member policies active']
            ],
            [
                'temporary_id' => 'demo-task-4',
                'title' => 'Develop Web Admin Dashboard & Task Kanban',
                'description' => 'Build Next.js web dashboard, client/project management forms, and drag-and-drop Kanban.',
                'category' => 'frontend',
                'estimated_hours' => 12.0,
                'priority' => 'medium',
                'acceptance_criteria' => ['Dashboard statistics rendering', 'Kanban state transitions synced with API']
            ],
            [
                'temporary_id' => 'demo-task-5',
                'title' => 'Develop Mobile Member App (Ionic React)',
                'description' => 'Build mobile task list, task status update view, progress notes, and time log modal.',
                'category' => 'frontend',
                'estimated_hours' => 12.0,
                'priority' => 'medium',
                'acceptance_criteria' => ['Mobile layout responsive on Android', 'Member task isolation enforced']
            ],
        ];

        return new TaskBreakdownResult(
            success: true,
            tasks: $fallbackTasks,
            provider: $provider,
            model: $model,
            latencyMs: $latencyMs,
            source: 'demo_fallback'
        );
    }
}
