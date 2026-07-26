<?php

namespace App\Http\Requests\AI;

use Illuminate\Foundation\Http\FormRequest;

class GenerateTaskBreakdownRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    public function rules(): array
    {
        return [
            'brief' => ['required', 'string', 'min:10', 'max:10000'],
            'preferences' => ['sometimes', 'array'],
            'preferences.include_qa' => ['sometimes', 'boolean'],
            'preferences.include_devops' => ['sometimes', 'boolean'],
            'preferences.maximum_tasks' => ['sometimes', 'integer', 'min:1', 'max:25'],
        ];
    }
}
