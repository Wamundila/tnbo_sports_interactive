<?php

namespace App\Services;

use App\Data\AuthBoxUserProfile;
use App\Exceptions\ApiException;
use App\Models\PredictorCampaign;
use App\Models\PredictorPrediction;
use App\Models\PredictorRound;
use App\Models\PredictorRoundEntry;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PredictorEntryService
{
    public function __construct(private readonly PredictorCampaignResolver $resolver)
    {
    }

    public function saveDraft(PredictorRound $round, PredictorCampaign $campaign, string $userId, ?AuthBoxUserProfile $profile, array $predictions): array
    {
        $round = $this->resolver->ensureRoundAcceptingPredictions($round->loadMissing('fixtures', 'season'));
        $validated = $this->validatedPredictions($round, $campaign, $predictions, false);

        return DB::transaction(function () use ($round, $campaign, $userId, $profile, $validated): array {
            $entry = $this->upsertEntry($round, $campaign, $userId, $profile, 'draft');
            $this->syncPredictions($entry, $validated, false);

            $entry->refresh()->load('predictions');

            return [
                'entry_id' => $entry->id,
                'entry_status' => $entry->entry_status,
                'saved_at' => $entry->last_edited_at?->toIso8601String(),
                'predictions_count' => $entry->predictions->count(),
                'completed_predictions_count' => $entry->predictions->count(),
                'banker_fixture_id' => $entry->banker_fixture_id,
            ];
        });
    }

    public function submit(PredictorRound $round, PredictorCampaign $campaign, string $userId, ?AuthBoxUserProfile $profile, array $predictions): array
    {
        $round = $this->resolver->ensureRoundAcceptingPredictions($round->loadMissing('fixtures', 'season'));
        $validated = $this->validatedPredictions($round, $campaign, $predictions, true);

        return DB::transaction(function () use ($round, $campaign, $userId, $profile, $validated): array {
            $entry = $this->upsertEntry($round, $campaign, $userId, $profile, 'submitted');
            $entry->forceFill([
                'entry_status' => 'submitted',
                'submitted_at' => now(),
                'last_edited_at' => now(),
            ])->save();

            $this->syncPredictions($entry, $validated, true);

            return [
                'entry_id' => $entry->id,
                'entry_status' => $entry->entry_status,
                'submitted_at' => $entry->submitted_at?->toIso8601String(),
                'locks_at' => $round->prediction_closes_at->toIso8601String(),
            ];
        });
    }

    public function myEntry(?PredictorRound $round, string $userId): ?array
    {
        if (! $round) {
            return null;
        }

        $entry = PredictorRoundEntry::query()
            ->with(['predictions.fixture'])
            ->where('round_id', $round->id)
            ->where('user_id', $userId)
            ->first();

        if (! $entry) {
            return null;
        }

        return [
            'entry_id' => $entry->id,
            'entry_status' => $entry->entry_status,
            'total_points' => (float) $entry->total_points,
            'banker_fixture_id' => $entry->banker_fixture_id,
            'submitted_at' => $entry->submitted_at?->toIso8601String(),
            'last_edited_at' => $entry->last_edited_at?->toIso8601String(),
            'predictions' => $entry->predictions->map(fn (PredictorPrediction $prediction): array => [
                'prediction_id' => $prediction->id,
                'round_fixture_id' => $prediction->round_fixture_id,
                'predicted_home_score' => $prediction->predicted_home_score,
                'predicted_away_score' => $prediction->predicted_away_score,
                'predicted_outcome' => $prediction->predicted_outcome,
                'is_banker' => $prediction->is_banker,
                'points_awarded' => (float) $prediction->points_awarded,
                'scoring_status' => $prediction->scoring_status,
                'actual_home_score' => $prediction->scoring_status === 'scored' ? $prediction->fixture->actual_home_score : null,
                'actual_away_score' => $prediction->scoring_status === 'scored' ? $prediction->fixture->actual_away_score : null,
                'points_breakdown' => [
                    'outcome_points' => (float) $prediction->outcome_points,
                    'exact_score_points' => (float) $prediction->exact_score_points,
                    'close_score_points' => (float) $prediction->close_score_points,
                    'banker_bonus_points' => (float) $prediction->banker_bonus_points,
                ],
                'fixture' => [
                    'home_team_name' => $prediction->fixture->home_team_name_snapshot,
                    'away_team_name' => $prediction->fixture->away_team_name_snapshot,
                    'kickoff_at' => $prediction->fixture->kickoff_at?->toIso8601String(),
                ],
            ])->values()->all(),
        ];
    }

    private function validatedPredictions(PredictorRound $round, PredictorCampaign $campaign, array $predictions, bool $requireComplete): Collection
    {
        $fixtures = $round->fixtures->keyBy('id');

        if (collect($predictions)->pluck('round_fixture_id')->duplicates()->isNotEmpty()) {
            throw ApiException::unprocessable('Duplicate fixture predictions are not allowed.', 'PREDICTOR_INVALID_PAYLOAD');
        }

        $rows = collect($predictions)->map(function (array $prediction) use ($fixtures): array {
            $fixtureId = (int) ($prediction['round_fixture_id'] ?? 0);
            $homeScore = $prediction['predicted_home_score'] ?? null;
            $awayScore = $prediction['predicted_away_score'] ?? null;

            if (! $fixtures->has($fixtureId)) {
                throw ApiException::unprocessable('One or more fixtures do not belong to this round.', 'PREDICTOR_INVALID_PAYLOAD');
            }

            if (! is_int($homeScore) || ! is_int($awayScore) || $homeScore < 0 || $awayScore < 0) {
                throw ApiException::unprocessable('Prediction scores must be non-negative integers.', 'PREDICTOR_INVALID_PAYLOAD');
            }

            return [
                'round_fixture_id' => $fixtureId,
                'predicted_home_score' => $homeScore,
                'predicted_away_score' => $awayScore,
                'predicted_outcome' => $this->predictedOutcome($homeScore, $awayScore),
                'is_banker' => (bool) ($prediction['is_banker'] ?? false),
            ];
        });

        if (! $campaign->banker_enabled && $rows->contains(fn (array $row): bool => $row['is_banker'])) {
            throw ApiException::unprocessable('Banker picks are disabled for this campaign.', 'PREDICTOR_INVALID_BANKER');
        }

        if ($rows->where('is_banker', true)->count() > 1) {
            throw ApiException::unprocessable('Only one banker selection is allowed.', 'PREDICTOR_INVALID_BANKER');
        }

        if ($requireComplete && ! $round->allow_partial_submission && $rows->count() !== $fixtures->count()) {
            throw ApiException::unprocessable('All fixtures must be predicted before submission.', 'PREDICTOR_SUBMISSION_LOCKED');
        }

        if ($rows->isEmpty()) {
            throw ApiException::unprocessable('At least one prediction row is required.', 'PREDICTOR_INVALID_PAYLOAD');
        }

        return $rows;
    }

    private function upsertEntry(PredictorRound $round, PredictorCampaign $campaign, string $userId, ?AuthBoxUserProfile $profile, string $status): PredictorRoundEntry
    {
        $entry = PredictorRoundEntry::query()
            ->where('round_id', $round->id)
            ->where('user_id', $userId)
            ->lockForUpdate()
            ->first();

        if ($entry && $entry->entry_status === 'submitted' && $status === 'draft') {
            throw ApiException::conflict('Submitted entries cannot be changed.', 'PREDICTOR_SUBMISSION_LOCKED');
        }

        if (! $entry) {
            $entry = PredictorRoundEntry::create([
                'round_id' => $round->id,
                'campaign_id' => $campaign->id,
                'season_id' => $round->season_id,
                'user_id' => $userId,
                'display_name_snapshot' => $profile?->displayName,
                'avatar_url_snapshot' => $profile?->avatarUrl,
                'entry_status' => $status,
                'banker_multiplier' => $campaign->banker_enabled ? $this->bankerMultiplier($campaign, $round->season) : null,
                'last_edited_at' => now(),
            ]);
        } else {
            $entry->forceFill([
                'display_name_snapshot' => $profile?->displayName ?? $entry->display_name_snapshot,
                'avatar_url_snapshot' => $profile?->avatarUrl ?? $entry->avatar_url_snapshot,
                'entry_status' => $status === 'submitted' ? 'submitted' : $entry->entry_status,
                'last_edited_at' => now(),
            ])->save();
        }

        return $entry;
    }

    private function syncPredictions(PredictorRoundEntry $entry, Collection $rows, bool $submitted): void
    {
        $bankerFixtureId = $rows->firstWhere('is_banker', true)['round_fixture_id'] ?? null;

        PredictorPrediction::query()
            ->where('round_entry_id', $entry->id)
            ->whereNotIn('round_fixture_id', $rows->pluck('round_fixture_id')->all())
            ->delete();

        foreach ($rows as $row) {
            PredictorPrediction::query()->updateOrCreate(
                [
                    'round_entry_id' => $entry->id,
                    'round_fixture_id' => $row['round_fixture_id'],
                ],
                [
                    'predicted_home_score' => $row['predicted_home_score'],
                    'predicted_away_score' => $row['predicted_away_score'],
                    'predicted_outcome' => $row['predicted_outcome'],
                    'is_banker' => $row['is_banker'],
                    'was_submitted' => $submitted,
                ]
            );
        }

        $entry->forceFill([
            'banker_fixture_id' => $bankerFixtureId,
            'entry_status' => $submitted ? 'submitted' : 'draft',
        ])->save();
    }

    private function predictedOutcome(int $homeScore, int $awayScore): string
    {
        return match (true) {
            $homeScore > $awayScore => 'home_win',
            $homeScore < $awayScore => 'away_win',
            default => 'draw',
        };
    }

    private function bankerMultiplier(PredictorCampaign $campaign, $season): float
    {
        $config = array_merge(config('predictor.default_scoring', []), $season?->scoring_config ?? []);

        return (float) ($config['banker_multiplier'] ?? 2);
    }
}
