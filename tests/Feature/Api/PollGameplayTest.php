<?php

namespace Tests\Feature\Api;

use App\Models\Poll;
use App\Models\PollOption;
use App\Models\PollVote;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\GeneratesJwtTokens;
use Tests\TestCase;

class PollGameplayTest extends TestCase
{
    use GeneratesJwtTokens;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configureJwtTestEnvironment();
    }

    public function test_guest_can_read_poll_summary_but_hidden_results_stay_hidden(): void
    {
        $poll = $this->createPoll([
            'slug' => 'player-of-the-month',
            'result_visibility_mode' => 'hidden_until_end',
        ]);

        $this->withHeaders($this->serviceHeaders())
            ->getJson('/api/v1/polls/summary?poll_slug='.$poll->slug)
            ->assertOk()
            ->assertJsonPath('poll.slug', $poll->slug)
            ->assertJsonPath('poll_surface.state', 'signed_out')
            ->assertJsonPath('poll_surface.auth_state', 'signed_out')
            ->assertJsonPath('poll_surface.available', false)
            ->assertJsonPath('poll_surface.can_vote', false)
            ->assertJsonPath('poll_surface.results_visible', false)
            ->assertJsonPath('results', null);

        $this->withHeaders($this->serviceHeaders())
            ->getJson('/api/v1/polls/'.$poll->slug.'/results')
            ->assertForbidden()
            ->assertJsonPath('code', 'POLL_RESULTS_HIDDEN');
    }

    public function test_verified_user_can_vote_and_receive_updated_poll_surface_with_canonical_option_shapes(): void
    {
        $poll = $this->createPoll([
            'slug' => 'goal-of-the-week',
            'result_visibility_mode' => 'live_percentages',
            'allow_result_view_before_vote' => false,
        ]);

        $this->fakeAuthBoxProfile();
        $option = $poll->options()->firstOrFail();

        $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/polls/'.$poll->slug.'/vote', [
                'option_id' => $option->id,
                'client' => 'flutter',
                'session_id' => 'session-1',
            ])
            ->assertOk()
            ->assertJsonPath('poll.slug', $poll->slug)
            ->assertJsonPath('poll_surface.state', 'already_voted')
            ->assertJsonPath('poll_surface.auth_state', 'verified')
            ->assertJsonPath('poll_surface.has_voted', true)
            ->assertJsonPath('poll_surface.my_vote_option_id', $option->id)
            ->assertJsonPath('poll_surface.results_visible', true)
            ->assertJsonPath('options.0.id', $option->id)
            ->assertJsonPath('options.0.is_selected', true)
            ->assertJsonPath('options.0.vote_count', 1)
            ->assertJsonPath('results.total_votes', 1)
            ->assertJsonPath('results.winner_option_id', $option->id)
            ->assertJsonPath('results.options.0.id', $option->id)
            ->assertJsonPath('results.options.0.is_selected', true)
            ->assertJsonPath('results.options.0.vote_count', 1)
            ->assertJsonPath('results.options.0.percentage', 100)
            ->assertJsonStructure(['submitted_at']);
    }

    public function test_results_endpoint_returns_rich_option_rows_when_results_are_visible(): void
    {
        $poll = $this->createPoll([
            'slug' => 'final-award-vote',
            'status' => 'closed',
            'close_at' => now()->subMinute(),
            'result_visibility_mode' => 'final_results',
        ]);

        PollVote::create([
            'poll_id' => $poll->id,
            'poll_option_id' => $poll->options[1]->id,
            'user_id' => 'ts_900',
            'submitted_at' => now()->subMinutes(2),
        ]);

        $this->withHeaders($this->serviceHeaders())
            ->getJson('/api/v1/polls/'.$poll->slug.'/results')
            ->assertOk()
            ->assertJsonPath('poll.slug', $poll->slug)
            ->assertJsonPath('poll_surface.results_visible', true)
            ->assertJsonPath('options.1.id', $poll->options[1]->id)
            ->assertJsonPath('options.1.vote_count', 1)
            ->assertJsonPath('results.options.1.id', $poll->options[1]->id)
            ->assertJsonPath('results.options.1.title', 'Option B')
            ->assertJsonPath('results.options.1.vote_count', 1)
            ->assertJsonPath('results.options.1.percentage', 100);
    }

    public function test_closed_final_results_poll_remains_results_visible_for_authenticated_user_who_already_voted(): void
    {
        $poll = $this->createPoll([
            'slug' => 'absa-cup-fpott',
            'status' => 'closed',
            'close_at' => now()->subMinute(),
            'result_visibility_mode' => 'final_results',
        ]);

        PollVote::create([
            'poll_id' => $poll->id,
            'poll_option_id' => $poll->options[1]->id,
            'user_id' => 'ts_4',
            'submitted_at' => now()->subMinutes(5),
        ]);

        $this->fakeAuthBoxProfile([
            'user_id' => 'ts_4',
        ]);

        $headers = $this->authHeaders(userId: 'ts_4');

        $this->withHeaders($headers)
            ->getJson('/api/v1/polls/'.$poll->slug)
            ->assertOk()
            ->assertJsonPath('poll_surface.state', 'results_only')
            ->assertJsonPath('poll_surface.has_voted', true)
            ->assertJsonPath('poll_surface.my_vote_option_id', $poll->options[1]->id)
            ->assertJsonPath('poll_surface.results_visible', true)
            ->assertJsonPath('results.total_votes', 1)
            ->assertJsonPath('results.winner_option_id', $poll->options[1]->id);

        $this->withHeaders($headers)
            ->getJson('/api/v1/polls/'.$poll->slug.'/results')
            ->assertOk()
            ->assertJsonPath('poll_surface.state', 'results_only')
            ->assertJsonPath('poll_surface.has_voted', true)
            ->assertJsonPath('poll_surface.my_vote_option_id', $poll->options[1]->id)
            ->assertJsonPath('poll_surface.results_visible', true)
            ->assertJsonPath('results.total_votes', 1)
            ->assertJsonPath('results.winner_option_id', $poll->options[1]->id);
    }

    public function test_verified_required_poll_blocks_unverified_user_and_summary_signals_it(): void
    {
        $poll = $this->createPoll([
            'slug' => 'fan-awards',
            'verified_account_required' => true,
            'result_visibility_mode' => 'live_percentages',
        ]);

        $this->fakeAuthBoxProfile([
            'email_verified_at' => null,
            'verified' => false,
        ]);

        $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/polls/summary?poll_slug='.$poll->slug)
            ->assertOk()
            ->assertJsonPath('poll_surface.state', 'verification_required')
            ->assertJsonPath('poll_surface.auth_state', 'unverified');

        $option = $poll->options()->firstOrFail();

        $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/polls/'.$poll->slug.'/vote', [
                'option_id' => $option->id,
            ])
            ->assertForbidden()
            ->assertJsonPath('code', 'POLL_VERIFICATION_REQUIRED');
    }

    private function serviceHeaders(): array
    {
        return [
            'X-TNBO-Service-Key' => 'test-service-key',
            'Accept' => 'application/json',
        ];
    }

    private function fakeAuthBoxProfile(array $overrides = []): void
    {
        Http::fake([
            'https://authbox.test/api/v1/me' => Http::response(array_merge([
                'user_id' => 'ts_123',
                'display_name' => 'Poll User',
                'avatar_url' => 'https://cdn.test/poll-user.png',
                'email_verified_at' => now()->toIso8601String(),
                'verified' => true,
            ], $overrides)),
        ]);
    }

    private function createPoll(array $overrides = []): Poll
    {
        $poll = Poll::create(array_merge([
            'type' => 'single_choice',
            'category' => 'fan_vote',
            'title' => 'TNBO Fan Poll',
            'question' => 'Who should win?',
            'slug' => 'tnbo-fan-poll',
            'description' => 'Cast your vote.',
            'short_description' => 'Cast your vote.',
            'status' => 'live',
            'visibility' => 'public',
            'open_at' => now()->subHour(),
            'close_at' => now()->addDay(),
            'login_required' => true,
            'verified_account_required' => false,
            'allow_result_view_before_vote' => false,
            'result_visibility_mode' => 'hidden_until_end',
        ], $overrides));

        foreach ([
            ['title' => 'Option A', 'display_order' => 1],
            ['title' => 'Option B', 'display_order' => 2],
            ['title' => 'Option C', 'display_order' => 3],
        ] as $option) {
            PollOption::create(array_merge($option, [
                'poll_id' => $poll->id,
                'status' => 'active',
            ]));
        }

        return $poll->fresh('options');
    }
}
