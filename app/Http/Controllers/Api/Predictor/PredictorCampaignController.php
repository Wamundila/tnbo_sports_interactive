<?php

namespace App\Http\Controllers\Api\Predictor;

use App\Auth\JwtUser;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Models\PredictorCampaign;
use App\Models\PredictorRoundEntry;
use App\Services\AuthBoxClient;
use App\Services\PredictorCampaignResolver;
use App\Services\PredictorLeaderboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PredictorCampaignController extends Controller
{
    public function __construct(
        private readonly PredictorCampaignResolver $resolver,
        private readonly PredictorLeaderboardService $leaderboards,
        private readonly AuthBoxClient $authBoxClient,
    ) {
    }

    public function index(): JsonResponse
    {
        $items = $this->resolver->visibleCampaigns()->map(function (PredictorCampaign $campaign): array {
            $season = $this->resolver->currentSeason($campaign);

            return [
                'id' => $campaign->id,
                'slug' => $campaign->slug,
                'display_name' => $campaign->display_name,
                'sponsor_name' => $campaign->sponsor_name,
                'scope_type' => $campaign->scope_type,
                'status' => $campaign->status,
                'current_season' => $season ? [
                    'id' => $season->id,
                    'name' => $season->name,
                ] : null,
            ];
        })->values()->all();

        return response()->json(['items' => $items]);
    }

    public function show(PredictorCampaign $campaign): JsonResponse
    {
        $season = $this->resolver->currentSeason($campaign);

        return response()->json([
            'campaign' => [
                'id' => $campaign->id,
                'slug' => $campaign->slug,
                'display_name' => $campaign->display_name,
                'sponsor_name' => $campaign->sponsor_name,
                'description' => $campaign->description,
                'scope_type' => $campaign->scope_type,
                'status' => $campaign->status,
            ],
            'current_season' => $season ? [
                'id' => $season->id,
                'name' => $season->name,
                'status' => $season->status,
            ] : null,
        ]);
    }

    public function currentRound(PredictorCampaign $campaign): JsonResponse
    {
        $season = $this->resolver->currentSeason($campaign);
        $round = $this->resolver->currentRound($campaign);
        $config = $this->resolver->scoringConfig($campaign, $season);

        return response()->json([
            'campaign' => [
                'id' => $campaign->id,
                'slug' => $campaign->slug,
                'display_name' => $campaign->display_name,
            ],
            'season' => $season ? [
                'id' => $season->id,
                'name' => $season->name,
            ] : null,
            'round' => $round ? [
                'id' => $round->id,
                'name' => $round->name,
                'status' => $round->status,
                'opens_at' => $round->opens_at->toIso8601String(),
                'prediction_closes_at' => $round->prediction_closes_at->toIso8601String(),
                'round_closes_at' => $round->round_closes_at->toIso8601String(),
            ] : null,
            'fixtures' => $round ? $round->fixtures->map(fn ($fixture): array => [
                'id' => $fixture->id,
                'display_order' => $fixture->display_order,
                'competition_name' => $fixture->competition_name_snapshot,
                'kickoff_at' => $fixture->kickoff_at?->toIso8601String(),
                'home_team' => [
                    'id' => $fixture->home_team_id,
                    'name' => $fixture->home_team_name_snapshot,
                    'logo_url' => $fixture->home_team_logo_url,
                ],
                'away_team' => [
                    'id' => $fixture->away_team_id,
                    'name' => $fixture->away_team_name_snapshot,
                    'logo_url' => $fixture->away_team_logo_url,
                ],
            ])->values()->all() : [],
            'scoring_rules' => $config,
        ]);
    }

    public function summary(Request $request): JsonResponse
    {
        /** @var JwtUser $user */
        $user = $request->user();
        $validated = $request->validate([
            'campaign_slug' => ['nullable', 'string', 'max:100'],
        ]);

        $campaign = $this->resolver->resolveCampaign($validated['campaign_slug'] ?? null);
        $season = $this->resolver->currentSeason($campaign);
        $round = $this->resolver->currentRound($campaign);
        $entry = $round
            ? PredictorRoundEntry::query()->where('round_id', $round->id)->where('user_id', $user->userId())->first()
            : null;
        $verified = $this->verifiedState($request, $user->token(), $user->userId());
        $state = $this->resolver->surfaceState($round, $entry, $verified);
        $userSummary = $this->userSummary($campaign, $season, $user->userId());

        return response()->json([
            'campaign' => [
                'id' => $campaign->id,
                'slug' => $campaign->slug,
                'display_name' => $campaign->display_name,
                'status' => $campaign->status,
            ],
            'predictor_surface' => [
                'title' => $round ? 'Predict '.$round->name : $campaign->display_name,
                'short_description' => $round
                    ? sprintf('%d fixtures - closes %s', $round->fixtures->count(), $round->prediction_closes_at->diffForHumans())
                    : 'No active round is available right now.',
                'state' => $state,
                'auth_state' => $verified ? 'verified' : 'unverified',
                'available' => $round ? $this->resolver->isRoundOpen($round) : false,
                'requires_verified_account' => true,
                'opens_at' => $round?->opens_at?->toIso8601String(),
                'prediction_closes_at' => $round?->prediction_closes_at?->toIso8601String(),
                'round_closes_at' => $round?->round_closes_at?->toIso8601String(),
                'current_round' => $round ? [
                    'id' => $round->id,
                    'name' => $round->name,
                ] : null,
                'entry_summary' => $entry ? [
                    'entry_id' => $entry->id,
                    'entry_status' => $entry->entry_status,
                    'saved_at' => $entry->last_edited_at?->toIso8601String(),
                    'submitted_at' => $entry->submitted_at?->toIso8601String(),
                    'predictions_count' => $entry->predictions()->count(),
                    'completed_predictions_count' => $entry->predictions()->count(),
                    'banker_fixture_id' => $entry->banker_fixture_id,
                ] : null,
                'cta' => $this->ctaPayload($state, $campaign, $round),
            ],
            'user_summary' => $userSummary,
            'leaderboard_previews' => $this->leaderboards->previewPayload(
                campaign: $campaign,
                season: $season,
                round: $round,
                limit: (int) config('predictor.leaderboard_preview_limit', 5),
            ),
        ]);
    }

    public function leaderboard(Request $request, PredictorCampaign $campaign, string $boardType): JsonResponse
    {
        /** @var JwtUser $user */
        $user = $request->user();
        $request->validate([
            'limit' => ['nullable', 'integer', 'min:1', 'max:200'],
        ]);

        if (! in_array($boardType, ['round', 'monthly', 'season'], true)) {
            throw ApiException::notFound('Predictor leaderboard not found.', 'PREDICTOR_LEADERBOARD_NOT_FOUND');
        }

        return response()->json($this->leaderboards->leaderboardPayload(
            boardType: $boardType,
            campaign: $campaign,
            season: $this->resolver->currentSeason($campaign),
            round: $this->resolver->currentRound($campaign),
            userId: $user->userId(),
            limit: (int) ($request->integer('limit') ?: config('predictor.leaderboard_default_limit', 50)),
        ));
    }

    private function verifiedState(Request $request, string $token, string $userId): bool
    {
        try {
            return $this->authBoxClient->currentUserProfile($token, $userId)->verified;
        } catch (ApiException) {
            return false;
        }
    }

    private function userSummary(PredictorCampaign $campaign, $season, string $userId): array
    {
        $entriesQuery = PredictorRoundEntry::query()
            ->where('campaign_id', $campaign->id)
            ->where('user_id', $userId)
            ->whereIn('entry_status', ['submitted', 'locked', 'scored']);

        if ($season) {
            $entriesQuery->where('season_id', $season->id);
        }

        $entries = $entriesQuery->get();
        $predictionsCount = $entries->sum(fn ($entry) => $entry->predictions()->where('was_submitted', true)->count());
        $correctOutcomes = (int) $entries->sum('correct_outcomes_count');

        return [
            'season_points' => (float) $entries->sum('total_points'),
            'rounds_played' => $entries->count(),
            'accuracy_percentage' => $predictionsCount > 0 ? round(($correctOutcomes / $predictionsCount) * 100, 2) : 0.0,
            'current_rank' => $this->leaderboards->currentRank($campaign, $season, $userId),
        ];
    }

    private function ctaPayload(string $state, PredictorCampaign $campaign, $round): array
    {
        return match ($state) {
            'verification_required' => ['label' => 'Verify Account', 'action' => 'verify_account', 'destination' => 'account_verification', 'disabled' => false],
            'draft_saved' => ['label' => 'Continue Picks', 'action' => 'open_predictor_dashboard', 'destination' => 'predictor_dashboard', 'disabled' => false],
            'submitted' => ['label' => 'View Entry', 'action' => 'open_predictor_dashboard', 'destination' => 'predictor_dashboard', 'disabled' => false],
            'available' => ['label' => 'Make Picks', 'action' => 'open_predictor_dashboard', 'destination' => 'predictor_dashboard', 'disabled' => false],
            'not_open' => ['label' => 'Opens Soon', 'action' => 'open_predictor_dashboard', 'destination' => 'predictor_dashboard', 'disabled' => false],
            'closed', 'completed' => ['label' => 'View Results', 'action' => 'open_predictor_dashboard', 'destination' => 'predictor_dashboard', 'disabled' => false],
            default => ['label' => 'Predictor Unavailable', 'action' => 'none', 'destination' => 'predictor_dashboard', 'disabled' => true],
        };
    }
}
