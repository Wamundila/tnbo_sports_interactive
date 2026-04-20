<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\Admin;
use App\Models\TriviaQuestion;
use App\Models\TriviaQuestionOption;
use App\Models\TriviaQuiz;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class AdminTriviaQuizService
{
    public function __construct(private readonly AdminTriviaActivityLogger $activityLogger)
    {
    }

    public function create(array $payload, Admin $admin): TriviaQuiz
    {
        return DB::transaction(function () use ($payload, $admin): TriviaQuiz {
            $quiz = TriviaQuiz::create([
                ...$this->quizAttributes($payload),
                'created_by_admin_id' => $admin->id,
            ]);

            $this->syncQuestions($quiz, $payload['questions'] ?? []);
            $this->activityLogger->log($admin, 'quiz_created', TriviaQuiz::class, $quiz->id, [
                'quiz_date' => $quiz->quiz_date?->toDateString(),
            ]);

            return $quiz->fresh(['questions.options']);
        });
    }

    public function update(TriviaQuiz $quiz, array $payload, Admin $admin): TriviaQuiz
    {
        return DB::transaction(function () use ($quiz, $payload, $admin): TriviaQuiz {
            $quiz->fill($this->quizAttributes($payload));
            $quiz->save();

            if (array_key_exists('questions', $payload)) {
                $this->syncQuestions($quiz, $payload['questions']);
            }

            $this->activityLogger->log($admin, 'quiz_updated', TriviaQuiz::class, $quiz->id);

            return $quiz->fresh(['questions.options']);
        });
    }

    public function publish(TriviaQuiz $quiz, Admin $admin): TriviaQuiz
    {
        $quiz->loadMissing('questions.options');
        $this->assertPublishable($quiz);

        $quiz->forceFill([
            'status' => 'published',
            'published_at' => now(),
            'published_by_admin_id' => $admin->id,
        ])->save();

        $this->activityLogger->log($admin, 'quiz_published', TriviaQuiz::class, $quiz->id);

        return $quiz->fresh(['questions.options']);
    }

    public function close(TriviaQuiz $quiz, Admin $admin): TriviaQuiz
    {
        $quiz->forceFill(['status' => 'closed'])->save();
        $this->activityLogger->log($admin, 'quiz_closed', TriviaQuiz::class, $quiz->id);

        return $quiz->fresh(['questions.options']);
    }

    public function duplicate(TriviaQuiz $quiz, array $payload, Admin $admin): TriviaQuiz
    {
        $quiz->loadMissing('questions.options');

        return DB::transaction(function () use ($quiz, $payload, $admin): TriviaQuiz {
            $newQuiz = TriviaQuiz::create([
                'quiz_date' => $payload['quiz_date'],
                'title' => $payload['title'] ?? $quiz->title,
                'short_description' => $payload['short_description'] ?? $quiz->short_description,
                'trivia_banner_url' => $quiz->trivia_banner_url,
                'status' => 'draft',
                'opens_at' => $payload['opens_at'] ?? $quiz->opens_at,
                'closes_at' => $payload['closes_at'] ?? $quiz->closes_at,
                'question_count_expected' => $quiz->question_count_expected,
                'time_per_question_seconds' => $quiz->time_per_question_seconds,
                'points_per_correct' => $quiz->points_per_correct,
                'streak_bonus_enabled' => $quiz->streak_bonus_enabled,
                'sport_slug' => $quiz->sport_slug,
                'metadata' => $quiz->metadata,
                'created_by_admin_id' => $admin->id,
            ]);

            foreach ($quiz->questions as $question) {
                $newQuestion = $newQuiz->questions()->create([
                    'position' => $question->position,
                    'question_text' => $question->question_text,
                    'image_url' => $question->image_url,
                    'explanation_text' => $question->explanation_text,
                    'source_type' => $question->source_type,
                    'source_ref' => $question->source_ref,
                    'difficulty' => $question->difficulty,
                    'sport_slug' => $question->sport_slug,
                    'status' => $question->status,
                ]);

                foreach ($question->options as $option) {
                    $newQuestion->options()->create([
                        'position' => $option->position,
                        'option_text' => $option->option_text,
                        'is_correct' => $option->is_correct,
                    ]);
                }
            }

            $this->activityLogger->log($admin, 'quiz_duplicated', TriviaQuiz::class, $newQuiz->id, [
                'source_quiz_id' => $quiz->id,
            ]);

            return $newQuiz->fresh(['questions.options']);
        });
    }

    public function assertPublishable(TriviaQuiz $quiz): void
    {
        if (! $quiz->opens_at || ! $quiz->closes_at || ! $quiz->opens_at->lt($quiz->closes_at)) {
            throw ApiException::unprocessable('Quiz opens_at must be before closes_at.', 'TRIVIA_QUIZ_INVALID');
        }

        $activeQuestions = $quiz->questions->where('status', 'active')->values();

        if ($activeQuestions->count() !== $quiz->question_count_expected) {
            throw ApiException::unprocessable(
                'Quiz must have exactly the expected number of active questions before publish.',
                'TRIVIA_QUIZ_INVALID'
            );
        }

        foreach ($activeQuestions as $question) {
            if ($question->options->count() !== 3) {
                throw ApiException::unprocessable('Each active question must have exactly 3 options.', 'TRIVIA_QUIZ_INVALID');
            }

            if ($question->options->where('is_correct', true)->count() !== 1) {
                throw ApiException::unprocessable('Each active question must have exactly 1 correct option.', 'TRIVIA_QUIZ_INVALID');
            }
        }
    }

    private function quizAttributes(array $payload): array
    {
        return Arr::only($payload, [
            'quiz_date',
            'title',
            'short_description',
            'trivia_banner_url',
            'status',
            'opens_at',
            'closes_at',
            'question_count_expected',
            'time_per_question_seconds',
            'points_per_correct',
            'streak_bonus_enabled',
            'sport_slug',
            'metadata',
        ]);
    }

    private function syncQuestions(TriviaQuiz $quiz, array $questions): void
    {
        $existing = $quiz->questions()->with('options')->get()->keyBy('id');
        $incomingIds = collect($questions)->pluck('id')->filter()->map(fn ($id) => (int) $id);
        $deleteIds = $existing->keys()->diff($incomingIds);

        if ($deleteIds->isNotEmpty()) {
            TriviaQuestion::query()->whereIn('id', $deleteIds)->delete();
        }

        foreach ($questions as $questionPayload) {
            $question = isset($questionPayload['id'])
                ? $existing->get((int) $questionPayload['id'])
                : null;

            if (isset($questionPayload['id']) && ! $question) {
                throw ApiException::unprocessable('Question does not belong to the selected quiz.', 'TRIVIA_QUIZ_INVALID');
            }

            $question ??= new TriviaQuestion(['trivia_quiz_id' => $quiz->id]);
            $question->fill(Arr::only($questionPayload, [
                'position',
                'question_text',
                'image_url',
                'explanation_text',
                'source_type',
                'source_ref',
                'difficulty',
                'sport_slug',
                'status',
            ]));
            $question->trivia_quiz_id = $quiz->id;
            $question->save();

            $this->syncOptions($question, $questionPayload['options'] ?? []);
        }
    }

    private function syncOptions(TriviaQuestion $question, array $options): void
    {
        $existing = $question->options()->get()->keyBy('id');
        $incomingIds = collect($options)->pluck('id')->filter()->map(fn ($id) => (int) $id);
        $deleteIds = $existing->keys()->diff($incomingIds);

        if ($deleteIds->isNotEmpty()) {
            TriviaQuestionOption::query()->whereIn('id', $deleteIds)->delete();
        }

        foreach ($options as $optionPayload) {
            $option = isset($optionPayload['id'])
                ? $existing->get((int) $optionPayload['id'])
                : null;

            if (isset($optionPayload['id']) && ! $option) {
                throw ApiException::unprocessable('Option does not belong to the selected question.', 'TRIVIA_QUIZ_INVALID');
            }

            $option ??= new TriviaQuestionOption(['trivia_question_id' => $question->id]);
            $option->fill(Arr::only($optionPayload, [
                'position',
                'option_text',
                'is_correct',
            ]));
            $option->trivia_question_id = $question->id;
            $option->save();
        }
    }
}
