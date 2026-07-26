<?php

namespace App\Http\Requests\TimeLog;

class UpdateTimeLogRequest extends StoreTimeLogRequest
{
    public function rules(): array
    {
        return [
            'work_date' => ['sometimes', 'required', 'date', 'before_or_equal:today'],
            'duration_minutes' => ['sometimes', 'required', 'integer', 'min:1', 'max:1440'],
            'note' => ['sometimes', 'nullable', 'string', 'max:5000'],
        ];
    }
}
