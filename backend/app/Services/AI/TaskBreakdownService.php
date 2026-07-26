<?php

namespace App\Services\AI;

use App\Models\AITaskGeneration;
use Illuminate\Support\Facades\Log;

class TaskBreakdownService
{
    public function generateTasks(TaskBreakdownRequestData $request): TaskBreakdownResult
    {
        $providerName = env('AI_PROVIDER', 'openai');
        $provider = match (strtolower($providerName)) {
            'gemini' => new GeminiTaskBreakdownProvider(),
            default => new OpenAITaskBreakdownProvider(),
        };

        $result = $provider->generate($request);

        // Audit log in database
        try {
            AITaskGeneration::create([
                'project_id' => $request->projectId,
                'requested_by' => $request->userId ?? auth()->id() ?? 1,
                'provider' => $result->provider,
                'model' => $result->model,
                'brief_hash' => hash('sha256', $request->brief),
                'request_payload' => [
                    'brief_length' => strlen($request->brief),
                    'preferences' => $request->preferences,
                ],
                'response_payload' => $result->success ? ['task_count' => count($result->tasks)] : null,
                'status' => $result->success ? 'success' : 'failed',
                'error_code' => $result->errorCode,
                'error_message' => $result->errorMessage,
                'latency_ms' => $result->latencyMs,
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to record AI task generation audit log', ['error' => $e->getMessage()]);
        }

        return $result;
    }
}
