<?php

namespace Tests\Feature\Web;

use App\Models\Admin;
use App\Models\PredictorCampaign;
use App\Models\PredictorLeaderboardEntry;
use App\Models\PredictorPrediction;
use App\Models\PredictorRound;
use App\Models\PredictorRoundEntry;
use App\Models\PredictorRoundFixture;
use App\Models\PredictorSeason;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\GeneratesJwtTokens;
use Tests\TestCase;

class PredictorScoringAdminTest extends TestCase
{
    use GeneratesJwtTokens;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configureJwtTestEnvironment();
    }

    public function test_admin_can_score_round_and_refresh_predictor_leaderboards(): void
    {
        $admin = Admin::create([
            'name' => 'Predictor Admin',
            'email' => 'scoring-admin@example.com',
            'password' => 'secret-pass',
            'role' => 'interactive_admin',
            'status' => 'active',
        ]);

        $this->actingAs($admin, 'admin');

        $campaign = PredictorCampaign::create([
            'name' => 'Super League Predictor',
            'slug' => 'super_league_predictor',
            'display_name' => 'MTN Super League Predictor',
            'scope_type' => 'single_competition',
            'default_fixture_count' => 2,
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
            'is_current' => true,
            'scoring_config' => [
                'outcome_points' => 3,
                'exact_score_points' => 5,
                'close_score_points' => 1.5,
                'banker_enabled' => true,
                'banker_multiplier' => 2,
            ],
        ]);

        $round = PredictorRound::create([
            'season_id' => $season->id,
            'name' => 'Round 8',
            'round_number' => 8,
            'opens_at' => now()->subDays(2),
            'prediction_closes_at' => now()->subDay(),
            'round_closes_at' => now()->addHours(6),
            'status' => 'locked',
            'fixture_count' => 2,
            'allow_partial_submission' => false,
        ]);

        $fixtureOne = PredictorRoundFixture::create([
            'round_id' => $round->id,
            'competition_name_snapshot' => 'MTN Super League',
            'home_team_name_snapshot' => 'Power Dynamos',
            'away_team_name_snapshot' => 'Zesco United',
            'kickoff_at' => now()->subHours(4),
            'display_order' => 1,
            'result_status' => 'completed',
            'actual_home_score' => 2,
            'actual_away_score' => 1,
            'result_entered_at' => now()->subHours(1),
            'result_source' => 'manual_admin',
        ]);

        $fixtureTwo = PredictorRoundFixture::create([
            'round_id' => $round->id,
            'competition_name_snapshot' => 'MTN Super League',
            'home_team_name_snapshot' => 'Nkana',
            'away_team_name_snapshot' => 'Kabwe Warriors',
            'kickoff_at' => now()->subHours(3),
            'display_order' => 2,
            'result_status' => 'completed',
            'actual_home_score' => 0,
            'actual_away_score' => 0,
            'result_entered_at' => now()->subHours(1),
            'result_source' => 'manual_admin',
        ]);

        $entryOne = PredictorRoundEntry::create([
            'round_id' => $round->id,
            'campaign_id' => $campaign->id,
            'season_id' => $season->id,
            'user_id' => 'ts_123',
            'display_name_snapshot' => 'Predictor One',
            'entry_status' => 'submitted',
            'submitted_at' => now()->subDay()->addMinutes(10),
            'last_edited_at' => now()->subDay()->addMinutes(9),
            'banker_fixture_id' => $fixtureOne->id,
            'banker_multiplier' => 2,
        ]);

        PredictorPrediction::create([
            'round_entry_id' => $entryOne->id,
            'round_fixture_id' => $fixtureOne->id,
            'predicted_home_score' => 2,
            'predicted_away_score' => 1,
            'predicted_outcome' => 'home_win',
            'is_banker' => true,
            'was_submitted' => true,
        ]);

        PredictorPrediction::create([
            'round_entry_id' => $entryOne->id,
            'round_fixture_id' => $fixtureTwo->id,
            'predicted_home_score' => 1,
            'predicted_away_score' => 1,
            'predicted_outcome' => 'draw',
            'is_banker' => false,
            'was_submitted' => true,
        ]);

        $entryTwo = PredictorRoundEntry::create([
            'round_id' => $round->id,
            'campaign_id' => $campaign->id,
            'season_id' => $season->id,
            'user_id' => 'ts_456',
            'display_name_snapshot' => 'Predictor Two',
            'entry_status' => 'submitted',
            'submitted_at' => now()->subDay()->addMinutes(20),
            'last_edited_at' => now()->subDay()->addMinutes(19),
            'banker_fixture_id' => null,
            'banker_multiplier' => 2,
        ]);

        PredictorPrediction::create([
            'round_entry_id' => $entryTwo->id,
            'round_fixture_id' => $fixtureOne->id,
            'predicted_home_score' => 1,
            'predicted_away_score' => 0,
            'predicted_outcome' => 'home_win',
            'is_banker' => false,
            'was_submitted' => true,
        ]);

        PredictorPrediction::create([
            'round_entry_id' => $entryTwo->id,
            'round_fixture_id' => $fixtureTwo->id,
            'predicted_home_score' => 0,
            'predicted_away_score' => 0,
            'predicted_outcome' => 'draw',
            'is_banker' => false,
            'was_submitted' => true,
        ]);

        $this->post('/admin/predictor/rounds/'.$round->id.'/score')
            ->assertRedirect(route('admin.predictor.rounds.edit', $round));

        $round->refresh();
        $entryOne->refresh();
        $entryTwo->refresh();
        $bankerPrediction = PredictorPrediction::query()->where('round_entry_id', $entryOne->id)->where('round_fixture_id', $fixtureOne->id)->firstOrFail();

        $this->assertSame('completed', $round->status);
        $this->assertSame('scored', $entryOne->entry_status);
        $this->assertSame('scored', $entryTwo->entry_status);
        $this->assertEquals(20.5, (float) $entryOne->total_points);
        $this->assertEquals(12.5, (float) $entryTwo->total_points);
        $this->assertEquals(8.0, (float) $bankerPrediction->banker_bonus_points);
        $this->assertEquals(16.0, (float) $bankerPrediction->points_awarded);

        $this->assertDatabaseHas('predictor_leaderboard_entries', [
            'leaderboard_type' => 'round',
            'campaign_id' => $campaign->id,
            'round_id' => $round->id,
            'user_id' => 'ts_123',
            'rank' => 1,
        ]);

        $this->assertSame(2, PredictorLeaderboardEntry::query()->where('leaderboard_type', 'season')->where('season_id', $season->id)->count());
        $this->assertSame(2, PredictorLeaderboardEntry::query()->where('leaderboard_type', 'monthly')->count());

        $this->withHeaders($this->authHeaders(userId: 'ts_123'))
            ->getJson('/api/v1/predictor/campaigns/'.$campaign->slug.'/leaderboards/round?limit=5')
            ->assertOk()
            ->assertJsonPath('entries.0.user.user_id', 'ts_123')
            ->assertJsonPath('entries.0.points_total', 20.5)
            ->assertJsonPath('entries.1.user.user_id', 'ts_456')
            ->assertJsonPath('current_user.rank', 1)
            ->assertJsonPath('current_user.points_total', 20.5);
    }
}
