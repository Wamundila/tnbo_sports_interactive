<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DuplicateTriviaQuizRequest;
use App\Http\Requests\Admin\UpsertTriviaQuizRequest;
use App\Models\Admin;
use App\Models\TriviaQuiz;
use App\Services\AdminTriviaQuizService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TriviaQuizAdminController extends Controller
{
    public function __construct(private readonly AdminTriviaQuizService $quizService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $query = TriviaQuiz::query()
            ->withCount(['questions', 'attempts'])
            ->withAvg('attempts', 'score_total')
            ->orderByDesc('quiz_date');

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $items = $query->get()->map(fn (TriviaQuiz $quiz): array => $this->quizSummaryPayload($quiz))->all();

        return response()->json(['items' => $items]);
    }

    public function show(TriviaQuiz $quiz): JsonResponse
    {
        return response()->json([
            'quiz' => $this->quizDetailPayload($quiz->load(['questions.options'])),
        ]);
    }

    public function store(UpsertTriviaQuizRequest $request): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user();
        $quiz = $this->quizService->create($request->validated(), $admin);

        return response()->json([
            'quiz' => $this->quizDetailPayload($quiz),
        ], 201);
    }

    public function update(UpsertTriviaQuizRequest $request, TriviaQuiz $quiz): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user();
        $quiz = $this->quizService->update($quiz, $request->validated(), $admin);

        return response()->json([
            'quiz' => $this->quizDetailPayload($quiz),
        ]);
    }

    public function publish(Request $request, TriviaQuiz $quiz): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user();
        $quiz = $this->quizService->publish($quiz, $admin);

        return response()->json([
            'quiz' => $this->quizDetailPayload($quiz),
        ]);
    }

    public function close(Request $request, TriviaQuiz $quiz): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user();
        $quiz = $this->quizService->close($quiz, $admin);

        return response()->json([
            'quiz' => $this->quizDetailPayload($quiz),
        ]);
    }

    public function duplicate(DuplicateTriviaQuizRequest $request, TriviaQuiz $quiz): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user();
        $duplicate = $this->quizService->duplicate($quiz, $request->validated(), $admin);

        return response()->json([
            'quiz' => $this->quizDetailPayload($duplicate),
        ], 201);
    }

    private function quizSummaryPayload(TriviaQuiz $quiz): array
    {
        return [
            'id' => $quiz->id,
            'quiz_date' => $quiz->quiz_date?->toDateString(),
            'title' => $quiz->title,
            'status' => $quiz->status,
            'questions_count' => $quiz->questions_count ?? 0,
            'opens_at' => $quiz->opens_at?->toIso8601String(),
            'closes_at' => $quiz->closes_at?->toIso8601String(),
            'attempts_count' => $quiz->attempts_count ?? 0,
            'avg_score' => $quiz->attempts_avg_score_total !== null ? round((float) $quiz->attempts_avg_score_total, 2) : null,
        ];
    }

    private function quizDetailPayload(TriviaQuiz $quiz): array
    {
        return [
            'id' => $quiz->id,
            'quiz_date' => $quiz->quiz_date?->toDateString(),
            'title' => $quiz->title,
            'short_description' => $quiz->short_description,
            'status' => $quiz->status,
            'opens_at' => $quiz->opens_at?->toIso8601String(),
            'closes_at' => $quiz->closes_at?->toIso8601String(),
            'question_count_expected' => $quiz->question_count_expected,
            'time_per_question_seconds' => $quiz->time_per_question_seconds,
            'points_per_correct' => $quiz->points_per_correct,
            'streak_bonus_enabled' => $quiz->streak_bonus_enabled,
            'sport_slug' => $quiz->sport_slug,
            'published_at' => $quiz->published_at?->toIso8601String(),
            'questions' => $quiz->questions->map(fn ($question): array => [
                'id' => $question->id,
                'position' => $question->position,
                'question_text' => $question->question_text,
                'image_url' => $question->image_url,
                'explanation_text' => $question->explanation_text,
                'source_type' => $question->source_type,
                'source_ref' => $question->source_ref,
                'difficulty' => $question->difficulty,
                'sport_slug' => $question->sport_slug,
                'status' => $question->status,
                'options' => $question->options->map(fn ($option): array => [
                    'id' => $option->id,
                    'position' => $option->position,
                    'option_text' => $option->option_text,
                    'is_correct' => $option->is_correct,
                ])->values()->all(),
            ])->values()->all(),
        ];
    }
}
