<?php

namespace App\Http\Requests\Task;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    public function rules(): array
    {
        $nestedProject = $this->route('project');

        return [
            'project_id' => [
                $nestedProject ? 'nullable' : 'required',
                'integer',
                Rule::exists('projects', 'id')->whereNull('deleted_at'),
            ],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'category' => ['required', 'in:frontend,backend,design,qa,devops,management,other'],
            'assignee_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')
                    ->where('role', 'member')
                    ->where('is_active', true),
            ],
            'priority' => ['required', 'in:low,medium,high,urgent'],
            'status' => ['required', 'in:todo,in_progress,review,done'],
            'estimated_hours' => ['nullable', 'numeric', 'min:0.1', 'max:500'],
            'start_date' => ['nullable', 'date'],
            'deadline' => ['nullable', 'date', 'after_or_equal:start_date'],
        ];
    }
}
