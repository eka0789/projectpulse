<?php

namespace App\Http\Requests\ProgressNote;

use Illuminate\Foundation\Http\FormRequest;

class StoreProgressNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'note' => ['required', 'string', 'min:3', 'max:5000'],
        ];
    }
}
