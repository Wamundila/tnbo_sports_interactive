<?php

namespace Tests\Feature\Console;

use App\Models\TriviaAttempt;
use App\Models\TriviaQuestion;
use App\Models\TriviaQuestionOption;
use App\Models\TriviaQuiz;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class TriviaOperationsCommandsTest extends TestCase
{
    use RefreshDatabase;

    public function test_operations_commands_publish_close_expire_and_refresh_leaderboards(): void
    {
        $publishQuiz = TriviaQuiz::create([
            'quiz_date' => now()->addDays(2)->toDateString(),
            'title' => 'Scheduled Quiz',
            'status' => 'scheduled',
            'opens_at' => now()->subMinute(),
            'closes_at' => now()->addHour(),
            'question_count_expected' => 3,
            'time_per_question_seconds' => 30,
            'points_per_correct' => 3,
            'streak_bonus_enabled' => true,
        ]);

        $closeQuiz = TriviaQuiz::create([
            'quiz_date' => now()->subDay()->toDateString(),
            'title' => 'Old Quiz',
            'status' => 'published',
            'opens_at' => now()->subDay()->subHour(),
            'closes_at' => now()->subMinute(),
            'question_count_expected' => 3,
            'time_per_question_seconds' => 30,
            'points_per_correct' => 3,
            'streak_bonus_enabled' => true,
        ]);

        $leaderboardQuiz = TriviaQuiz::create([
            'quiz_date' => now()->toDateString(),
            'title' => 'Leaderboard Quiz',
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
                'trivia_quiz_id' => $leaderboardQuiz->id,
                'position' => $position,
                'question_text' => 'Question '.$position,
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

        $attempt = TriviaAttempt::create([
            'trivia_quiz_id' => $leaderboardQuiz->id,
            'user_id' => 'ts_55',
            'display_name_snapshot' => 'Ranked User',
            'started_at' => now()->subMinutes(5),
            'expires_at' => now()->subMinute(),
            'submitted_at' => now()->subMinutes(4),
            'status' => 'submitted',
            'score_total' => 9,
            'correct_answers_count' => 3,
            'wrong_answers_count' => 0,
            'time_taken_seconds' => 10,
        ]);

        $staleAttempt = TriviaAttempt::create([
            'trivia_quiz_id' => $leaderboardQuiz->id,
            'user_id' => 'ts_66',
            'started_at' => now()->subMinutes(10),
            'expires_at' => now()->subMinute(),
            'status' => 'in_progress',
        ]);

        Artisan::call('trivia:quizzes:auto-publish');
        Artisan::call('trivia:quizzes:auto-close');
        Artisan::call('trivia:attempts:expire');
        Artisan::call('trivia:leaderboards:refresh');

        $this->assertDatabaseHas('trivia_quizzes', [
            'id' => $publishQuiz->id,
            'status' => 'published',
        ]);

        $this->assertDatabaseHas('trivia_quizzes', [
            'id' => $closeQuiz->id,
            'status' => 'closed',
        ]);

        $this->assertDatabaseHas('trivia_attempts', [
            'id' => $staleAttempt->id,
            'status' => 'expired',
        ]);

        $this->assertDatabaseHas('leaderboard_entries', [
            'board_type' => 'daily',
            'period_key' => now()->toDateString(),
            'user_id' => 'ts_55',
            'rank_position' => 1,
        ]);

        $this->assertDatabaseHas('trivia_activity_logs', [
            'event_name' => 'quiz_auto_published',
            'reference_id' => $publishQuiz->id,
        ]);

        $this->assertDatabaseHas('trivia_activity_logs', [
            'event_name' => 'quiz_auto_closed',
            'reference_id' => $closeQuiz->id,
        ]);

        $this->assertDatabaseHas('trivia_activity_logs', [
            'event_name' => 'attempt_expired',
            'reference_id' => $staleAttempt->id,
        ]);
    }
}




