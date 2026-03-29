<?php

namespace App\Services;

use App\Models\LeaderboardEntry;
use App\Models\TriviaAttempt;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TriviaLeaderboardService
{
    public function refreshForAttempt(TriviaAttempt $attempt): array
    {
        $quizDate = $attempt->quiz->quiz_date instanceof Carbon
            ? $attempt->quiz->quiz_date
            : Carbon::parse($attempt->quiz->quiz_date);

        $periods = [
            'daily' => $quizDate->toDateString(),
            'weekly' => sprintf('%d-W%02d', $quizDate->isoWeekYear, $quizDate->isoWeek),
            'monthly' => $quizDate->format('Y-m'),
            'all_time' => 'all',
        ];

        $ranks = [];

        foreach ($periods as $boardType => $periodKey) {
            $this->rebuildBoard($boardType, $periodKey);

            $ranks[$boardType.'_rank'] = LeaderboardEntry::query()
                ->where('board_type', $boardType)
                ->where('period_key', $periodKey)
                ->where('user_id', $attempt->user_id)
                ->value('rank_position');
        }

        return $ranks;
    }

    public function defaultPeriodKey(string $boardType): string
    {
        $today = now();

        return match ($boardType) {
            'daily' => $today->toDateString(),
            'weekly' => sprintf('%d-W%02d', $today->isoWeekYear, $today->isoWeek),
            'monthly' => $today->format('Y-m'),
            'all_time' => 'all',
            default => $today->toDateString(),
        };
    }

    public function rebuildBoard(string $boardType, string $periodKey): void
    {
        $rows = $this->aggregatedRows($boardType, $periodKey);
        $timestamp = now();

        DB::transaction(function () use ($boardType, $periodKey, $rows, $timestamp): void {
            LeaderboardEntry::query()
                ->where('board_type', $boardType)
                ->where('period_key', $periodKey)
                ->delete();

            if ($rows->isEmpty()) {
                return;
            }

            LeaderboardEntry::insert(
                $rows->values()->map(function (array $row, int $index) use ($boardType, $periodKey, $timestamp): array {
                    return [
                        'board_type' => $boardType,
                        'period_key' => $periodKey,
                        'user_id' => $row['user_id'],
                        'display_name_snapshot' => $row['display_name_snapshot'],
                        'avatar_url_snapshot' => $row['avatar_url_snapshot'],
                        'points' => $row['points'],
                        'quizzes_played' => $row['quizzes_played'],
                        'correct_answers' => $row['correct_answers'],
                        'accuracy' => $row['accuracy'],
                        'avg_score' => $row['avg_score'],
                        'rank_position' => $index + 1,
                        'created_at' => $timestamp,
                        'updated_at' => $timestamp,
                    ];
                })->all()
            );
        });
    }

    private function aggregatedRows(string $boardType, string $periodKey): Collection
    {
        $query = TriviaAttempt::query()
            ->join('trivia_quizzes', 'trivia_quizzes.id', '=', 'trivia_attempts.trivia_quiz_id')
            ->where('trivia_attempts.status', 'submitted');

        if ($boardType === 'daily') {
            $query->whereDate('trivia_quizzes.quiz_date', $periodKey);
        } elseif ($boardType === 'weekly') {
            [$startDate, $endDate] = $this->weeklyDateRange($periodKey);
            $query->whereBetween('trivia_quizzes.quiz_date', [$startDate, $endDate]);
        } elseif ($boardType === 'monthly') {
            [$startDate, $endDate] = $this->monthlyDateRange($periodKey);
            $query->whereBetween('trivia_quizzes.quiz_date', [$startDate, $endDate]);
        }

        $rows = $query
            ->selectRaw('
                trivia_attempts.user_id,
                MAX(trivia_attempts.display_name_snapshot) as display_name_snapshot,
                MAX(trivia_attempts.avatar_url_snapshot) as avatar_url_snapshot,
                SUM(trivia_attempts.score_total) as points,
                COUNT(*) as quizzes_played,
                SUM(trivia_attempts.correct_answers_count) as correct_answers,
                SUM(trivia_attempts.wrong_answers_count) as wrong_answers,
                AVG(trivia_attempts.score_total) as avg_score,
                SUM(COALESCE(trivia_attempts.time_taken_seconds, 0)) as time_taken_seconds,
                MAX(trivia_attempts.submitted_at) as submitted_at
            ')
            ->groupBy('trivia_attempts.user_id')
            ->get()
            ->map(function (object $row): array {
                $denominator = (int) $row->correct_answers + (int) $row->wrong_answers;
                $accuracy = $denominator > 0
                    ? round(((int) $row->correct_answers / $denominator) * 100, 2)
                    : 0.0;

                return [
                    'user_id' => $row->user_id,
                    'display_name_snapshot' => $row->display_name_snapshot,
                    'avatar_url_snapshot' => $row->avatar_url_snapshot,
                    'points' => (int) $row->points,
                    'quizzes_played' => (int) $row->quizzes_played,
                    'correct_answers' => (int) $row->correct_answers,
                    'accuracy' => $accuracy,
                    'avg_score' => $row->avg_score !== null ? round((float) $row->avg_score, 2) : null,
                    'time_taken_seconds' => (int) $row->time_taken_seconds,
                    'submitted_at' => $row->submitted_at,
                ];
            });

        return $rows->sort(function (array $left, array $right) use ($boardType): int {
            $comparisons = [
                $right['points'] <=> $left['points'],
                $right['correct_answers'] <=> $left['correct_answers'],
            ];

            if ($boardType === 'daily') {
                $comparisons[] = $left['time_taken_seconds'] <=> $right['time_taken_seconds'];
                $comparisons[] = strcmp((string) $left['submitted_at'], (string) $right['submitted_at']);
            } else {
                $comparisons[] = $right['accuracy'] <=> $left['accuracy'];
            }

            $comparisons[] = strcmp($left['user_id'], $right['user_id']);

            foreach ($comparisons as $comparison) {
                if ($comparison !== 0) {
                    return $comparison;
                }
            }

            return 0;
        })->values();
    }

    private function weeklyDateRange(string $periodKey): array
    {
        [$year, $week] = explode('-W', $periodKey);
        $start = Carbon::now()->setISODate((int) $year, (int) $week)->startOfWeek();
        $end = $start->copy()->endOfWeek();

        return [$start->toDateString(), $end->toDateString()];
    }

    private function monthlyDateRange(string $periodKey): array
    {
        $start = Carbon::createFromFormat('Y-m', $periodKey)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        return [$start->toDateString(), $end->toDateString()];
    }
}
