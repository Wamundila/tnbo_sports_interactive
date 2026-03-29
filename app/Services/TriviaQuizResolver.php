<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\TriviaQuiz;
use Illuminate\Support\Carbon;

class TriviaQuizResolver
{
    public function todayDate(): Carbon
    {
        return now()->startOfDay();
    }

    public function todayQuiz(): ?TriviaQuiz
    {
        return TriviaQuiz::query()
            ->with(['questions.options'])
            ->whereDate('quiz_date', $this->todayDate()->toDateString())
            ->first();
    }

    public function resolveStartableTodayQuiz(): TriviaQuiz
    {
        $quiz = $this->todayQuiz();

        if (! $quiz) {
            throw ApiException::conflict(
                "Today's trivia is not open yet.",
                'TRIVIA_NOT_OPEN'
            );
        }

        return $this->ensureQuizSubmittable($quiz);
    }

    public function ensureQuizSubmittable(TriviaQuiz $quiz): TriviaQuiz
    {
        if ($quiz->status === 'closed') {
            throw ApiException::conflict('This trivia quiz is closed.', 'TRIVIA_CLOSED');
        }

        if ($quiz->status !== 'published') {
            throw ApiException::conflict("Today's trivia is not open yet.", 'TRIVIA_NOT_OPEN');
        }

        if ($quiz->opens_at && $quiz->opens_at->isFuture()) {
            throw ApiException::conflict("Today's trivia is not open yet.", 'TRIVIA_NOT_OPEN');
        }

        if ($quiz->closes_at && $quiz->closes_at->isPast()) {
            throw ApiException::conflict('This trivia quiz is closed.', 'TRIVIA_CLOSED');
        }

        if ($quiz->questions->count() !== $quiz->question_count_expected) {
            throw ApiException::conflict("Today's trivia is not open yet.", 'TRIVIA_NOT_OPEN');
        }

        foreach ($quiz->questions as $question) {
            if ($question->options->count() < 2 || $question->options->where('is_correct', true)->count() !== 1) {
                throw ApiException::conflict("Today's trivia is not open yet.", 'TRIVIA_NOT_OPEN');
            }
        }

        return $quiz;
    }

    public function isAvailable(?TriviaQuiz $quiz): bool
    {
        if (! $quiz) {
            return false;
        }

        try {
            $this->ensureQuizSubmittable($quiz);

            return true;
        } catch (ApiException) {
            return false;
        }
    }
}



