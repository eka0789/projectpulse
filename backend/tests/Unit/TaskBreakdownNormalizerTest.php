<?php

namespace Tests\Unit;

use App\Services\AI\TaskBreakdownNormalizer;
use PHPUnit\Framework\TestCase;

class TaskBreakdownNormalizerTest extends TestCase
{
    public function test_it_normalizes_and_bounds_untrusted_provider_output(): void
    {
        $normalizer = new TaskBreakdownNormalizer;

        $tasks = $normalizer->normalize([
            [
                'title' => str_repeat('A', 300),
                'description' => str_repeat('B', 11000),
                'category' => 'unknown',
                'priority' => 'impossible',
                'estimated_hours' => 999,
                'acceptance_criteria' => ['Valid', 123, str_repeat('C', 1100)],
            ],
            ['title' => ''],
            'invalid',
        ], 10);

        $this->assertCount(1, $tasks);
        $this->assertSame('other', $tasks[0]['category']);
        $this->assertSame('medium', $tasks[0]['priority']);
        $this->assertSame(80.0, $tasks[0]['estimated_hours']);
        $this->assertSame(255, mb_strlen($tasks[0]['title']));
        $this->assertSame(10000, mb_strlen($tasks[0]['description']));
        $this->assertCount(2, $tasks[0]['acceptance_criteria']);
        $this->assertSame(1000, mb_strlen($tasks[0]['acceptance_criteria'][1]));
    }
}
