<?php

namespace App\Services\AI;

use App\Models\AITaskGeneration;
use Illuminate\Support\Facades\Log;

class TaskBreakdownService
{
    public function __construct(
        private readonly TaskBreakdownProviderInterface $provider
    ) {}

    public function generateTasks(TaskBreakdownRequestData $request): TaskBreakdownResult
    {
        $audit = $this->startAudit($request);
        $result = $this->provider->generate($request);
        $result->generationId = $audit?->id;

        if ($audit) {
            $audit->update([
                'provider' => $result->provider,
                'model' => $result->model,
                'response_payload' => $result->success
                    ? ['task_count' => count($result->tasks), 'source' => $result->source]
                    : null,
                'status' => $result->success
                    ? 'success'
                    : ($result->errorCode === 'AI_REQUEST_TIMEOUT' ? 'timeout' : 'failed'),
                'error_code' => $result->errorCode,
                'error_message' => $result->errorMessage,
                'latency_ms' => $result->latencyMs,
            ]);
        }

        Log::log($result->success ? 'info' : 'warning', 'AI task generation finished', [
            'generation_id' => $result->generationId,
            'provider' => $result->provider,
            'model' => $result->model,
            'success' => $result->success,
            'task_count' => count($result->tasks),
            'error_code' => $result->errorCode,
            'latency_ms' => $result->latencyMs,
        ]);

        return $result;
    }

    private function startAudit(TaskBreakdownRequestData $request): ?AITaskGeneration
    {
        if ($request->userId === null) {
            Log::warning('AI audit skipped because requester is missing.');

            return null;
        }

        try {
            $provider = (string) config('services.ai.provider', 'openai');
            $model = (string) config("services.ai.{$provider}.model", 'unknown');

            return AITaskGeneration::create([
                'project_id' => $request->projectId,
                'requested_by' => $request->userId,
                'provider' => $provider,
                'model' => $model,
                'brief_hash' => hash('sha256', $request->brief),
                'request_payload' => [
                    'brief_length' => mb_strlen($request->brief),
                    'preferences' => $request->preferences,
                ],
                'status' => 'pending',
            ]);
        } catch (\Throwable $exception) {
            Log::error('Failed to create AI generation audit', [
                'exception_class' => $exception::class,
            ]);

            return null;
        }
    }
}
