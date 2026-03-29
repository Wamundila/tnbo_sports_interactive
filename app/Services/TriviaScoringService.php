<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\TriviaQuiz;
use Illuminate\Support\Carbon;

class TriviaScoringService
{
    public function scoreAttempt(TriviaQuiz $quiz, array $submittedAnswers): array
    {
        $answersByQuestionId = collect($submittedAnswers)->keyBy('question_id');
        $answerRows = [];
        $answerReview = [];
        $correctAnswers = 0;
        $wrongAnswers = 0;
        $unanswered = 0;
        $timeTakenMs = 0;

        foreach ($quiz->questions as $question) {
            $correctOption = $question->options->firstWhere('is_correct', true);

            if (! $correctOption) {
                throw ApiException::unprocessable('Quiz is missing a correct option.', 'TRIVIA_INVALID_CONFIGURATION');
            }

            $submitted = $answersByQuestionId->get($question->id);
            $selectedOptionId = $submitted['option_id'] ?? null;
            $selectedOption = $selectedOptionId
                ? $question->options->firstWhere('id', $selectedOptionId)
                : null;

            if ($selectedOptionId !== null && ! $selectedOption) {
                throw ApiException::unprocessable(
                    'One or more submitted options are invalid.',
                    'TRIVIA_INVALID_ANSWER_PAYLOAD'
                );
            }

            $isCorrect = $selectedOption?->id === $correctOption->id;

            if ($selectedOption === null) {
                $unanswered++;
            } elseif ($isCorrect) {
                $correctAnswers++;
            } else {
                $wrongAnswers++;
            }

            $responseTime = $submitted['response_time_ms'] ?? null;
            $timeTakenMs += is_int($responseTime) ? $responseTime : 0;

            $answerRows[] = [
                'trivia_question_id' => $question->id,
                'trivia_question_option_id' => $selectedOption?->id,
                'is_correct' => $isCorrect,
                'answered_at' => Carbon::now(),
                'response_time_ms' => $responseTime,
            ];

            $answerReview[] = [
                'question_id' => $question->id,
                'question_text' => $question->question_text,
                'selected_option_id' => $selectedOption?->id,
                'selected_option_text' => $selectedOption?->option_text,
                'correct_option_id' => $correctOption->id,
                'correct_option_text' => $correctOption->option_text,
                'is_correct' => $isCorrect,
                'explanation_text' => $question->explanation_text,
            ];
        }

        return [
            'answer_rows' => $answerRows,
            'answer_review' => $answerReview,
            'correct_answers_count' => $correctAnswers,
            'wrong_answers_count' => $wrongAnswers,
            'unanswered_count' => $unanswered,
            'score_base' => $correctAnswers * $quiz->points_per_correct,
            'time_taken_seconds' => (int) ceil($timeTakenMs / 1000),
        ];
    }

    public function validateAnswerOwnership(TriviaQuiz $quiz, array $submittedAnswers): void
    {
        $questionIds = $quiz->questions->pluck('id');

        foreach ($submittedAnswers as $answer) {
            if (! $questionIds->contains($answer['question_id'])) {
                throw ApiException::unprocessable(
                    'One or more submitted questions do not belong to this quiz.',
                    'TRIVIA_INVALID_ANSWER_PAYLOAD'
                );
            }

            $question = $quiz->questions->firstWhere('id', $answer['question_id']);
            $optionId = $answer['option_id'] ?? null;

            if ($optionId !== null && ! $question->options->pluck('id')->contains($optionId)) {
                throw ApiException::unprocessable(
                    'One or more submitted options do not belong to the referenced question.',
                    'TRIVIA_INVALID_ANSWER_PAYLOAD'
                );
            }
        }
    }
}
