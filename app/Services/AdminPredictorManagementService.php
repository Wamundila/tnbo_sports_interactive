<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\Admin;
use App\Models\PredictorCampaign;
use App\Models\PredictorRound;
use App\Models\PredictorRoundFixture;
use App\Models\PredictorSeason;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class AdminPredictorManagementService
{
    public function __construct(private readonly PredictorScoringService $scoringService)
    {
    }

    public function createCampaign(array $payload, Admin $admin): PredictorCampaign
    {
        return PredictorCampaign::create([
            ...$this->campaignAttributes($payload),
            'created_by_admin_id' => $admin->id,
            'updated_by_admin_id' => $admin->id,
        ]);
    }

    public function updateCampaign(PredictorCampaign $campaign, array $payload, Admin $admin): PredictorCampaign
    {
        $campaign->fill($this->campaignAttributes($payload));
        $campaign->updated_by_admin_id = $admin->id;
        $campaign->save();

        return $campaign->fresh();
    }

    public function createSeason(PredictorCampaign $campaign, array $payload): PredictorSeason
    {
        return DB::transaction(function () use ($campaign, $payload): PredictorSeason {
            $season = PredictorSeason::create([
                ...$this->seasonAttributes($payload),
                'campaign_id' => $campaign->id,
            ]);

            $this->syncCurrentSeason($campaign, $season, (bool) $payload['is_current']);

            return $season->fresh();
        });
    }

    public function updateSeason(PredictorSeason $season, array $payload): PredictorSeason
    {
        return DB::transaction(function () use ($season, $payload): PredictorSeason {
            $season->fill($this->seasonAttributes($payload));
            $season->save();

            $this->syncCurrentSeason($season->campaign, $season, (bool) $payload['is_current']);

            return $season->fresh();
        });
    }

    public function createRound(PredictorSeason $season, array $payload): PredictorRound
    {
        return DB::transaction(function () use ($season, $payload): PredictorRound {
            $round = PredictorRound::create([
                ...$this->roundAttributes($payload),
                'season_id' => $season->id,
            ]);

            $this->syncFixtures($round, $payload['fixtures'] ?? []);
            $this->assertRoundConfiguration($round->fresh('fixtures'));

            return $round->fresh(['fixtures', 'season.campaign']);
        });
    }

    public function updateRound(PredictorRound $round, array $payload): PredictorRound
    {
        return DB::transaction(function () use ($round, $payload): PredictorRound {
            $round->fill($this->roundAttributes($payload));
            $round->save();

            $this->syncFixtures($round, $payload['fixtures'] ?? []);
            $this->assertRoundConfiguration($round->fresh('fixtures'));

            return $round->fresh(['fixtures', 'season.campaign']);
        });
    }

    public function transitionRound(PredictorRound $round, string $status): PredictorRound
    {
        $round->loadMissing(['fixtures', 'season.campaign']);

        return DB::transaction(function () use ($round, $status): PredictorRound {
            $round->status = $status;
            $this->assertRoundConfiguration($round);

            if ($status !== 'completed') {
                $round->leaderboard_frozen_at = null;
            }

            $round->save();

            return $round->fresh(['fixtures', 'season.campaign']);
        });
    }

    public function scoreRound(PredictorRound $round, bool $force = false): array
    {
        return $this->scoringService->scoreRound($round, $force);
    }

    private function campaignAttributes(array $payload): array
    {
        return Arr::only($payload, [
            'name',
            'slug',
            'display_name',
            'sponsor_name',
            'description',
            'banner_image_url',
            'scope_type',
            'default_fixture_count',
            'banker_enabled',
            'status',
            'visibility',
            'starts_at',
            'ends_at',
        ]);
    }

    private function seasonAttributes(array $payload): array
    {
        return [
            'name' => $payload['name'],
            'slug' => $payload['slug'],
            'start_date' => $payload['start_date'],
            'end_date' => $payload['end_date'],
            'status' => $payload['status'],
            'rules_text' => $payload['rules_text'] ?? null,
            'is_current' => (bool) $payload['is_current'],
            'scoring_config' => $payload['scoring_config'],
        ];
    }

    private function roundAttributes(array $payload): array
    {
        return [
            'name' => $payload['name'],
            'round_number' => $payload['round_number'] ?? null,
            'opens_at' => $payload['opens_at'],
            'prediction_closes_at' => $payload['prediction_closes_at'],
            'round_closes_at' => $payload['round_closes_at'],
            'status' => $payload['status'],
            'allow_partial_submission' => (bool) $payload['allow_partial_submission'],
            'notes' => $payload['notes'] ?? null,
        ];
    }

    private function syncCurrentSeason(PredictorCampaign $campaign, PredictorSeason $season, bool $isCurrent): void
    {
        if (! $isCurrent) {
            return;
        }

        PredictorSeason::query()
            ->where('campaign_id', $campaign->id)
            ->whereKeyNot($season->id)
            ->update(['is_current' => false]);
    }

    private function syncFixtures(PredictorRound $round, array $fixtures): void
    {
        $existing = $round->fixtures()->get()->keyBy('id');
        $incomingIds = collect($fixtures)->pluck('id')->filter()->map(fn ($id) => (int) $id);
        $deleteIds = $existing->keys()->diff($incomingIds);

        if ($deleteIds->isNotEmpty()) {
            PredictorRoundFixture::query()->whereIn('id', $deleteIds)->delete();
        }

        foreach (array_values($fixtures) as $index => $fixturePayload) {
            $fixture = isset($fixturePayload['id'])
                ? $existing->get((int) $fixturePayload['id'])
                : null;

            if (isset($fixturePayload['id']) && ! $fixture) {
                throw ApiException::unprocessable('Fixture does not belong to the selected round.', 'PREDICTOR_CONFIGURATION_ERROR');
            }

            $fixture ??= new PredictorRoundFixture(['round_id' => $round->id]);
            $fixture->fill(Arr::only($fixturePayload, [
                'source_fixture_id',
                'competition_id',
                'competition_name_snapshot',
                'home_team_id',
                'away_team_id',
                'home_team_name_snapshot',
                'away_team_name_snapshot',
                'home_team_logo_url',
                'away_team_logo_url',
                'kickoff_at',
                'result_status',
                'actual_home_score',
                'actual_away_score',
                'result_source',
            ]));
            $fixture->display_order = (int) ($fixturePayload['display_order'] ?? ($index + 1));
            $fixture->round_id = $round->id;

            if (in_array($fixture->result_status, ['pending', 'postponed', 'cancelled'], true)) {
                $fixture->actual_home_score = null;
                $fixture->actual_away_score = null;
            }

            $fixture->result_entered_at = $fixture->actual_home_score !== null && $fixture->actual_away_score !== null
                ? now()
                : null;

            $fixture->save();
        }

        $round->fixture_count = count($fixtures);
        $round->save();
    }

    private function assertRoundConfiguration(PredictorRound $round): void
    {
        if (! $round->opens_at || ! $round->prediction_closes_at || ! $round->round_closes_at) {
            throw ApiException::unprocessable('Round timing must be configured before saving.', 'PREDICTOR_CONFIGURATION_ERROR');
        }

        if (! $round->opens_at->lt($round->prediction_closes_at) || ! $round->prediction_closes_at->lt($round->round_closes_at)) {
            throw ApiException::unprocessable('Round timing is invalid. Make sure opens_at is before prediction close and round close.', 'PREDICTOR_CONFIGURATION_ERROR');
        }

        if (in_array($round->status, ['open', 'locked', 'scoring', 'completed'], true) && $round->fixtures->isEmpty()) {
            throw ApiException::unprocessable('A round needs at least one fixture before it can leave draft.', 'PREDICTOR_CONFIGURATION_ERROR');
        }
    }
}
