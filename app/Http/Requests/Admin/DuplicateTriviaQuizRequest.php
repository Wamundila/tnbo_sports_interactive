<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DuplicateTriviaQuizRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'quiz_date' => ['required', 'date', Rule::unique('trivia_quizzes', 'quiz_date')],
            'title' => ['nullable', 'string', 'max:255'],
            'short_description' => ['nullable', 'string'],
            'opens_at' => ['nullable', 'date'],
            'closes_at' => ['nullable', 'date', 'after:opens_at'],
        ];
    }
}
