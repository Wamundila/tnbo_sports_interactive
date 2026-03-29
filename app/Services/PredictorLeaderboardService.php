<?php

namespace App\Services;

use App\Models\PredictorCampaign;
use App\Models\PredictorLeaderboardEntry;
use App\Models\PredictorRound;
use App\Models\PredictorRoundEntry;
use App\Models\PredictorSeason;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class PredictorLeaderboardService
{
    public function defaultMonthlyKey(): string
    {
        return now()->format('Y-m');
    }

    public function monthlyKeyForRound(PredictorRound $round): string
    {
        return ($round->leaderboard_frozen_at ?? $round->round_closes_at ?? now())->format('Y-m');
    }

    public function previewPayload(PredictorCampaign $campaign, ?PredictorSeason $season, ?PredictorRound $round, int $limit): array
    {
        return [
            'round' => [
                'entries' => $this->entries('round', $campaign, $season, $round, $limit),
            ],
            'monthly' => [
                'entries' => $this->entries('monthly', $campaign, $season, $round, $limit),
            ],
            'season' => [
                'entries' => $this->entries('season', $campaign, $season, $round, $limit),
            ],
        ];
    }

    public function leaderboardPayload(string $boardType, PredictorCampaign $campaign, ?PredictorSeason $season, ?PredictorRound $round, string $userId, int $limit): array
    {
        $query = $this->baseQuery($boardType, $campaign, $season, $round);

        $entries = (clone $query)
            ->orderBy('rank')
            ->limit($limit)
            ->get()
            ->map(fn (PredictorLeaderboardEntry $entry): array => [
                'rank' => $entry->rank,
                'user' => [
                    'user_id' => $entry->user_id,
                    'display_name' => $entry->display_name_snapshot,
                    'avatar_url' => $entry->avatar_url_snapshot,
                ],
                'points_total' => (float) $entry->points_total,
                'rounds_played' => $entry->rounds_played,
                'correct_outcomes_count' => $entry->correct_outcomes_count,
                'exact_scores_count' => $entry->exact_scores_count,
                'accuracy_percentage' => $entry->accuracy_percentage !== null ? (float) $entry->accuracy_percentage : null,
            ])
            ->all();

        $currentUser = (clone $query)->where('user_id', $userId)->first();

        return [
            'leaderboard_type' => $boardType,
            'limit' => $limit,
            'entries' => $entries,
            'current_user' => $currentUser ? [
                'rank' => $currentUser->rank,
                'points_total' => (float) $currentUser->points_total,
            ] : null,
        ];
    }

    public function currentRank(PredictorCampaign $campaign, ?PredictorSeason $season, string $userId): ?int
    {
        if (! $season) {
            return null;
        }

        return PredictorLeaderboardEntry::query()
            ->where('leaderboard_type', 'season')
            ->where('campaign_id', $campaign->id)
            ->where('season_id', $season->id)
            ->where('user_id', $userId)
            ->value('rank');
    }

    public function refreshForRound(PredictorRound $round): array
    {
        $round->loadMissing('season.campaign');
        $monthKey = $this->monthlyKeyForRound($round);

        return [
            'monthly_key' => $monthKey,
            'round_entries' => $this->refreshRoundBoard($round),
            'monthly_entries' => $this->refreshMonthlyBoard($round->season->campaign, $monthKey),
            'season_entries' => $this->refreshSeasonBoard($round->season),
            'all_time_entries' => $this->refreshAllTimeBoard($round->season->campaign),
        ];
    }

    private function refreshRoundBoard(PredictorRound $round): int
    {
        $entries = PredictorRoundEntry::query()
            ->with('predictions')
            ->where('round_id', $round->id)
            ->where('entry_status', 'scored')
            ->get();

        return $this->replaceScopeRows([
            'leaderboard_type' => 'round',
            'campaign_id' => $round->season->campaign_id,
            'season_id' => $round->season_id,
            'round_id' => $round->id,
            'leaderboard_period_key' => null,
        ], $this->aggregatedRows($entries, [
            'leaderboard_type' => 'round',
            'campaign_id' => $round->season->campaign_id,
            'season_id' => $round->season_id,
            'round_id' => $round->id,
            'leaderboard_period_key' => null,
        ]));
    }

    private function refreshMonthlyBoard(PredictorCampaign $campaign, string $monthKey): int
    {
        $start = now()->createFromFormat('Y-m', $monthKey)->startOfMonth();
        $end = (clone $start)->endOfMonth();

        $entries = PredictorRoundEntry::query()
            ->with(['predictions', 'round'])
            ->where('campaign_id', $campaign->id)
            ->where('entry_status', 'scored')
            ->whereHas('round', fn (Builder $query) => $query
                ->where('status', 'completed')
                ->whereBetween('leaderboard_frozen_at', [$start, $end]))
            ->get();

        return $this->replaceScopeRows([
            'leaderboard_type' => 'monthly',
            'campaign_id' => $campaign->id,
            'season_id' => null,
            'round_id' => null,
            'leaderboard_period_key' => $monthKey,
        ], $this->aggregatedRows($entries, [
            'leaderboard_type' => 'monthly',
            'campaign_id' => $campaign->id,
            'season_id' => null,
            'round_id' => null,
            'leaderboard_period_key' => $monthKey,
        ]));
    }

    private function refreshSeasonBoard(PredictorSeason $season): int
    {
        $entries = PredictorRoundEntry::query()
            ->with('predictions')
            ->where('season_id', $season->id)
            ->where('entry_status', 'scored')
            ->get();

        return $this->replaceScopeRows([
            'leaderboard_type' => 'season',
            'campaign_id' => $season->campaign_id,
            'season_id' => $season->id,
            'round_id' => null,
            'leaderboard_period_key' => null,
        ], $this->aggregatedRows($entries, [
            'leaderboard_type' => 'season',
            'campaign_id' => $season->campaign_id,
            'season_id' => $season->id,
            'round_id' => null,
            'leaderboard_period_key' => null,
        ]));
    }

    private function refreshAllTimeBoard(PredictorCampaign $campaign): int
    {
        $entries = PredictorRoundEntry::query()
            ->with('predictions')
            ->where('campaign_id', $campaign->id)
            ->where('entry_status', 'scored')
            ->get();

        return $this->replaceScopeRows([
            'leaderboard_type' => 'all_time',
            'campaign_id' => $campaign->id,
            'season_id' => null,
            'round_id' => null,
            'leaderboard_period_key' => null,
        ], $this->aggregatedRows($entries, [
            'leaderboard_type' => 'all_time',
            'campaign_id' => $campaign->id,
            'season_id' => null,
            'round_id' => null,
            'leaderboard_period_key' => null,
        ]));
    }

    private function entries(string $boardType, PredictorCampaign $campaign, ?PredictorSeason $season, ?PredictorRound $round, int $limit): array
    {
        return $this->baseQuery($boardType, $campaign, $season, $round)
            ->orderBy('rank')
            ->limit($limit)
            ->get()
            ->map(fn (PredictorLeaderboardEntry $entry): array => [
                'rank' => $entry->rank,
                'user_id' => $entry->user_id,
                'display_name' => $entry->display_name_snapshot,
                'avatar_url' => $entry->avatar_url_snapshot,
                'points_total' => (float) $entry->points_total,
                'exact_scores_count' => $entry->exact_scores_count,
                'correct_outcomes_count' => $entry->correct_outcomes_count,
            ])
            ->all();
    }

    private function baseQuery(string $boardType, PredictorCampaign $campaign, ?PredictorSeason $season, ?PredictorRound $round): Builder
    {
        $query = PredictorLeaderboardEntry::query()
            ->where('leaderboard_type', $boardType)
            ->where('campaign_id', $campaign->id);

        return match ($boardType) {
            'round' => $round ? $query->where('round_id', $round->id) : $query->whereRaw('1 = 0'),
            'season' => $season ? $query->where('season_id', $season->id) : $query->whereRaw('1 = 0'),
            'monthly' => $query->where('leaderboard_period_key', $this->defaultMonthlyKey()),
            'all_time' => $query,
            default => $query->whereRaw('1 = 0'),
        };
    }

    private function aggregatedRows(Collection $entries, array $scope): array
    {
        $grouped = $entries
            ->groupBy('user_id')
            ->map(function (Collection $userEntries, string $userId): array {
                $latestEntry = $userEntries
                    ->sortByDesc(fn (PredictorRoundEntry $entry) => $entry->submitted_at?->getTimestamp() ?? 0)
                    ->first();

                $predictions = $userEntries->flatMap(fn (PredictorRoundEntry $entry) => $entry->predictions);
                $eligiblePredictions = $predictions->filter(fn ($prediction) => $prediction->scoring_status !== 'void')->count();

                return [
                    'user_id' => $userId,
                    'display_name_snapshot' => $latestEntry?->display_name_snapshot,
                    'avatar_url_snapshot' => $latestEntry?->avatar_url_snapshot,
                    'points_total' => round((float) $userEntries->sum('total_points'), 2),
                    'rounds_played' => $userEntries->count(),
                    'correct_outcomes_count' => (int) $userEntries->sum('correct_outcomes_count'),
                    'exact_scores_count' => (int) $userEntries->sum('exact_scores_count'),
                    'close_score_count' => (int) $userEntries->sum('close_score_count'),
                    'accuracy_percentage' => $eligiblePredictions > 0
                        ? round(($userEntries->sum('correct_outcomes_count') / $eligiblePredictions) * 100, 2)
                        : null,
                    '_submitted_at' => $userEntries->min(fn (PredictorRoundEntry $entry) => $entry->submitted_at?->getTimestamp() ?? PHP_INT_MAX),
                ];
            })
            ->sort(fn (array $left, array $right) => $this->compareRows($left, $right))
            ->values();

        $now = now();

        return $grouped->map(function (array $row, int $index) use ($scope, $now): array {
            return [
                'leaderboard_type' => $scope['leaderboard_type'],
                'campaign_id' => $scope['campaign_id'],
                'season_id' => $scope['season_id'],
                'round_id' => $scope['round_id'],
                'leaderboard_period_key' => $scope['leaderboard_period_key'],
                'user_id' => $row['user_id'],
                'display_name_snapshot' => $row['display_name_snapshot'],
                'avatar_url_snapshot' => $row['avatar_url_snapshot'],
                'rank' => $index + 1,
                'points_total' => $row['points_total'],
                'rounds_played' => $row['rounds_played'],
                'correct_outcomes_count' => $row['correct_outcomes_count'],
                'exact_scores_count' => $row['exact_scores_count'],
                'close_score_count' => $row['close_score_count'],
                'accuracy_percentage' => $row['accuracy_percentage'],
                'metadata' => null,
                'refreshed_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        })->all();
    }

    private function replaceScopeRows(array $scope, array $rows): int
    {
        $query = PredictorLeaderboardEntry::query()
            ->where('leaderboard_type', $scope['leaderboard_type'])
            ->where('campaign_id', $scope['campaign_id']);

        foreach (['season_id', 'round_id', 'leaderboard_period_key'] as $field) {
            if ($scope[$field] === null) {
                $query->whereNull($field);
            } else {
                $query->where($field, $scope[$field]);
            }
        }

        $query->delete();

        if ($rows === []) {
            return 0;
        }

        PredictorLeaderboardEntry::query()->insert($rows);

        return count($rows);
    }

    private function compareRows(array $left, array $right): int
    {
        $fields = [
            ['points_total', true],
            ['exact_scores_count', true],
            ['correct_outcomes_count', true],
            ['close_score_count', true],
            ['_submitted_at', false],
            ['user_id', false],
        ];

        foreach ($fields as [$field, $descending]) {
            $comparison = $left[$field] <=> $right[$field];

            if ($comparison !== 0) {
                return $descending ? -$comparison : $comparison;
            }
        }

        return 0;
    }
}
