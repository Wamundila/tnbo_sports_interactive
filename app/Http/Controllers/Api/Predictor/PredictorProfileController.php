<?php

namespace App\Http\Controllers\Api\Predictor;

use App\Auth\JwtUser;
use App\Http\Controllers\Controller;
use App\Models\PredictorCampaign;
use App\Models\PredictorRoundEntry;
use App\Services\PredictorCampaignResolver;
use App\Services\PredictorLeaderboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PredictorProfileController extends Controller
{
    public function __construct(
        private readonly PredictorCampaignResolver $resolver,
        private readonly PredictorLeaderboardService $leaderboards,
    ) {
    }

    public function performance(Request $request): JsonResponse
    {
        /** @var JwtUser $user */
        $user = $request->user();
        $request->validate([
            'campaign_slug' => ['nullable', 'string', 'max:100'],
        ]);

        $campaign = $this->resolver->resolveCampaign($request->string('campaign_slug')->toString() ?: null);
        $season = $this->resolver->currentSeason($campaign);

        $entries = PredictorRoundEntry::query()
            ->where('campaign_id', $campaign->id)
            ->where('user_id', $user->userId())
            ->whereIn('entry_status', ['submitted', 'locked', 'scored'])
            ->when($season, fn ($query) => $query->where('season_id', $season->id))
            ->get();

        $predictionsCount = $entries->sum(fn (PredictorRoundEntry $entry) => $entry->predictions()->where('was_submitted', true)->count());
        $correctOutcomes = (int) $entries->sum('correct_outcomes_count');

        return response()->json([
            'campaign_slug' => $campaign->slug,
            'season_points' => (float) $entries->sum('total_points'),
            'rounds_played' => $entries->count(),
            'accuracy_percentage' => $predictionsCount > 0 ? round(($correctOutcomes / $predictionsCount) * 100, 2) : 0.0,
            'exact_scores_count' => (int) $entries->sum('exact_scores_count'),
            'correct_outcomes_count' => $correctOutcomes,
            'current_rank' => $this->leaderboards->currentRank($campaign, $season, $user->userId()),
        ]);
    }

    public function history(Request $request): JsonResponse
    {
        /** @var JwtUser $user */
        $user = $request->user();
        $request->validate([
            'campaign_slug' => ['nullable', 'string', 'max:100'],
        ]);

        $campaign = $this->resolver->resolveCampaign($request->string('campaign_slug')->toString() ?: null);

        $items = PredictorRoundEntry::query()
            ->with(['round', 'season'])
            ->where('campaign_id', $campaign->id)
            ->where('user_id', $user->userId())
            ->whereIn('entry_status', ['submitted', 'locked', 'scored'])
            ->latest('submitted_at')
            ->limit(20)
            ->get()
            ->map(fn (PredictorRoundEntry $entry): array => [
                'round_id' => $entry->round_id,
                'round_name' => $entry->round->name,
                'season_name' => $entry->season->name,
                'campaign_display_name' => $campaign->display_name,
                'completed_at' => $entry->submitted_at?->toIso8601String(),
                'score_total' => (float) $entry->total_points,
                'correct_outcomes_count' => $entry->correct_outcomes_count,
                'fixture_count' => $entry->round->fixture_count,
                'entry_status' => $entry->entry_status,
            ])
            ->all();

        return response()->json(['items' => $items]);
    }
}

