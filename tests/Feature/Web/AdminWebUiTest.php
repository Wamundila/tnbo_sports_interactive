<?php

namespace Tests\Feature\Web;

use App\Models\Admin;
use App\Models\PredictorCampaign;
use App\Models\PredictorRound;
use App\Models\PredictorSeason;
use App\Models\TriviaQuiz;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Tests\Concerns\GeneratesJwtTokens;
use Tests\TestCase;

class AdminWebUiTest extends TestCase
{
    use GeneratesJwtTokens;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configureJwtTestEnvironment();
    }

    public function test_admin_login_page_is_available_to_guests(): void
    {
        $this->get('/admin/login')
            ->assertOk()
            ->assertSee('Admin Login');
    }

    public function test_admin_dashboard_redirects_guests_to_login(): void
    {
        $this->get('/admin')
            ->assertRedirect(route('admin.login'));
    }

    public function test_admin_can_authenticate_through_web_form(): void
    {
        $admin = Admin::create([
            'name' => 'Trivia Admin',
            'email' => 'admin@example.com',
            'password' => 'secret-pass',
            'role' => 'interactive_admin',
            'status' => 'active',
        ]);

        $this->post('/admin/login', [
            'email' => $admin->email,
            'password' => 'secret-pass',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticated('admin');

        $this->get('/admin')
            ->assertOk()
            ->assertSee('Dashboard')
            ->assertSee('Recent quizzes')
            ->assertSee('Daily Quiz')
            ->assertSee('Predictor League');
    }

    public function test_authenticated_admin_can_open_quiz_report_help_and_predictor_pages(): void
    {
        $admin = Admin::create([
            'name' => 'Trivia Admin',
            'email' => 'admin@example.com',
            'password' => 'secret-pass',
            'role' => 'interactive_admin',
            'status' => 'active',
        ]);

        $this->actingAs($admin, 'admin');

        $this->get('/admin/quizzes/create')
            ->assertOk()
            ->assertSee('Create Quiz')
            ->assertSee('Trivia banner image')
            ->assertSee('multipart/form-data')
            ->assertSee('state: not_open');

        $this->get('/admin/reports')
            ->assertOk()
            ->assertSee('Reports');

        $this->get('/admin/help/how-to')
            ->assertOk()
            ->assertSee('How To Get Trivia Live')
            ->assertSee('scheduled')
            ->assertSee('Publish');

        $this->get('/admin/predictor')
            ->assertOk()
            ->assertSee('Predictor Campaigns')
            ->assertSee('/api/v1/predictor/summary');

        $this->get('/admin/predictor/campaigns/create')
            ->assertOk()
            ->assertSee('Campaign banner image')
            ->assertSee('multipart/form-data');
    }

    public function test_admin_can_create_predictor_campaign_season_and_round_that_surfaces_as_available(): void
    {
        $admin = Admin::create([
            'name' => 'Predictor Admin',
            'email' => 'predictor-admin@example.com',
            'password' => 'secret-pass',
            'role' => 'interactive_admin',
            'status' => 'active',
        ]);

        $this->actingAs($admin, 'admin');

        $this->post('/admin/predictor/campaigns', [
            'name' => 'Super League Predictor',
            'slug' => 'super_league_predictor',
            'display_name' => 'MTN Super League Predictor',
            'sponsor_name' => 'MTN',
            'description' => 'Weekly football predictor.',
            'scope_type' => 'single_competition',
            'default_fixture_count' => 4,
            'banker_enabled' => '1',
            'status' => 'active',
            'visibility' => 'public',
            'starts_at' => now()->subDay()->format('Y-m-d\TH:i'),
            'ends_at' => now()->addMonths(2)->format('Y-m-d\TH:i'),
        ])->assertRedirect();

        $campaign = PredictorCampaign::query()->where('slug', 'super_league_predictor')->firstOrFail();

        $this->post('/admin/predictor/campaigns/'.$campaign->slug.'/seasons', [
            'name' => '2026 Season',
            'slug' => '2026-season',
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
            'status' => 'active',
            'is_current' => '1',
            'rules_text' => 'Exact scores matter.',
            'scoring_outcome_points' => 3,
            'scoring_exact_score_points' => 5,
            'scoring_close_score_points' => 1.5,
            'scoring_banker_multiplier' => 2,
        ])->assertRedirect();

        $season = PredictorSeason::query()->where('campaign_id', $campaign->id)->where('slug', '2026-season')->firstOrFail();

        $this->post('/admin/predictor/seasons/'.$season->id.'/rounds', [
            'name' => 'Round 8',
            'round_number' => 8,
            'opens_at' => now()->subHours(2)->format('Y-m-d\TH:i'),
            'prediction_closes_at' => now()->addHours(6)->format('Y-m-d\TH:i'),
            'round_closes_at' => now()->addDay()->format('Y-m-d\TH:i'),
            'status' => 'open',
            'fixtures' => [
                [
                    'display_order' => 1,
                    'competition_name_snapshot' => 'MTN Super League',
                    'home_team_name_snapshot' => 'Power Dynamos',
                    'away_team_name_snapshot' => 'Zesco United',
                    'kickoff_at' => now()->addHours(1)->format('Y-m-d\TH:i'),
                    'result_status' => 'pending',
                ],
                [
                    'display_order' => 2,
                    'competition_name_snapshot' => 'MTN Super League',
                    'home_team_name_snapshot' => 'Nkana',
                    'away_team_name_snapshot' => 'Kabwe Warriors',
                    'kickoff_at' => now()->addHours(2)->format('Y-m-d\TH:i'),
                    'result_status' => 'pending',
                ],
                [
                    'display_order' => 3,
                    'competition_name_snapshot' => 'MTN Super League',
                    'home_team_name_snapshot' => 'Forest Rangers',
                    'away_team_name_snapshot' => 'Green Eagles',
                    'kickoff_at' => now()->addHours(3)->format('Y-m-d\TH:i'),
                    'result_status' => 'pending',
                ],
                [
                    'display_order' => 4,
                    'competition_name_snapshot' => 'MTN Super League',
                    'home_team_name_snapshot' => 'Nchanga Rangers',
                    'away_team_name_snapshot' => 'Red Arrows',
                    'kickoff_at' => now()->addHours(4)->format('Y-m-d\TH:i'),
                    'result_status' => 'pending',
                ],
            ],
        ])->assertRedirect();

        $round = PredictorRound::query()->where('season_id', $season->id)->where('name', 'Round 8')->firstOrFail();

        $this->assertSame('open', $round->status);
        $this->assertSame(4, $round->fixture_count);
        $this->assertTrue($season->fresh()->is_current);

        $this->fakeAuthBoxProfile();

        $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/predictor/summary?campaign_slug='.$campaign->slug)
            ->assertOk()
            ->assertJsonPath('campaign.slug', $campaign->slug)
            ->assertJsonPath('predictor_surface.state', 'available')
            ->assertJsonPath('predictor_surface.current_round.id', $round->id);
    }

    public function test_admin_banner_uploads_are_stored_on_public_storage_disk(): void
    {
        Storage::fake('public');

        $admin = Admin::create([
            'name' => 'Media Admin',
            'email' => 'media-admin@example.com',
            'password' => 'secret-pass',
            'role' => 'interactive_admin',
            'status' => 'active',
        ]);

        $this->actingAs($admin, 'admin');

        $this->post('/admin/quizzes', [
            'quiz_date' => now()->addDay()->toDateString(),
            'title' => 'Banner Quiz',
            'short_description' => 'Quiz with a storage-backed banner.',
            'status' => 'draft',
            'trivia_banner_upload' => $this->fakePngUpload('trivia-banner.png'),
        ])->assertRedirect();

        $quiz = TriviaQuiz::query()->where('title', 'Banner Quiz')->firstOrFail();

        $this->assertStringStartsWith('/storage/uploads/trivia/banners/', $quiz->trivia_banner_url);
        Storage::disk('public')->assertExists(str_replace('/storage/', '', $quiz->trivia_banner_url));

        $this->post('/admin/predictor/campaigns', [
            'name' => 'Storage Predictor',
            'slug' => 'storage_predictor',
            'display_name' => 'Storage Predictor',
            'description' => 'Predictor with a storage-backed banner.',
            'scope_type' => 'single_competition',
            'default_fixture_count' => 4,
            'banker_enabled' => '1',
            'status' => 'draft',
            'visibility' => 'public',
            'banner_image_upload' => $this->fakePngUpload('predictor-banner.png'),
        ])->assertRedirect();

        $campaign = PredictorCampaign::query()->where('slug', 'storage_predictor')->firstOrFail();

        $this->assertStringStartsWith('/storage/uploads/predictor/campaign-banners/', $campaign->banner_image_url);
        Storage::disk('public')->assertExists(str_replace('/storage/', '', $campaign->banner_image_url));
    }

    private function fakeAuthBoxProfile(): void
    {
        Http::fake([
            'https://authbox.test/api/v1/me' => Http::response([
                'user_id' => 'ts_123',
                'display_name' => 'Predictor User',
                'avatar_url' => null,
                'email_verified_at' => now()->toIso8601String(),
                'verified' => true,
            ]),
        ]);
    }

    private function fakePngUpload(string $name): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/p9sAAAAASUVORK5CYII='
        ));
    }
}
