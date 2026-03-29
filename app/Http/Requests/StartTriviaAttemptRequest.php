<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StartTriviaAttemptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'client' => ['nullable', 'string', 'max:50'],
        ];
    }
}
