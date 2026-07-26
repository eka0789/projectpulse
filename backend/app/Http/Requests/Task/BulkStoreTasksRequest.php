<?php

namespace App\Http\Requests\Task;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkStoreTasksRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    public function rules(): array
    {
        return [
            'tasks' => ['required', 'array', 'min:1', 'max:50'],
            'tasks.*.title' => ['required', 'string', 'max:255'],
            'tasks.*.description' => ['nullable', 'string', 'max:10000'],
            'tasks.*.category' => ['required', 'in:frontend,backend,design,qa,devops,management,other'],
            'tasks.*.priority' => ['required', 'in:low,medium,high,urgent'],
            'tasks.*.estimated_hours' => ['nullable', 'numeric', 'min:0.5', 'max:500'],
            'tasks.*.assignee_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')
                    ->where('role', 'member')
                    ->where('is_active', true),
            ],
            'tasks.*.deadline' => ['nullable', 'date'],
            'tasks.*.source' => ['nullable', 'in:manual,ai'],
        ];
    }
}
