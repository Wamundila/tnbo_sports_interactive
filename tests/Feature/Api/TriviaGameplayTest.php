<?php

namespace Tests\Feature\Api;

use App\Models\LeaderboardEntry;
use App\Models\TriviaAttempt;
use App\Models\TriviaQuestion;
use App\Models\TriviaQuestionOption;
use App\Models\TriviaQuiz;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\GeneratesJwtTokens;
use Tests\TestCase;

class TriviaGameplayTest extends TestCase
{
    use GeneratesJwtTokens;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configureJwtTestEnvironment();
    }

    public function test_unverified_user_is_blocked_from_starting_trivia(): void
    {
        $this->createPublishedQuiz();
        $this->fakeAuthBoxProfile([
            'email_verified_at' => null,
            'verified' => false,
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/trivia/today/start', ['client' => 'flutter_android']);

        $response->assertForbidden()
            ->assertJson([
                'code' => 'TRIVIA_VERIFICATION_REQUIRED',
            ]);
    }

    public function test_verified_user_can_start_and_persist_ts_user_id(): void
    {
        $quiz = $this->createPublishedQuiz();
        $this->fakeAuthBoxProfile();

        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/trivia/today/start', ['client' => 'flutter_android']);

        $response->assertOk()
            ->assertJsonPath('status', 'in_progress')
            ->assertJsonPath('quiz.id', $quiz->id)
            ->assertJsonPath('quiz.trivia_banner_url', '/uploads/trivia/banners/daily-test.jpg')
            ->assertJsonPath('questions.0.position', 1);

        $this->assertDatabaseHas('trivia_attempts', [
            'trivia_quiz_id' => $quiz->id,
            'user_id' => 'ts_123',
            'status' => 'in_progress',
        ]);
    }

    public function test_today_endpoint_returns_in_progress_state_for_active_attempt(): void
    {
        $quiz = $this->createPublishedQuiz();

        $attempt = TriviaAttempt::create([
            'trivia_quiz_id' => $quiz->id,
            'user_id' => 'ts_123',
            'started_at' => now()->subSeconds(10),
            'expires_at' => now()->addSeconds(80),
            'status' => 'in_progress',
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/trivia/today');

        $response->assertOk()
            ->assertJsonPath('available', true)
            ->assertJsonPath('state', 'in_progress')
            ->assertJsonPath('quiz.trivia_banner_url', '/uploads/trivia/banners/daily-test.jpg')
            ->assertJsonPath('current_attempt.attempt_id', $attempt->id);
    }

    public function test_today_endpoint_can_signal_verification_required(): void
    {
        $this->createPublishedQuiz();
        $this->fakeAuthBoxProfile([
            'email_verified_at' => null,
            'verified' => false,
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/trivia/today');

        $response->assertOk()
            ->assertJsonPath('available', true)
            ->assertJsonPath('state', 'verification_required');
    }

    public function test_summary_endpoint_returns_in_progress_block_and_empty_previews(): void
    {
        $quiz = $this->createPublishedQuiz();
        $attempt = TriviaAttempt::create([
            'trivia_quiz_id' => $quiz->id,
            'user_id' => 'ts_123',
            'started_at' => now()->subSeconds(10),
            'expires_at' => now()->addSeconds(80),
            'status' => 'in_progress',
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/trivia/summary');

        $response->assertOk()
            ->assertJsonPath('date', now()->toDateString())
            ->assertJsonPath('daily_trivia.state', 'in_progress')
            ->assertJsonPath('daily_trivia.trivia_banner_url', '/uploads/trivia/banners/daily-test.jpg')
            ->assertJsonPath('daily_trivia.available', true)
            ->assertJsonPath('daily_trivia.current_attempt.attempt_id', $attempt->id)
            ->assertJsonPath('daily_trivia.today_score_total', null)
            ->assertJsonPath('daily_trivia.rank.daily', null)
            ->assertJsonPath('user_summary.user_id', 'ts_123')
            ->assertJsonPath('user_summary.rank.daily', null)
            ->assertJsonPath('user_summary.total_points', 0)
            ->assertJsonPath('user_summary.today_status.played', false)
            ->assertJsonPath('leaderboard_previews.daily.board_type', 'daily')
            ->assertJsonPath('leaderboard_previews.daily.period_key', now()->toDateString())
            ->assertJsonCount(0, 'leaderboard_previews.daily.entries')
            ->assertJsonPath('leaderboard_previews.all_time.period_key', 'all');
    }

    public function test_expired_attempt_cannot_be_submitted(): void
    {
        $quiz = $this->createPublishedQuiz();
        $attempt = TriviaAttempt::create([
            'trivia_quiz_id' => $quiz->id,
            'user_id' => 'ts_123',
            'started_at' => now()->subMinutes(10),
            'expires_at' => now()->subSecond(),
            'status' => 'in_progress',
        ]);

        $this->fakeAuthBoxProfile();

        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/trivia/attempts/'.$attempt->id.'/submit', [
                'answers' => [
                    ['question_id' => $quiz->questions[0]->id, 'option_id' => $quiz->questions[0]->options[0]->id, 'response_time_ms' => 1000],
                ],
            ]);

        $response->assertConflict()
            ->assertJson([
                'code' => 'TRIVIA_ATTEMPT_EXPIRED',
            ]);
    }

    public function test_submit_updates_summary_history_and_review_with_display_snapshot(): void
    {
        $quiz = $this->createPublishedQuiz();
        $this->fakeAuthBoxProfile([
            'display_name' => 'John D.',
            'avatar_url' => 'https://cdn.test/avatar.png',
        ]);

        $submitResponse = $this->submitPerfectAttempt($quiz);

        $submitResponse->assertOk()
            ->assertJsonPath('result.score_total', 9)
            ->assertJsonPath('result.leaderboard_impact.daily_rank', 1)
            ->assertJsonPath('result.rank.daily', 1)
            ->assertJsonPath('answer_review.0.question_text', 'Question 1')
            ->assertJsonPath('answer_review.0.selected_option_text', 'Option 1.1')
            ->assertJsonPath('answer_review.0.correct_option_text', 'Option 1.1')
            ->assertJsonPath('answer_review.1.question_text', 'Question 2')
            ->assertJsonPath('answer_review.1.correct_option_text', 'Option 2.1');

        $summaryResponse = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/trivia/me/summary');

        $summaryResponse->assertOk()
            ->assertJson([
                'user_id' => 'ts_123',
                'total_points' => 9,
            ])
            ->assertJsonPath('rank.daily', 1);

        $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/trivia/leaderboards?board_type=daily&period_key='.now()->toDateString())
            ->assertOk()
            ->assertJsonPath('entries.0.user.user_id', 'ts_123')
            ->assertJsonPath('entries.0.user.display_name', 'John D.')
            ->assertJsonPath('entries.0.user.avatar_url', 'https://cdn.test/avatar.png');

        $historyResponse = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/trivia/me/history');

        $historyResponse->assertOk()
            ->assertJsonPath('items.0.quiz_title', "Today's TNBO Sports Trivia")
            ->assertJsonPath('items.0.question_count', 3)
            ->assertJsonPath('items.0.score_total', 9)
            ->assertJsonPath('items.0.correct_answers_count', 3)
            ->assertJsonPath('items.0.streak_after', 1);

        $this->assertNotNull($historyResponse->json('items.0.completed_at'));
    }

    public function test_summary_endpoint_returns_composed_payload_after_submission(): void
    {
        $quiz = $this->createPublishedQuiz();
        $this->fakeAuthBoxProfile([
            'display_name' => 'John D.',
            'avatar_url' => 'https://cdn.test/avatar.png',
        ]);

        $this->submitPerfectAttempt($quiz);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/trivia/summary');

        $response->assertOk()
            ->assertJsonPath('date', now()->toDateString())
            ->assertJsonPath('daily_trivia.title', "Today's TNBO Sports Trivia")
            ->assertJsonPath('daily_trivia.short_description', $quiz->short_description)
            ->assertJsonPath('daily_trivia.trivia_banner_url', '/uploads/trivia/banners/daily-test.jpg')
            ->assertJsonPath('daily_trivia.state', 'already_played')
            ->assertJsonPath('daily_trivia.available', true)
            ->assertJsonPath('daily_trivia.current_attempt', null)
            ->assertJsonPath('daily_trivia.today_score_total', 9)
            ->assertJsonPath('daily_trivia.rank.daily', 1)
            ->assertJsonPath('daily_trivia.points.today', 9)
            ->assertJsonPath('daily_trivia.points.total', 9)
            ->assertJsonPath('daily_trivia.streak.current', 1)
            ->assertJsonPath('daily_trivia.streak.best', 1)
            ->assertJsonPath('user_summary.user_id', 'ts_123')
            ->assertJsonPath('user_summary.rank.daily', 1)
            ->assertJsonPath('user_summary.total_points', 9)
            ->assertJsonPath('user_summary.today_status.played', true)
            ->assertJsonPath('user_summary.today_status.score_total', 9)
            ->assertJsonPath('leaderboard_previews.daily.board_type', 'daily')
            ->assertJsonPath('leaderboard_previews.daily.period_key', now()->toDateString())
            ->assertJsonPath('leaderboard_previews.daily.entries.0.rank', 1)
            ->assertJsonPath('leaderboard_previews.daily.entries.0.user.user_id', 'ts_123')
            ->assertJsonPath('leaderboard_previews.daily.entries.0.user.display_name', 'John D.')
            ->assertJsonPath('leaderboard_previews.weekly.board_type', 'weekly')
            ->assertJsonPath('leaderboard_previews.monthly.board_type', 'monthly')
            ->assertJsonPath('leaderboard_previews.all_time.board_type', 'all_time');
    }

    public function test_leaderboard_supports_limit_parameter(): void
    {
        LeaderboardEntry::create([
            'board_type' => 'daily',
            'period_key' => now()->toDateString(),
            'user_id' => 'ts_123',
            'display_name_snapshot' => 'User One',
            'avatar_url_snapshot' => null,
            'points' => 9,
            'quizzes_played' => 1,
            'correct_answers' => 3,
            'accuracy' => 100,
            'avg_score' => 9,
            'rank_position' => 1,
        ]);

        LeaderboardEntry::create([
            'board_type' => 'daily',
            'period_key' => now()->toDateString(),
            'user_id' => 'ts_456',
            'display_name_snapshot' => 'User Two',
            'avatar_url_snapshot' => null,
            'points' => 6,
            'quizzes_played' => 1,
            'correct_answers' => 2,
            'accuracy' => 66.67,
            'avg_score' => 6,
            'rank_position' => 2,
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/trivia/leaderboards?board_type=daily&period_key='.now()->toDateString().'&limit=1');

        $response->assertOk()
            ->assertJsonPath('limit', 1)
            ->assertJsonCount(1, 'entries')
            ->assertJsonPath('entries.0.user.user_id', 'ts_123');
    }

    private function submitPerfectAttempt(TriviaQuiz $quiz): TestResponse
    {
        $startResponse = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/trivia/today/start', ['client' => 'flutter_android'])
            ->assertOk();

        $attemptId = $startResponse->json('attempt_id');
        $answers = $quiz->questions->map(fn ($question) => [
            'question_id' => $question->id,
            'option_id' => $question->options->firstWhere('is_correct', true)->id,
            'response_time_ms' => 1000,
        ])->all();

        return $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/trivia/attempts/'.$attemptId.'/submit', [
                'answers' => $answers,
            ]);
    }

    private function fakeAuthBoxProfile(array $overrides = []): void
    {
        Http::fake([
            'https://authbox.test/api/v1/me' => Http::response(array_merge([
                'user_id' => 'ts_123',
                'display_name' => 'Test User',
                'avatar_url' => null,
                'email_verified_at' => now()->toIso8601String(),
                'verified' => true,
            ], $overrides)),
        ]);
    }

    private function createPublishedQuiz(): TriviaQuiz
    {
        $quiz = TriviaQuiz::create([
            'quiz_date' => now()->toDateString(),
            'title' => "Today's TNBO Sports Trivia",
            'short_description' => '3 questions - 90 seconds total potential - 9 base points',
            'trivia_banner_url' => '/uploads/trivia/banners/daily-test.jpg',
            'status' => 'published',
            'opens_at' => now()->subHour(),
            'closes_at' => now()->addHour(),
            'question_count_expected' => 3,
            'time_per_question_seconds' => 30,
            'points_per_correct' => 3,
            'streak_bonus_enabled' => true,
        ]);

        foreach ([1, 2, 3] as $position) {
            $question = TriviaQuestion::create([
                'trivia_quiz_id' => $quiz->id,
                'position' => $position,
                'question_text' => 'Question '.$position,
                'explanation_text' => 'Explanation '.$position,
                'status' => 'active',
            ]);

            foreach ([1, 2, 3] as $optionPosition) {
                TriviaQuestionOption::create([
                    'trivia_question_id' => $question->id,
                    'position' => $optionPosition,
                    'option_text' => 'Option '.$position.'.'.$optionPosition,
                    'is_correct' => $optionPosition === 1,
                ]);
            }
        }

        return $quiz->load('questions.options');
    }
}
