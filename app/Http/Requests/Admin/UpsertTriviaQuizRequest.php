<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpsertTriviaQuizRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $quizId = $this->route('quiz')?->id;

        return [
            'quiz_date' => ['required', 'date', Rule::unique('trivia_quizzes', 'quiz_date')->ignore($quizId)],
            'title' => ['required', 'string', 'max:255'],
            'short_description' => ['nullable', 'string'],
            'existing_trivia_banner_url' => ['nullable', 'string', 'max:255'],
            'trivia_banner_upload' => ['nullable', 'file', 'image', 'max:5120'],
            'status' => ['nullable', Rule::in(['draft', 'scheduled', 'archived'])],
            'opens_at' => ['nullable', 'date'],
            'closes_at' => ['nullable', 'date', 'after:opens_at'],
            'question_count_expected' => ['nullable', 'integer', 'min:1', 'max:10'],
            'time_per_question_seconds' => ['nullable', 'integer', 'min:1', 'max:300'],
            'points_per_correct' => ['nullable', 'integer', 'min:1', 'max:100'],
            'streak_bonus_enabled' => ['nullable', 'boolean'],
            'sport_slug' => ['nullable', 'string', 'max:100'],
            'metadata' => ['nullable', 'array'],
            'questions' => ['nullable', 'array', 'max:20'],
            'questions.*.id' => ['nullable', 'integer', 'exists:trivia_questions,id'],
            'questions.*.position' => ['required', 'integer', 'min:1', 'distinct'],
            'questions.*.question_text' => ['required', 'string'],
            'questions.*.image_url' => ['nullable', 'url'],
            'questions.*.explanation_text' => ['nullable', 'string'],
            'questions.*.source_type' => ['nullable', 'string', 'max:50'],
            'questions.*.source_ref' => ['nullable', 'string', 'max:255'],
            'questions.*.difficulty' => ['nullable', Rule::in(['easy', 'medium', 'hard'])],
            'questions.*.sport_slug' => ['nullable', 'string', 'max:100'],
            'questions.*.status' => ['nullable', Rule::in(['draft', 'active', 'retired'])],
            'questions.*.options' => ['nullable', 'array', 'max:3'],
            'questions.*.options.*.id' => ['nullable', 'integer', 'exists:trivia_question_options,id'],
            'questions.*.options.*.position' => ['required', 'integer', 'min:1'],
            'questions.*.options.*.option_text' => ['required', 'string'],
            'questions.*.options.*.is_correct' => ['nullable', 'boolean'],
        ];
    }
}
