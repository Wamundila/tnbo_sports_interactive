<?php

namespace Tests\Feature\Web;

use App\Models\Admin;
use App\Models\Poll;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPollOptionsWebUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_poll_with_two_filled_options_and_blank_extra_rows(): void
    {
        $admin = Admin::create([
            'name' => 'Poll Admin',
            'email' => 'poll-options-admin@example.com',
            'password' => 'secret-pass',
            'role' => 'interactive_admin',
            'status' => 'active',
        ]);

        $this->actingAs($admin, 'admin');

        $this->post('/admin/polls', [
            'title' => 'Weekly Fan Vote',
            'question' => 'Who should win this week?',
            'slug' => 'weekly-fan-vote',
            'visibility' => 'public',
            'result_visibility_mode' => 'hidden_until_end',
            'options' => [
                [
                    'title' => 'Option A',
                    'status' => 'active',
                ],
                [
                    'title' => 'Option B',
                    'status' => 'active',
                ],
                [
                    'title' => '',
                    'status' => 'active',
                ],
                [
                    'title' => '',
                    'status' => 'active',
                ],
            ],
        ])->assertRedirect();

        $poll = Poll::query()->where('slug', 'weekly-fan-vote')->firstOrFail();

        $this->assertSame(2, $poll->options()->count());
        $this->assertSame(['Option A', 'Option B'], $poll->options()->orderBy('display_order')->pluck('title')->all());
    }
}
