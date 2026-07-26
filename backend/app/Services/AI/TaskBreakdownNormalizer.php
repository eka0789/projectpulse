<?php

namespace App\Services\AI;

class TaskBreakdownNormalizer
{
    private const CATEGORIES = [
        'frontend',
        'backend',
        'design',
        'qa',
        'devops',
        'management',
        'other',
    ];

    private const PRIORITIES = ['low', 'medium', 'high', 'urgent'];

    public function normalize(array $rawTasks, int $maximumTasks = 15): array
    {
        $items = [];

        foreach (array_slice($rawTasks, 0, max(1, min($maximumTasks, 25))) as $raw) {
            if (! is_array($raw) || ! is_string($raw['title'] ?? null)) {
                continue;
            }

            $title = trim($raw['title']);
            if ($title === '') {
                continue;
            }

            $category = strtolower((string) ($raw['category'] ?? 'other'));
            if (! in_array($category, self::CATEGORIES, true)) {
                $category = 'other';
            }

            $priority = strtolower((string) ($raw['priority'] ?? 'medium'));
            if (! in_array($priority, self::PRIORITIES, true)) {
                $priority = 'medium';
            }

            $hours = max(0.5, min((float) ($raw['estimated_hours'] ?? 4), 80));
            $criteria = is_array($raw['acceptance_criteria'] ?? null)
                ? array_values(array_slice(array_filter(array_map(
                    fn (mixed $criterion): ?string => is_string($criterion)
                        ? mb_substr(trim($criterion), 0, 1000)
                        : null,
                    $raw['acceptance_criteria']
                )), 0, 20))
                : [];

            $items[] = (new TaskBreakdownItemData(
                temporaryId: 'ai-task-'.(count($items) + 1),
                title: mb_substr($title, 0, 255),
                description: is_string($raw['description'] ?? null)
                    ? mb_substr(trim($raw['description']), 0, 10000)
                    : null,
                category: $category,
                estimatedHours: $hours,
                priority: $priority,
                acceptanceCriteria: $criteria
            ))->toArray();
        }

        return $items;
    }

    public function demoTasks(): array
    {
        return [
            [
                'title' => 'Define solution architecture and delivery milestones',
                'description' => 'Document the application boundaries, data model, API contracts, and delivery risks.',
                'category' => 'management',
                'estimated_hours' => 6,
                'priority' => 'high',
                'acceptance_criteria' => [
                    'Architecture and data flow are documented',
                    'Milestones have measurable acceptance criteria',
                ],
            ],
            [
                'title' => 'Implement secure backend foundation',
                'description' => 'Build authentication, authorization, database migrations, validation, and API resources.',
                'category' => 'backend',
                'estimated_hours' => 12,
                'priority' => 'urgent',
                'acceptance_criteria' => [
                    'Role isolation is covered by automated tests',
                    'API errors follow the documented contract',
                ],
            ],
            [
                'title' => 'Build responsive administration workspace',
                'description' => 'Create the project management dashboard and CRUD workflows against the live API.',
                'category' => 'frontend',
                'estimated_hours' => 16,
                'priority' => 'high',
                'acceptance_criteria' => [
                    'All primary screens handle loading, empty, and error states',
                    'Forms provide accessible validation feedback',
                ],
            ],
            [
                'title' => 'Add automated quality and deployment checks',
                'description' => 'Cover critical workflows and validate container and Kubernetes deployment configuration.',
                'category' => 'devops',
                'estimated_hours' => 8,
                'priority' => 'medium',
                'acceptance_criteria' => [
                    'CI runs backend and frontend quality gates',
                    'Deployment manifests include probes and resource limits',
                ],
            ],
        ];
    }
}
