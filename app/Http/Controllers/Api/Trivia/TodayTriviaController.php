<?php

namespace App\Http\Controllers\Api\Trivia;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Models\TriviaAttempt;
use App\Models\TriviaQuiz;
use App\Services\AuthBoxClient;
use App\Services\TriviaQuizResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TodayTriviaController extends Controller
{
    public function __construct(
        private readonly TriviaQuizResolver $quizResolver,
        private readonly AuthBoxClient $authBoxClient,
    ) {
    }

    public function show(Request $request): JsonResponse
    {
        $quiz = $this->quizResolver->todayQuiz();
        $userId = (string) $request->user()->getAuthIdentifier();
        $attempt = $quiz
            ? TriviaAttempt::query()
                ->where('trivia_quiz_id', $quiz->id)
                ->where('user_id', $userId)
                ->first()
            : null;

        $available = $this->quizResolver->isAvailable($quiz);
        $state = $this->resolveState($request, $quiz, $attempt, $userId, $available);

        return response()->json([
            'date' => $this->quizResolver->todayDate()->toDateString(),
            'available' => $available,
            'state' => $state,
            'current_attempt' => $this->currentAttemptPayload($attempt, $state),
            'quiz' => $quiz ? [
                'id' => $quiz->id,
                'title' => $quiz->title,
                'description' => $quiz->short_description,
                'trivia_banner_url' => $quiz->trivia_banner_url,
                'opens_at' => $quiz->opens_at?->toIso8601String(),
                'closes_at' => $quiz->closes_at?->toIso8601String(),
                'question_count' => $quiz->question_count_expected,
                'time_per_question_seconds' => $quiz->time_per_question_seconds,
                'points_per_correct' => $quiz->points_per_correct,
                'already_played' => $attempt?->status === 'submitted',
                'requires_verified_account' => true,
            ] : null,
        ]);
    }

    private function resolveState(
        Request $request,
        ?TriviaQuiz $quiz,
        ?TriviaAttempt $attempt,
        string $userId,
        bool $available,
    ): string {
        if (! $quiz) {
            return 'no_quiz';
        }

        if ($attempt?->status === 'submitted') {
            return 'already_played';
        }

        if ($attempt?->status === 'in_progress' && ! $attempt->isExpired()) {
            return 'in_progress';
        }

        if (! $available) {
            return $this->quizClosed($quiz) ? 'closed' : 'not_open';
        }

        return $this->verificationRequired($request, $userId)
            ? 'verification_required'
            : 'available';
    }

    private function currentAttemptPayload(?TriviaAttempt $attempt, string $state): ?array
    {
        if ($state !== 'in_progress' || ! $attempt) {
            return null;
        }

        return [
            'attempt_id' => $attempt->id,
            'started_at' => $attempt->started_at?->toIso8601String(),
            'expires_at' => $attempt->expires_at?->toIso8601String(),
        ];
    }

    private function verificationRequired(Request $request, string $userId): bool
    {
        $token = $request->attributes->get('auth_token');

        if (! is_string($token) || $token === '') {
            return false;
        }

        try {
            return ! $this->authBoxClient->currentUserProfile($token, $userId)->verified;
        } catch (ApiException) {
            // Keep /today resilient even if AuthBox lookup fails here.
            return false;
        }
    }

    private function quizClosed(TriviaQuiz $quiz): bool
    {
        return $quiz->status === 'closed'
            || ($quiz->closes_at !== null && $quiz->closes_at->isPast());
    }
}
