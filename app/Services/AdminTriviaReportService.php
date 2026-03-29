<?php

namespace App\Services;

use App\Models\LeaderboardEntry;
use App\Models\TriviaActivityLog;
use App\Models\TriviaAttempt;
use App\Models\TriviaQuiz;
use App\Models\UserTriviaProfile;

class AdminTriviaReportService
{
    public function __construct(private readonly TriviaLeaderboardService $leaderboardService)
    {
    }

    public function overview(): array
    {
        $today = now()->toDateString();
        $todayQuiz = TriviaQuiz::query()
            ->withCount('questions')
            ->whereDate('quiz_date', $today)
            ->first();

        $todayAttemptsQuery = TriviaAttempt::query()
            ->whereHas('quiz', fn ($query) => $query->whereDate('quiz_date', $today));

        $todayAttempts = (clone $todayAttemptsQuery)->count();
        $submittedTodayAttempts = (clone $todayAttemptsQuery)->where('status', 'submitted');
        $averageScoreToday = $submittedTodayAttempts->avg('score_total');

        $streakLeader = UserTriviaProfile::query()
            ->orderByDesc('current_streak')
            ->orderByDesc('total_points')
            ->orderBy('user_id')
            ->first();

        $topEntries = LeaderboardEntry::query()
            ->where('board_type', 'daily')
            ->where('period_key', $today)
            ->orderBy('rank_position')
            ->limit(5)
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

        return [
            'today_quiz' => $todayQuiz ? [
                'id' => $todayQuiz->id,
                'quiz_date' => $todayQuiz->quiz_date?->toDateString(),
                'title' => $todayQuiz->title,
                'status' => $todayQuiz->status,
                'questions_count' => $todayQuiz->questions_count,
                'opens_at' => $todayQuiz->opens_at?->toIso8601String(),
                'closes_at' => $todayQuiz->closes_at?->toIso8601String(),
            ] : null,
            'attempts_today' => $todayAttempts,
            'average_score_today' => $averageScoreToday !== null ? round((float) $averageScoreToday, 2) : null,
            'current_active_streak_leader' => $streakLeader ? [
                'user_id' => $streakLeader->user_id,
                'display_name' => $streakLeader->display_name_snapshot,
                'avatar_url' => $streakLeader->avatar_url_snapshot,
                'current_streak' => $streakLeader->current_streak,
                'total_points' => $streakLeader->total_points,
            ] : null,
            'top_five_today' => $topEntries,
            'pending_draft_quizzes' => TriviaQuiz::query()
                ->whereIn('status', ['draft', 'scheduled'])
                ->count(),
        ];
    }

    public function attempts(array $filters): array
    {
        $query = TriviaAttempt::query()->with('quiz');

        if (! empty($filters['quiz_date'])) {
            $query->whereHas('quiz', fn ($quizQuery) => $quizQuery->whereDate('quiz_date', $filters['quiz_date']));
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['client'])) {
            $query->where('client_type', $filters['client']);
        }

        if (isset($filters['min_score']) && $filters['min_score'] !== null) {
            $query->where('score_total', '>=', (int) $filters['min_score']);
        }

        if (isset($filters['max_score']) && $filters['max_score'] !== null) {
            $query->where('score_total', '<=', (int) $filters['max_score']);
        }

        $limit = max(1, min((int) ($filters['limit'] ?? 100), 200));

        return [
            'items' => $query->latest('started_at')
                ->limit($limit)
                ->get()
                ->map(fn (TriviaAttempt $attempt): array => [
                    'id' => $attempt->id,
                    'quiz_date' => $attempt->quiz?->quiz_date?->toDateString(),
                    'user_id' => $attempt->user_id,
                    'display_name' => $attempt->display_name_snapshot,
                    'avatar_url' => $attempt->avatar_url_snapshot,
                    'status' => $attempt->status,
                    'score_total' => $attempt->score_total,
                    'correct_answers_count' => $attempt->correct_answers_count,
                    'streak_after' => $attempt->streak_after,
                    'client_type' => $attempt->client_type,
                    'started_at' => $attempt->started_at?->toIso8601String(),
                    'submitted_at' => $attempt->submitted_at?->toIso8601String(),
                    'total_time_seconds' => $attempt->time_taken_seconds,
                ])
                ->all(),
        ];
    }

    public function leaderboard(string $boardType, ?string $periodKey, int $limit = 50): array
    {
        $limit = max(1, min($limit, 200));
        $periodKey ??= $this->leaderboardService->defaultPeriodKey($boardType);

        return [
            'board_type' => $boardType,
            'period_key' => $periodKey,
            'entries' => LeaderboardEntry::query()
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
                    'quizzes_played' => $entry->quizzes_played,
                    'accuracy' => (float) $entry->accuracy,
                    'avg_score' => $entry->avg_score !== null ? (float) $entry->avg_score : null,
                ])
                ->all(),
        ];
    }

    public function activity(int $limit = 50): array
    {
        $limit = max(1, min($limit, 200));

        return [
            'items' => TriviaActivityLog::query()
                ->latest('created_at')
                ->limit($limit)
                ->get()
                ->map(fn (TriviaActivityLog $log): array => [
                    'id' => $log->id,
                    'actor_type' => $log->actor_type,
                    'actor_id' => $log->actor_id,
                    'event_name' => $log->event_name,
                    'reference_type' => $log->reference_type,
                    'reference_id' => $log->reference_id,
                    'metadata' => $log->metadata,
                    'created_at' => $log->created_at?->toIso8601String(),
                ])
                ->all(),
        ];
    }
}
