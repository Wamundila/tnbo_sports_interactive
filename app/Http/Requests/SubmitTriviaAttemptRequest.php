<?php

namespace App\Http\Requests;

use App\Exceptions\ApiException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class SubmitTriviaAttemptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'answers' => ['required', 'array', 'min:1'],
            'answers.*.question_id' => ['required', 'integer', 'distinct'],
            'answers.*.option_id' => ['nullable', 'integer'],
            'answers.*.response_time_ms' => ['nullable', 'integer', 'min:0'],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw ApiException::unprocessable(
            'The answer payload is invalid.',
            'TRIVIA_INVALID_ANSWER_PAYLOAD',
            $validator->errors()->toArray(),
        );
    }
}
