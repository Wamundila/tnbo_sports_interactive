<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\PredictorPrediction;
use App\Models\PredictorRound;
use App\Models\PredictorRoundEntry;
use App\Models\PredictorRoundFixture;
use Illuminate\Support\Facades\DB;

class PredictorScoringService
{
    public function __construct(private readonly PredictorLeaderboardService $leaderboards)
    {
    }

    public function scoreRound(PredictorRound $round, bool $force = false): array
    {
        $round->loadMissing(['season.campaign', 'fixtures', 'entries.predictions.fixture']);
        $this->assertRoundCanBeScored($round, $force);

        return DB::transaction(function () use ($round): array {
            $round->forceFill(['status' => 'scoring'])->save();

            $scoredAt = now();
            $config = array_merge(config('predictor.default_scoring', []), $round->season->scoring_config ?? []);
            $entries = PredictorRoundEntry::query()
                ->with(['predictions.fixture'])
                ->where('round_id', $round->id)
                ->whereIn('entry_status', ['submitted', 'locked', 'scored'])
                ->get();

            foreach ($entries as $entry) {
                $this->scoreEntry($entry, $config, $scoredAt);
            }

            $round->forceFill([
                'status' => 'completed',
                'leaderboard_frozen_at' => $scoredAt,
            ])->save();

            $refresh = $this->leaderboards->refreshForRound($round->fresh(['season.campaign']));

            return [
                'entries_scored' => $entries->count(),
                'fixtures_scored' => $round->fixtures->count(),
                'monthly_key' => $refresh['monthly_key'],
                'leaderboards' => [
                    'round' => $refresh['round_entries'],
                    'monthly' => $refresh['monthly_entries'],
                    'season' => $refresh['season_entries'],
                    'all_time' => $refresh['all_time_entries'],
                ],
            ];
        });
    }

    private function assertRoundCanBeScored(PredictorRound $round, bool $force): void
    {
        if (! $force && $round->status === 'completed') {
            throw ApiException::conflict('Round is already completed. Use recalculate if results changed.', 'PREDICTOR_SUBMISSION_LOCKED');
        }

        if ($round->fixtures->isEmpty()) {
            throw ApiException::unprocessable('Round has no fixtures to score.', 'PREDICTOR_CONFIGURATION_ERROR');
        }

        if (! $force && $round->prediction_closes_at->isFuture() && ! in_array($round->status, ['locked', 'scoring'], true)) {
            throw ApiException::conflict('Predictions are still open for this round.', 'PREDICTOR_ROUND_NOT_OPEN');
        }

        $invalidFixture = $round->fixtures->first(function (PredictorRoundFixture $fixture): bool {
            if (in_array($fixture->result_status, ['pending', 'live'], true)) {
                return true;
            }

            if ($fixture->result_status === 'completed' && ($fixture->actual_home_score === null || $fixture->actual_away_score === null)) {
                return true;
            }

            return false;
        });

        if ($invalidFixture) {
            throw ApiException::unprocessable('All fixtures must be finalized before scoring the round.', 'PREDICTOR_CONFIGURATION_ERROR');
        }
    }

    private function scoreEntry(PredictorRoundEntry $entry, array $config, $scoredAt): void
    {
        $totals = [
            'points' => 0.0,
            'correct_outcomes' => 0,
            'exact_scores' => 0,
            'close_scores' => 0,
        ];

        $multiplier = (float) ($entry->banker_multiplier ?? ($config['banker_multiplier'] ?? 2));

        foreach ($entry->predictions as $prediction) {
            $scored = $this->scorePrediction($prediction, $multiplier, $config, $scoredAt);

            $totals['points'] += $scored['points_awarded'];
            $totals['correct_outcomes'] += $scored['correct_outcomes'];
            $totals['exact_scores'] += $scored['exact_scores'];
            $totals['close_scores'] += $scored['close_scores'];
        }

        $entry->forceFill([
            'entry_status' => 'scored',
            'total_points' => round($totals['points'], 2),
            'correct_outcomes_count' => $totals['correct_outcomes'],
            'exact_scores_count' => $totals['exact_scores'],
            'close_score_count' => $totals['close_scores'],
        ])->save();
    }

