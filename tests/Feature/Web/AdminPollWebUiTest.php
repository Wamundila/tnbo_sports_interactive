<?php

namespace Tests\Feature\Web;

use App\Models\Admin;
use App\Models\Poll;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPollWebUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_admin_can_open_poll_pages_and_create_publish_a_poll(): void
    {
        $admin = Admin::create([
            'name' => 'Poll Admin',
            'email' => 'poll-admin@example.com',
            'password' => 'secret-pass',
            'role' => 'interactive_admin',
            'status' => 'active',
        ]);

        $this->actingAs($admin, 'admin');

        $this->get('/admin/polls')
            ->assertOk()
            ->assertSee('Single Choice Poll');

        $this->get('/admin/polls/create')
            ->assertOk()
            ->assertSee('Create Poll')
            ->assertSee('Publishing Rules');

        $this->post('/admin/polls', [
            'title' => 'Player of the Month',
            'question' => 'Who should win Player of the Month?',
            'slug' => 'player-of-the-month',
            'category' => 'player_of_the_month',
            'short_description' => 'Vote for the standout player.',
            'description' => 'Monthly award poll.',
            'visibility' => 'public',
            'open_at' => now()->subHour()->format('Y-m-d\TH:i'),
            'close_at' => now()->addDay()->format('Y-m-d\TH:i'),
            'login_required' => '1',
            'verified_account_required' => '1',
            'result_visibility_mode' => 'live_percentages',
            'options' => [
                [
                    'title' => 'Player A',
                    'subtitle' => 'Club A',
                    'status' => 'active',
                ],
                [
                    'title' => 'Player B',
                    'subtitle' => 'Club B',
                    'status' => 'active',
                ],
            ],
        ])->assertRedirect();

        $poll = Poll::query()->where('slug', 'player-of-the-month')->firstOrFail();

        $this->post('/admin/polls/'.$poll->slug.'/publish')
            ->assertRedirect('/admin/polls/'.$poll->slug.'/edit');

        $this->assertSame('live', $poll->fresh()->status);

        $this->get('/admin/polls/'.$poll->slug.'/edit')
            ->assertOk()
            ->assertSee('Player of the Month')
            ->assertSee('Publish')
            ->assertSee('Close');
    }
}
