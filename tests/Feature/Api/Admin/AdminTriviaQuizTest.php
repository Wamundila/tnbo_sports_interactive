<?php

namespace Tests\Feature\Api\Admin;

use App\Models\Admin;
use App\Models\TriviaQuiz;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTriviaQuizTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_publish_and_duplicate_a_valid_quiz(): void
    {
        $headers = $this->adminHeaders();

        $create = $this->withHeaders($headers)
            ->postJson('/api/admin/trivia/quizzes', $this->validQuizPayload(now()->toDateString()));

        $create->assertCreated()
            ->assertJsonPath('quiz.title', "Today's TNBO Sports Trivia")
            ->assertJsonCount(3, 'quiz.questions');

        $quizId = $create->json('quiz.id');

        $this->withHeaders($headers)
            ->postJson('/api/admin/trivia/quizzes/'.$quizId.'/publish')
            ->assertOk()
            ->assertJsonPath('quiz.status', 'published');

        $this->assertDatabaseHas('trivia_quizzes', [
            'id' => $quizId,
            'status' => 'published',
        ]);

        $this->withHeaders($headers)
            ->postJson('/api/admin/trivia/quizzes/'.$quizId.'/duplicate', [
                'quiz_date' => now()->addDay()->toDateString(),
                'title' => 'Tomorrow Trivia',
            ])
            ->assertCreated()
            ->assertJsonPath('quiz.title', 'Tomorrow Trivia')
            ->assertJsonPath('quiz.status', 'draft')
            ->assertJsonCount(3, 'quiz.questions');

        $this->assertDatabaseHas('trivia_activity_logs', [
            'event_name' => 'quiz_published',
            'reference_id' => $quizId,
        ]);
    }

    public function test_admin_cannot_publish_an_invalid_quiz(): void
    {
        $headers = $this->adminHeaders();

        $quiz = TriviaQuiz::create([
            'quiz_date' => now()->toDateString(),
            'title' => 'Broken Quiz',
            'status' => 'draft',
            'opens_at' => now()->subHour(),
            'closes_at' => now()->addHour(),
            'question_count_expected' => 3,
            'time_per_question_seconds' => 30,
            'points_per_correct' => 3,
            'streak_bonus_enabled' => true,
        ]);

        $this->withHeaders($headers)
            ->postJson('/api/admin/trivia/quizzes/'.$quiz->id.'/publish')
            ->assertStatus(422)
            ->assertJson([
                'code' => 'TRIVIA_QUIZ_INVALID',
            ]);
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

    private function validQuizPayload(string $quizDate): array
    {
        return [
            'quiz_date' => $quizDate,
            'title' => "Today's TNBO Sports Trivia",
            'short_description' => '3 questions - 90 seconds total potential - 9 base points',
            'status' => 'draft',
            'opens_at' => now()->subHour()->toIso8601String(),
            'closes_at' => now()->addHour()->toIso8601String(),
            'question_count_expected' => 3,
            'time_per_question_seconds' => 30,
            'points_per_correct' => 3,
            'streak_bonus_enabled' => true,
            'questions' => collect([1, 2, 3])->map(fn ($position) => [
                'position' => $position,
                'question_text' => 'Question '.$position,
                'explanation_text' => 'Explanation '.$position,
                'status' => 'active',
                'options' => collect([1, 2, 3])->map(fn ($optionPosition) => [
                    'position' => $optionPosition,
                    'option_text' => 'Option '.$position.'.'.$optionPosition,
                    'is_correct' => $optionPosition === 1,
                ])->all(),
            ])->all(),
        ];
    }
}
