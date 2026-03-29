<?php

namespace App\Services;

use App\Models\TriviaActivityLog;
use App\Models\TriviaAttempt;
use App\Models\TriviaQuiz;
use Illuminate\Support\Collection;

class TriviaOperationsService
{
    public function __construct(private readonly TriviaLeaderboardService $leaderboardService)
    {
    }

    public function autoPublishDueQuizzes(): int
    {
        $quizzes = TriviaQuiz::query()
            ->whereIn('status', ['draft', 'scheduled'])
            ->whereNotNull('opens_at')
            ->where('opens_at', '<=', now())
            ->get();

        foreach ($quizzes as $quiz) {
            $quiz->forceFill([
                'status' => 'published',
                'published_at' => now(),
            ])->save();

            $this->logSystemEvent('quiz_auto_published', TriviaQuiz::class, $quiz->id, [
                'quiz_date' => $quiz->quiz_date?->toDateString(),
            ]);
        }

        return $quizzes->count();
    }

    public function autoCloseExpiredQuizzes(): int
    {
        $quizzes = TriviaQuiz::query()
            ->where('status', 'published')
            ->whereNotNull('closes_at')
            ->where('closes_at', '<=', now())
            ->get();

        foreach ($quizzes as $quiz) {
            $quiz->forceFill(['status' => 'closed'])->save();
            $this->logSystemEvent('quiz_auto_closed', TriviaQuiz::class, $quiz->id, [
                'quiz_date' => $quiz->quiz_date?->toDateString(),
            ]);
        }

        return $quizzes->count();
    }

    public function expireStaleAttempts(): int
    {
        $attempts = TriviaAttempt::query()
            ->where('status', 'in_progress')
            ->where('expires_at', '<=', now())
            ->get();

        foreach ($attempts as $attempt) {
            $attempt->forceFill(['status' => 'expired'])->save();
            $this->logSystemEvent('attempt_expired', TriviaAttempt::class, $attempt->id, [
                'user_id' => $attempt->user_id,
                'trivia_quiz_id' => $attempt->trivia_quiz_id,
            ]);
        }

        return $attempts->count();
    }

    public function refreshLeaderboards(?string $boardType = null, ?string $periodKey = null): int
    {
        $targets = $this->leaderboardTargets($boardType, $periodKey);

        foreach ($targets as $target) {
            $this->leaderboardService->rebuildBoard($target['board_type'], $target['period_key']);
        }

        return $targets->count();
    }

    private function leaderboardTargets(?string $boardType, ?string $periodKey): Collection
    {
        if ($boardType !== null) {
            return collect([[
                'board_type' => $boardType,
                'period_key' => $periodKey ?? $this->leaderboardService->defaultPeriodKey($boardType),
            ]]);
        }

        $today = now();

        return collect([
            ['board_type' => 'daily', 'period_key' => $today->toDateString()],
            ['board_type' => 'weekly', 'period_key' => sprintf('%d-W%02d', $today->isoWeekYear, $today->isoWeek)],
            ['board_type' => 'monthly', 'period_key' => $today->format('Y-m')],
            ['board_type' => 'all_time', 'period_key' => 'all'],
        ]);
    }

    private function logSystemEvent(string $eventName, string $referenceType, int $referenceId, array $metadata = []): void
    {
        TriviaActivityLog::create([
            'actor_type' => 'system',
            'actor_id' => null,
            'event_name' => $eventName,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'metadata' => $metadata,
            'created_at' => now(),
        ]);
    }
}