    private function scorePrediction(PredictorPrediction $prediction, float $multiplier, array $config, $scoredAt): array
    {
        $fixture = $prediction->fixture;

        if (in_array($fixture->result_status, ['postponed', 'cancelled'], true)) {
            $prediction->forceFill([
                'points_awarded' => 0,
                'outcome_points' => 0,
                'exact_score_points' => 0,
                'close_score_points' => 0,
                'banker_bonus_points' => 0,
                'scoring_status' => 'void',
                'scoring_notes' => $fixture->result_status,
                'scored_at' => $scoredAt,
            ])->save();

            return [
                'points_awarded' => 0.0,
                'correct_outcomes' => 0,
                'exact_scores' => 0,
                'close_scores' => 0,
            ];
        }

        $actualOutcome = $this->outcome((int) $fixture->actual_home_score, (int) $fixture->actual_away_score);
        $outcomePoints = $prediction->predicted_outcome === $actualOutcome ? (float) ($config['outcome_points'] ?? 3) : 0.0;
        $exactScorePoints = ((int) $prediction->predicted_home_score === (int) $fixture->actual_home_score && (int) $prediction->predicted_away_score === (int) $fixture->actual_away_score)
            ? (float) ($config['exact_score_points'] ?? 5)
            : 0.0;
        $closeScorePoints = 0.0;

        if ($outcomePoints > 0 && $exactScorePoints === 0.0) {
            $homeDifference = abs((int) $prediction->predicted_home_score - (int) $fixture->actual_home_score);
            $awayDifference = abs((int) $prediction->predicted_away_score - (int) $fixture->actual_away_score);

            if ($homeDifference <= 1 && $awayDifference <= 1) {
                $closeScorePoints = (float) ($config['close_score_points'] ?? 1.5);
            }
        }

        $basePoints = $outcomePoints + $exactScorePoints + $closeScorePoints;
        $bankerBonusPoints = $prediction->is_banker && $basePoints > 0 ? $basePoints * max($multiplier - 1, 0) : 0.0;
        $pointsAwarded = round($basePoints + $bankerBonusPoints, 2);

        $prediction->forceFill([
            'points_awarded' => $pointsAwarded,
            'outcome_points' => $outcomePoints,
            'exact_score_points' => $exactScorePoints,
            'close_score_points' => $closeScorePoints,
            'banker_bonus_points' => round($bankerBonusPoints, 2),
            'scoring_status' => 'scored',
            'scoring_notes' => $this->scoringNote($outcomePoints, $exactScorePoints, $closeScorePoints, $prediction->is_banker),
            'scored_at' => $scoredAt,
        ])->save();

        return [
            'points_awarded' => $pointsAwarded,
            'correct_outcomes' => $outcomePoints > 0 ? 1 : 0,
            'exact_scores' => $exactScorePoints > 0 ? 1 : 0,
            'close_scores' => $closeScorePoints > 0 ? 1 : 0,
        ];
    }

    private function outcome(int $homeScore, int $awayScore): string
    {
        return match (true) {
            $homeScore > $awayScore => 'home_win',
            $homeScore < $awayScore => 'away_win',
            default => 'draw',
        };
    }

    private function scoringNote(float $outcomePoints, float $exactScorePoints, float $closeScorePoints, bool $isBanker): string
    {
        $parts = [];

        if ($exactScorePoints > 0) {
            $parts[] = 'exact_score';
        } elseif ($closeScorePoints > 0) {
            $parts[] = 'close_score';
        } elseif ($outcomePoints > 0) {
            $parts[] = 'correct_outcome';
        } else {
            $parts[] = 'wrong_outcome';
        }

        if ($isBanker) {
            $parts[] = 'banker';
        }

        return implode('|', $parts);
    }
}
