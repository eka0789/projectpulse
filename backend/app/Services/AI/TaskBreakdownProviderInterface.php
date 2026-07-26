<?php

namespace App\Services\AI;

interface TaskBreakdownProviderInterface
{
    public function generate(TaskBreakdownRequestData $request): TaskBreakdownResult;
}
