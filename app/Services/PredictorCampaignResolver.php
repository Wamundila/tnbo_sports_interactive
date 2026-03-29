<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\PredictorCampaign;
use App\Models\PredictorRound;
use App\Models\PredictorRoundEntry;
use App\Models\PredictorSeason;
use Illuminate\Database\Eloquent\Collection;

class PredictorCampaignResolver
{
    public function visibleCampaigns(): Collection
    {
        return PredictorCampaign::query()
            ->with(['seasons' => fn ($query) => $query->where('is_current', true)->orWhere('status', 'active')])
            ->where('visibility', 'public')
            ->whereIn('status', ['active', 'draft'])
            ->orderByDesc('starts_at')
            ->get();
    }

    public function resolveCampaign(?string $campaignSlug = null): PredictorCampaign
    {
        $query = PredictorCampaign::query()
            ->where('visibility', 'public')
            ->whereIn('status', ['active', 'draft']);

        $campaign = $campaignSlug
            ? $query->where('slug', $campaignSlug)->first()
            : $query->orderByDesc('starts_at')->first();

        if (! $campaign) {
            throw ApiException::notFound('Predictor campaign not found.', 'PREDICTOR_CAMPAIGN_NOT_FOUND');
        }

        return $campaign;
    }

    public function currentSeason(PredictorCampaign $campaign): ?PredictorSeason
    {
        return PredictorSeason::query()
            ->where('campaign_id', $campaign->id)
            ->orderByDesc('is_current')
            ->orderByRaw("CASE WHEN status = 'active' THEN 0 ELSE 1 END")
            ->orderByDesc('start_date')
            ->first();
    }

    public function currentRound(PredictorCampaign $campaign): ?PredictorRound
    {
        $season = $this->currentSeason($campaign);

        if (! $season) {
            return null;
        }

        $now = now();

        $activeRound = PredictorRound::query()
            ->with('fixtures')
            ->where('season_id', $season->id)
            ->where('opens_at', '<=', $now)
            ->where('round_closes_at', '>=', $now)
            ->orderBy('opens_at')
            ->first();

        if ($activeRound) {
            return $activeRound;
        }

        $upcomingRound = PredictorRound::query()
            ->with('fixtures')
            ->where('season_id', $season->id)
            ->where('opens_at', '>', $now)
            ->orderBy('opens_at')
            ->first();

        if ($upcomingRound) {
            return $upcomingRound;
        }

        return PredictorRound::query()
            ->with('fixtures')
            ->where('season_id', $season->id)
            ->latest('opens_at')
            ->first();
    }

    public function scoringConfig(PredictorCampaign $campaign, ?PredictorSeason $season = null): array
    {
        return array_merge(
            config('predictor.default_scoring', []),
            $season?->scoring_config ?? []
        );
    }

    public function isRoundOpen(PredictorRound $round): bool
    {
        if ($round->status !== 'open') {
            return false;
        }

        return $round->opens_at->isPast() && $round->prediction_closes_at->isFuture();
    }

    public function ensureRoundAcceptingPredictions(PredictorRound $round): PredictorRound
    {
        if ($round->status === 'completed') {
            throw ApiException::conflict('This round is completed.', 'PREDICTOR_ROUND_CLOSED');
        }

        if ($round->opens_at->isFuture() || $round->status === 'draft') {
            throw ApiException::conflict('Prediction window is not open yet.', 'PREDICTOR_ROUND_NOT_OPEN');
        }

        if ($round->prediction_closes_at->isPast() || in_array($round->status, ['locked', 'scoring', 'cancelled'], true)) {
            throw ApiException::conflict('Prediction window is closed for this round.', 'PREDICTOR_ROUND_CLOSED');
        }

        return $round;
    }

    public function surfaceState(?PredictorRound $round, ?PredictorRoundEntry $entry, bool $verified): string
    {
        if (! $round) {
            return 'no_round';
        }

        if ($round->status === 'completed') {
            return 'completed';
        }

        if ($entry?->entry_status === 'submitted') {
            return 'submitted';
        }

        if ($entry?->entry_status === 'draft') {
            if ($this->isRoundOpen($round)) {
                return 'draft_saved';
            }

            return $round->prediction_closes_at->isPast() ? 'closed' : 'not_open';
        }

        if ($round->opens_at->isFuture() || $round->status === 'draft') {
            return 'not_open';
        }

        if ($round->prediction_closes_at->isPast() || in_array($round->status, ['locked', 'scoring', 'cancelled'], true)) {
            return 'closed';
        }

        if (! $verified) {
            return 'verification_required';
        }

        return 'available';
    }
}
