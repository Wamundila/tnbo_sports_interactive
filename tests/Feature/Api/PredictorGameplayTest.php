<?php

namespace Tests\Feature\Api;

use App\Models\PredictorCampaign;
use App\Models\PredictorLeaderboardEntry;
use App\Models\PredictorPrediction;
use App\Models\PredictorRound;
use App\Models\PredictorRoundEntry;
use App\Models\PredictorRoundFixture;
use App\Models\PredictorSeason;
use App\Services\PredictorCampaignResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Mockery;
use RuntimeException;
use Tests\Concerns\GeneratesJwtTokens;
use Tests\TestCase;

class PredictorGameplayTest extends TestCase
{
    use GeneratesJwtTokens;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configureJwtTestEnvironment();
    }

    public function test_predictor_summary_returns_available_state_for_verified_user(): void
    {
        [$campaign, $season, $round] = $this->createOpenCampaign();
        $this->fakeAuthBoxProfile();

        PredictorLeaderboardEntry::create([
            'leaderboard_type' => 'season',
            'campaign_id' => $campaign->id,
            'season_id' => $season->id,
            'round_id' => null,
            'leaderboard_period_key' => null,
            'user_id' => 'ts_123',
            'display_name_snapshot' => 'Predictor User',
            'avatar_url_snapshot' => null,
            'rank' => 12,
            'points_total' => 84.5,
            'rounds_played' => 7,
            'correct_outcomes_count' => 19,
            'exact_scores_count' => 6,
            'close_score_count' => 4,
            'accuracy_percentage' => 68.2,
        ]);

        $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/predictor/campaigns/'.$campaign->slug.'/current-round')
            ->assertOk()
            ->assertJsonPath('round.id', $round->id)
            ->assertJsonCount(4, 'fixtures');

        $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/predictor/summary?campaign_slug='.$campaign->slug)
            ->assertOk()
            ->assertJsonPath('campaign.slug', $campaign->slug)
            ->assertJsonPath('predictor_surface.state', 'available')
            ->assertJsonPath('predictor_surface.current_round.id', $round->id)
            ->assertJsonPath('predictor_surface.auth_state', 'verified')
            ->assertJsonPath('user_summary.current_rank', 12)
            ->assertJsonPath('leaderboard_previews.round.entries', []);
    }

    public function test_predictor_summary_can_fall_back_to_unavailable_state_when_resolution_fails(): void
    {
        [$campaign] = $this->createOpenCampaign();

        $resolver = Mockery::mock(PredictorCampaignResolver::class);
        $resolver->shouldReceive('resolveCampaign')->once()->with($campaign->slug)->andReturn($campaign);
        $resolver->shouldReceive('currentSeason')->once()->with($campaign)->andThrow(new RuntimeException('round resolution failed'));
        $this->app->instance(PredictorCampaignResolver::class, $resolver);

        $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/predictor/summary?campaign_slug='.$campaign->slug)
            ->assertOk()
            ->assertJsonPath('campaign.slug', $campaign->slug)
            ->assertJsonPath('predictor_surface.state', 'unavailable')
            ->assertJsonPath('predictor_surface.auth_state', 'unknown')
            ->assertJsonPath('predictor_surface.available', false)
            ->assertJsonPath('predictor_surface.cta.disabled', true)
            ->assertJsonPath('predictor_surface.cta.destination', null)
            ->assertJsonPath('user_summary', null)
            ->assertJsonPath('leaderboard_previews.round.entries', []);
    }

    public function test_unverified_user_is_blocked_from_predictor_draft_and_summary_can_signal_verification_required(): void
    {
        [$campaign, , $round] = $this->createOpenCampaign();
        $this->fakeAuthBoxProfile([
            'email_verified_at' => null,
            'verified' => false,
        ]);

        $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/predictor/summary?campaign_slug='.$campaign->slug)
            ->assertOk()
            ->assertJsonPath('predictor_surface.state', 'verification_required')
            ->assertJsonPath('predictor_surface.auth_state', 'unverified');

        $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/predictor/rounds/'.$round->id.'/draft', [
                'predictions' => [$this->predictionRow($round->fixtures[0]->id, 2, 1, true)],
            ])
            ->assertForbidden()
            ->assertJsonPath('code', 'PREDICTOR_VERIFICATION_REQUIRED');
    }

    public function test_verified_user_can_save_draft_submit_and_view_predictor_entry_performance_and_history(): void
    {
        [$campaign, $season, $round] = $this->createOpenCampaign();
        $this->fakeAuthBoxProfile([
            'display_name' => 'Predictor User',
            'avatar_url' => 'https://cdn.test/predictor.png',
        ]);

        $predictions = collect($round->fixtures)->map(fn (PredictorRoundFixture $fixture, int $index) => $this->predictionRow(
            fixtureId: $fixture->id,
            homeScore: $index + 1,
            awayScore: $index,
            isBanker: $index === 0,
        ))->all();

        $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/predictor/rounds/'.$round->id.'/draft', ['predictions' => $predictions])
            ->assertOk()
            ->assertJsonPath('entry_status', 'draft')
            ->assertJsonPath('predictions_count', 4)
            ->assertJsonPath('banker_fixture_id', $round->fixtures[0]->id);

        $submitResponse = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/predictor/rounds/'.$round->id.'/submit', ['predictions' => $predictions]);

        $submitResponse->assertOk()
            ->assertJsonPath('entry_status', 'submitted');

        $entry = PredictorRoundEntry::query()
            ->where('round_id', $round->id)
            ->where('user_id', 'ts_123')
            ->firstOrFail();

        $entry->update([
            'total_points' => 11.5,
            'correct_outcomes_count' => 3,
            'exact_scores_count' => 1,
            'close_score_count' => 1,
        ]);

        $scoredPrediction = PredictorPrediction::query()
            ->where('round_entry_id', $entry->id)
            ->where('round_fixture_id', $round->fixtures[0]->id)
            ->firstOrFail();

        $scoredPrediction->update([
            'points_awarded' => 8,
            'outcome_points' => 3,
            'exact_score_points' => 5,
            'close_score_points' => 0,
            'banker_bonus_points' => 0,
            'scoring_status' => 'scored',
        ]);

        $round->fixtures[0]->update([
            'result_status' => 'completed',
            'actual_home_score' => 1,
            'actual_away_score' => 0,
        ]);

        PredictorLeaderboardEntry::create([
            'leaderboard_type' => 'season',
            'campaign_id' => $campaign->id,
            'season_id' => $season->id,
            'round_id' => null,
            'leaderboard_period_key' => null,
            'user_id' => 'ts_123',
            'display_name_snapshot' => 'Predictor User',
            'avatar_url_snapshot' => 'https://cdn.test/predictor.png',
            'rank' => 12,
            'points_total' => 11.5,
            'rounds_played' => 1,
            'correct_outcomes_count' => 3,
            'exact_scores_count' => 1,
            'close_score_count' => 1,
            'accuracy_percentage' => 75,
        ]);

        $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/predictor/rounds/'.$round->id.'/my-entry')
            ->assertOk()
            ->assertJsonPath('entry.entry_status', 'submitted')
            ->assertJsonPath('entry.banker_fixture_id', $round->fixtures[0]->id)
            ->assertJsonPath('entry.predictions.0.predicted_outcome', 'home_win')
            ->assertJsonPath('entry.predictions.0.is_banker', true)
            ->assertJsonPath('entry.predictions.0.actual_home_score', 1)
            ->assertJsonPath('entry.predictions.0.actual_away_score', 0)
            ->assertJsonPath('entry.predictions.0.points_breakdown.outcome_points', 3)
            ->assertJsonPath('entry.predictions.0.points_breakdown.exact_score_points', 5)
            ->assertJsonPath('entry.predictions.0.points_breakdown.close_score_points', 0)
            ->assertJsonPath('entry.predictions.0.points_breakdown.banker_bonus_points', 0)
            ->assertJsonPath('entry.predictions.1.actual_home_score', null)
            ->assertJsonPath('entry.predictions.1.actual_away_score', null);

        $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/predictor/me/performance?campaign_slug='.$campaign->slug)
            ->assertOk()
            ->assertJsonPath('season_points', 11.5)
            ->assertJsonPath('rounds_played', 1)
            ->assertJsonPath('current_rank', 12);

        $historyResponse = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/predictor/me/history?campaign_slug='.$campaign->slug);

        $historyResponse->assertOk()
            ->assertJsonPath('items.0.round_name', 'Round 8')
            ->assertJsonPath('items.0.campaign_display_name', 'MTN Super League Predictor')
            ->assertJsonPath('items.0.fixture_count', 4)
            ->assertJsonPath('items.0.score_total', 11.5);
    }

    private function fakeAuthBoxProfile(array $overrides = []): void
    {
        Http::fake([
            'https://authbox.test/api/v1/me' => Http::response(array_merge([
                'user_id' => 'ts_123',
                'display_name' => 'Predictor User',
                'avatar_url' => null,
                'email_verified_at' => now()->toIso8601String(),
                'verified' => true,
            ], $overrides)),
        ]);
    }

    private function createOpenCampaign(): array
    {
        $campaign = PredictorCampaign::create([
            'name' => 'Super League Predictor',
            'slug' => 'super_league_predictor',
            'display_name' => 'MTN Super League Predictor',
            'scope_type' => 'single_competition',
            'default_fixture_count' => 4,
            'banker_enabled' => true,
            'status' => 'active',
            'visibility' => 'public',
            'starts_at' => now()->subWeek(),
        ]);

        $season = PredictorSeason::create([
            'campaign_id' => $campaign->id,
            'name' => '2026 Season',
            'slug' => '2026-season',
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
            'status' => 'active',
            'scoring_config' => [
                'outcome_points' => 3,
                'exact_score_points' => 5,
                'close_score_points' => 1.5,
                'banker_enabled' => true,
                'banker_multiplier' => 2,
            ],
            'is_current' => true,
        ]);

        $round = PredictorRound::create([
            'season_id' => $season->id,
            'name' => 'Round 8',
            'round_number' => 8,
            'opens_at' => now()->subDay(),
            'prediction_closes_at' => now()->addHours(3),
            'round_closes_at' => now()->addDay(),
            'status' => 'open',
            'fixture_count' => 4,
            'allow_partial_submission' => false,
        ]);

        foreach ([1, 2, 3, 4] as $order) {
            PredictorRoundFixture::create([
                'round_id' => $round->id,
                'competition_id' => 1,
                'competition_name_snapshot' => 'MTN Super League',
                'home_team_id' => 100 + $order,
                'away_team_id' => 200 + $order,
                'home_team_name_snapshot' => 'Home '.$order,
                'away_team_name_snapshot' => 'Away '.$order,
                'kickoff_at' => now()->addHours($order),
                'display_order' => $order,
                'result_status' => 'pending',
            ]);
        }

        return [$campaign, $season, $round->load('fixtures')];
    }

    private function predictionRow(int $fixtureId, int $homeScore, int $awayScore, bool $isBanker): array
    {
        return [
            'round_fixture_id' => $fixtureId,
            'predicted_home_score' => $homeScore,
            'predicted_away_score' => $awayScore,
            'is_banker' => $isBanker,
        ];
    }
}


