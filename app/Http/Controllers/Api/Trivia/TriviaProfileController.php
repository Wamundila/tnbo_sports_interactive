<?php

namespace App\Http\Controllers\Api\Trivia;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Models\LeaderboardEntry;
use App\Models\TriviaAttempt;
use App\Models\TriviaQuiz;
use App\Models\UserTriviaProfile;
use App\Services\AuthBoxClient;
use App\Services\TriviaLeaderboardService;
use App\Services\TriviaQuizResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TriviaProfileController extends Controller
{
    public function __construct(
        private readonly TriviaLeaderboardService $leaderboardService,
        private readonly TriviaQuizResolver $quizResolver,
        private readonly AuthBoxClient $authBoxClient,
    ) {
    }

    public function summary(Request $request): JsonResponse
    {
        $userId = (string) $request->user()->getAuthIdentifier();
        $profile = UserTriviaProfile::query()->where('user_id', $userId)->first();
        $todayAttempt = TriviaAttempt::query()
            ->where('user_id', $userId)
            ->whereHas('quiz', fn ($query) => $query->whereDate('quiz_date', $this->quizResolver->todayDate()->toDateString()))
            ->where('status', 'submitted')
            ->first();

        return response()->json($this->summaryPayload($userId, $profile, $todayAttempt));
    }

    public function surfaceSummary(Request $request): JsonResponse
    {
        $userId = (string) $request->user()->getAuthIdentifier();
        $profile = UserTriviaProfile::query()->where('user_id', $userId)->first();
        $todayContext = $this->todayContext($request, $userId);
        $todaySubmittedAttempt = $todayContext['attempt']?->status === 'submitted'
            ? $todayContext['attempt']
            : null;
        $previewLimit = (int) config('trivia.leaderboard_preview_limit', 5);

        return response()->json([
            'date' => $this->quizResolver->todayDate()->toDateString(),
            'daily_trivia' => $this->dailyTriviaPayload($todayContext, $profile, $todaySubmittedAttempt),
            'user_summary' => $this->summaryPayload($userId, $profile, $todaySubmittedAttempt),
            'leaderboard_previews' => $this->leaderboardPreviewPayload($userId, $previewLimit),
        ]);
    }

    public function history(Request $request): JsonResponse
    {
        $userId = (string) $request->user()->getAuthIdentifier();

        $items = TriviaAttempt::query()
            ->with('quiz')
            ->where('user_id', $userId)
            ->where('status', 'submitted')
            ->latest('submitted_at')
            ->limit(20)
            ->get()
            ->map(fn (TriviaAttempt $attempt): array => [
                'quiz_date' => $attempt->quiz->quiz_date->toDateString(),
                'quiz_title' => $attempt->quiz->title,
                'completed_at' => $attempt->submitted_at?->toIso8601String(),
                'score_total' => $attempt->score_total,
                'correct_answers_count' => $attempt->correct_answers_count,
                'question_count' => $attempt->quiz->question_count_expected,
                'streak_after' => $attempt->streak_after,
            ])
            ->all();

        return response()->json(['items' => $items]);
    }

    public function leaderboard(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'board_type' => ['nullable', Rule::in(['daily', 'weekly', 'monthly', 'all_time'])],
            'period_key' => ['nullable', 'string', 'max:50'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:200'],
        ]);

        $userId = (string) $request->user()->getAuthIdentifier();
        $boardType = (string) ($validated['board_type'] ?? 'daily');
        $periodKey = (string) ($validated['period_key'] ?? $this->leaderboardService->defaultPeriodKey($boardType));
        $limit = (int) ($validated['limit'] ?? config('trivia.leaderboard_default_limit', 50));

        return response()->json($this->singleLeaderboardPayload($boardType, $periodKey, $limit, $userId));
    }

    private function summaryPayload(string $userId, ?UserTriviaProfile $profile, ?TriviaAttempt $todayAttempt): array
    {
        return [
            'user_id' => $userId,
            'current_streak' => $profile?->current_streak ?? 0,
            'best_streak' => $profile?->best_streak ?? 0,
            'total_points' => $profile?->total_points ?? 0,
            'total_quizzes_played' => $profile?->total_quizzes_played ?? 0,
            'total_quizzes_completed' => $profile?->total_quizzes_completed ?? 0,
            'lifetime_accuracy' => (float) ($profile?->lifetime_accuracy ?? 0),
            'rank' => $this->rankPayload($userId, $todayAttempt),
            'today_status' => [
                'played' => $todayAttempt !== null,
                'score_total' => $todayAttempt?->score_total,
            ],
        ];
    }

    private function dailyTriviaPayload(array $todayContext, ?UserTriviaProfile $profile, ?TriviaAttempt $todaySubmittedAttempt): array
    {
        /** @var TriviaQuiz|null $quiz */
        $quiz = $todayContext['quiz'];
        /** @var TriviaAttempt|null $attempt */
        $attempt = $todayContext['attempt'];
        $userId = (string) $todayContext['user_id'];

        return [
            'title' => $quiz?->title ?? "Today's TNBO Sports Trivia",
            'short_description' => $quiz?->short_description,
            'trivia_banner_url' => $quiz?->trivia_banner_url,
            'state' => $todayContext['state'],
            'available' => $todayContext['available'],
            'requires_verified_account' => true,
            'opens_at' => $quiz?->opens_at?->toIso8601String(),
            'closes_at' => $quiz?->closes_at?->toIso8601String(),
            'current_attempt' => $this->currentAttemptPayload($attempt, $todayContext['state']),
            'today_score_total' => $todaySubmittedAttempt?->score_total,
            'rank' => $this->rankPayload($userId, $todaySubmittedAttempt),
            'points' => [
                'today' => $todaySubmittedAttempt?->score_total,
                'total' => $profile?->total_points ?? 0,
            ],
            'streak' => [
                'current' => $profile?->current_streak ?? 0,
                'best' => $profile?->best_streak ?? 0,
            ],
        ];
    }

    private function leaderboardPreviewPayload(string $userId, int $limit): array
    {
        $payload = [];

        foreach (['daily', 'weekly', 'monthly', 'all_time'] as $boardType) {
            $periodKey = $this->leaderboardService->defaultPeriodKey($boardType);
            $payload[$boardType] = $this->singleLeaderboardPayload($boardType, $periodKey, $limit, $userId, false);
        }

        return $payload;
    }

    private function singleLeaderboardPayload(
        string $boardType,
        string $periodKey,
        int $limit,
        string $userId,
        bool $includeCurrentUser = true,
    ): array {
        $entries = LeaderboardEntry::query()
            ->where('board_type', $boardType)
            ->where('period_key', $periodKey)
            ->orderBy('rank_position')
            ->limit($limit)
            ->get()
            ->map(fn (LeaderboardEntry $entry): array => [
                'rank' => $entry->rank_position,
                'user' => [
                    'user_id' => $entry->user_id,
                    'display_name' => $entry->display_name_snapshot,
                    'avatar_url' => $entry->avatar_url_snapshot,
                ],
                'points' => $entry->points,
                'accuracy' => (float) $entry->accuracy,
                'quizzes_played' => $entry->quizzes_played,
            ])
            ->all();

        $payload = [
            'board_type' => $boardType,
            'period_key' => $periodKey,
            'entries' => $entries,
        ];

        if (! $includeCurrentUser) {
            return $payload;
        }

        $currentUser = LeaderboardEntry::query()
            ->where('board_type', $boardType)
            ->where('period_key', $periodKey)
            ->where('user_id', $userId)
            ->first();

        $payload['limit'] = $limit;
        $payload['current_user'] = $currentUser ? [
            'rank' => $currentUser->rank_position,
            'points' => $currentUser->points,
        ] : null;

        return $payload;
    }

    private function rankPayload(string $userId, ?TriviaAttempt $todaySubmittedAttempt = null): array
    {
        $payload = [];

        foreach (['daily', 'weekly', 'monthly', 'all_time'] as $boardType) {
            $payload[$boardType] = LeaderboardEntry::query()
                ->where('board_type', $boardType)
                ->where('period_key', $this->leaderboardService->defaultPeriodKey($boardType))
                ->where('user_id', $userId)
                ->value('rank_position')
                ?? data_get($todaySubmittedAttempt?->ranking_snapshot, $boardType.'_rank');
        }

        return $payload;
    }

    private function todayContext(Request $request, string $userId): array
    {
        $quiz = $this->quizResolver->todayQuiz();
        $attempt = $quiz
            ? TriviaAttempt::query()
                ->where('trivia_quiz_id', $quiz->id)
                ->where('user_id', $userId)
                ->first()
            : null;

        $available = $this->quizResolver->isAvailable($quiz);
        $state = $this->resolveTodayState($request, $quiz, $attempt, $userId, $available);

        return [
            'user_id' => $userId,
            'quiz' => $quiz,
            'attempt' => $attempt,
            'available' => $available,
            'state' => $state,
        ];
    }

    private function resolveTodayState(
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
            return false;
        }
    }

    private function quizClosed(TriviaQuiz $quiz): bool
    {
        return $quiz->status === 'closed'
            || ($quiz->closes_at !== null && $quiz->closes_at->isPast());
    }
}
