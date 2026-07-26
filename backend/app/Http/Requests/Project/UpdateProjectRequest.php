<?php

namespace App\Http\Requests\Project;

use Illuminate\Validation\Rule;

class UpdateProjectRequest extends StoreProjectRequest
{
    public function rules(): array
    {
        return [
            'client_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('clients', 'id')->whereNull('deleted_at'),
            ],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:10000'],
            'client_brief' => ['sometimes', 'nullable', 'string', 'max:20000'],
            'start_date' => ['sometimes', 'nullable', 'date'],
            'deadline' => ['sometimes', 'required', 'date', 'after_or_equal:start_date'],
            'status' => ['sometimes', 'required', 'in:draft,active,on_hold,completed,cancelled'],
        ];
    }
}
