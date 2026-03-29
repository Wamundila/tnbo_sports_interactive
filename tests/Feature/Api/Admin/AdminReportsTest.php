<?php

namespace Tests\Feature\Api\Admin;

use App\Models\Admin;
use App\Models\LeaderboardEntry;
use App\Models\TriviaActivityLog;
use App\Models\TriviaAttempt;
use App\Models\TriviaQuiz;
use App\Models\UserTriviaProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminReportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_overview_attempts_leaderboards_and_activity(): void
    {
        $headers = $this->adminHeaders();
        $quiz = TriviaQuiz::create([
            'quiz_date' => now()->toDateString(),
            'title' => 'Report Quiz',
            'status' => 'published',
            'opens_at' => now()->subHour(),
            'closes_at' => now()->addHour(),
            'question_count_expected' => 3,
            'time_per_question_seconds' => 30,
            'points_per_correct' => 3,
            'streak_bonus_enabled' => true,
        ]);

        TriviaAttempt::create([
            'trivia_quiz_id' => $quiz->id,
            'user_id' => 'ts_11',
            'display_name_snapshot' => 'John D.',
            'started_at' => now()->subMinutes(5),
            'expires_at' => now()->addMinutes(5),
            'submitted_at' => now()->subMinutes(4),
            'status' => 'submitted',
            'score_total' => 9,
            'correct_answers_count' => 3,
            'time_taken_seconds' => 12,
            'streak_after' => 4,
            'client_type' => 'flutter_android',
        ]);

        UserTriviaProfile::create([
            'user_id' => 'ts_11',
            'display_name_snapshot' => 'John D.',
            'current_streak' => 4,
            'best_streak' => 4,
            'total_points' => 30,
            'total_correct_answers' => 10,
            'total_wrong_answers' => 2,
            'total_quizzes_played' => 4,
            'total_quizzes_completed' => 4,
            'lifetime_accuracy' => 83.33,
            'last_played_quiz_date' => now()->toDateString(),
        ]);

        LeaderboardEntry::create([
            'board_type' => 'daily',
            'period_key' => now()->toDateString(),
            'user_id' => 'ts_11',
            'display_name_snapshot' => 'John D.',
            'avatar_url_snapshot' => null,
            'points' => 9,
            'quizzes_played' => 1,
            'correct_answers' => 3,
            'accuracy' => 100,
            'avg_score' => 9,
            'rank_position' => 1,
        ]);

        TriviaActivityLog::create([
            'actor_type' => 'admin',
            'actor_id' => '1',
            'event_name' => 'quiz_published',
            'reference_type' => TriviaQuiz::class,
            'reference_id' => $quiz->id,
            'metadata' => ['quiz_date' => now()->toDateString()],
            'created_at' => now(),
        ]);

        $this->withHeaders($headers)
            ->getJson('/api/admin/overview')
            ->assertOk()
            ->assertJsonPath('today_quiz.id', $quiz->id)
            ->assertJsonPath('attempts_today', 1)
            ->assertJsonPath('top_five_today.0.user.user_id', 'ts_11');

        $this->withHeaders($headers)
            ->getJson('/api/admin/trivia/attempts?quiz_date='.now()->toDateString())
            ->assertOk()
            ->assertJsonPath('items.0.user_id', 'ts_11')
            ->assertJsonPath('items.0.score_total', 9);

        $this->withHeaders($headers)
            ->getJson('/api/admin/trivia/leaderboards?board_type=daily&period_key='.now()->toDateString())
            ->assertOk()
            ->assertJsonPath('entries.0.user.display_name', 'John D.');

        $this->withHeaders($headers)
            ->getJson('/api/admin/trivia/activity')
            ->assertOk()
            ->assertJsonPath('items.0.event_name', 'quiz_published');
    }

    private function adminHeaders(): array
    {
        Admin::create([
            'name' => 'Trivia Admin',
            'email' => 'admin@example.com',
            'password' => 'secret-pass',
            'role' => 'interactive_admin',
            'status' => 'active',
        ]);

        $login = $this->postJson('/api/admin/auth/login', [
            'email' => 'admin@example.com',
            'password' => 'secret-pass',
        ])->assertOk();

        return [
            'Authorization' => 'Bearer '.$login->json('token'),
            'Accept' => 'application/json',
        ];
    }
}
