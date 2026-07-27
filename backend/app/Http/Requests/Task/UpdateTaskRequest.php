<?php

namespace App\Http\Requests\Task;

use Illuminate\Validation\Rule;

class UpdateTaskRequest extends StoreTaskRequest
{
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:10000'],
            'category' => ['sometimes', 'required', 'in:frontend,backend,design,qa,devops,management,other'],
            'assignee_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('users', 'id')
                    ->where('role', 'member')
                    ->where('is_active', true),
            ],
            'priority' => ['sometimes', 'required', 'in:low,medium,high,urgent'],
            'status' => ['sometimes', 'required', 'in:todo,in_progress,review,done'],
            'estimated_hours' => ['sometimes', 'nullable', 'numeric', 'min:0.1', 'max:500'],
            'start_date' => ['sometimes', 'nullable', 'date'],
            'deadline' => ['sometimes', 'nullable', 'date', 'after_or_equal:start_date'],
            'updated_at' => ['sometimes', 'required', 'date'],
        ];
    }
}
