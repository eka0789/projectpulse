<?php

namespace App\Providers;

use App\Services\AI\GeminiTaskBreakdownProvider;
use App\Services\AI\OpenAITaskBreakdownProvider;
use App\Services\AI\TaskBreakdownProviderInterface;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            TaskBreakdownProviderInterface::class,
            fn ($app) => match (config('services.ai.provider')) {
                'gemini' => $app->make(GeminiTaskBreakdownProvider::class),
                default => $app->make(OpenAITaskBreakdownProvider::class),
            }
        );
    }

    public function boot(): void
    {
        //
    }
}
