<?php

namespace App\Services;

use App\Data\AuthBoxUserProfile;
use App\Exceptions\ApiException;
use App\Models\TriviaAttempt;
use App\Models\TriviaAttemptAnswer;
use App\Models\UserTriviaProfile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class TriviaAttemptService
{
    public function __construct(
        private readonly TriviaQuizResolver $quizResolver,
        private readonly TriviaScoringService $scoringService,
        private readonly TriviaStreakService $streakService,
        private readonly TriviaLeaderboardService $leaderboardService,
    ) {
    }

    public function startTodayAttempt(string $userId, ?AuthBoxUserProfile $profile, ?string $clientType): TriviaAttempt
    {
        $quiz = $this->quizResolver->resolveStartableTodayQuiz();

        return DB::transaction(function () use ($quiz, $userId, $profile, $clientType): TriviaAttempt {
            $attempt = TriviaAttempt::query()
                ->where('trivia_quiz_id', $quiz->id)
                ->where('user_id', $userId)
                ->lockForUpdate()
                ->first();

            if ($attempt) {
                if ($attempt->status === 'submitted') {
                    throw ApiException::conflict(
                        "You have already completed today's trivia.",
                        'TRIVIA_ALREADY_PLAYED'
                    );
                }

                if ($attempt->status === 'in_progress' && ! $attempt->isExpired()) {
                    return $attempt->loadMissing('quiz.questions.options');
                }

                if ($attempt->status === 'in_progress' && $attempt->isExpired()) {
                    $attempt->update(['status' => 'expired']);

                    throw ApiException::conflict(
                        'Your trivia attempt has expired.',
                        'TRIVIA_ATTEMPT_EXPIRED'
                    );
                }

                throw ApiException::conflict(
                    "You have already completed today's trivia.",
                    'TRIVIA_ALREADY_PLAYED'
                );
            }

            return TriviaAttempt::create([
                'trivia_quiz_id' => $quiz->id,
                'user_id' => $userId,
                'display_name_snapshot' => $profile?->displayName,
                'avatar_url_snapshot' => $profile?->avatarUrl,
                'started_at' => now(),
                'expires_at' => now()->addSeconds(
                    ($quiz->question_count_expected * $quiz->time_per_question_seconds)
                    + config('trivia.attempt_grace_seconds', 5)
                ),
                'status' => 'in_progress',
                'client_type' => $clientType,
            ])->load('quiz.questions.options');
        });
    }

    public function submitAttempt(TriviaAttempt $attempt, string $userId, array $answers): array
    {
        return DB::transaction(function () use ($attempt, $userId, $answers): array {
            $attempt = TriviaAttempt::query()
                ->with('quiz.questions.options')
                ->lockForUpdate()
                ->findOrFail($attempt->id);

            if ($attempt->user_id !== $userId) {
                throw ApiException::notFound('Trivia attempt not found.', 'TRIVIA_ATTEMPT_NOT_FOUND');
            }

            if ($attempt->status === 'submitted') {
                throw ApiException::conflict(
                    'You have already completed this trivia attempt.',
                    'TRIVIA_ALREADY_PLAYED'
                );
            }

            if ($attempt->isExpired()) {
                $attempt->update(['status' => 'expired']);

                throw ApiException::conflict(
                    'Your trivia attempt has expired.',
                    'TRIVIA_ATTEMPT_EXPIRED'
                );
            }

            $quiz = $this->quizResolver->ensureQuizSubmittable($attempt->quiz);
            $this->scoringService->validateAnswerOwnership($quiz, $answers);
            $scored = $this->scoringService->scoreAttempt($quiz, $answers);

            $profile = UserTriviaProfile::query()
                ->where('user_id', $userId)
                ->lockForUpdate()
                ->first();

            $streakBefore = $this->streakService->streakBefore($profile);
            $quizDate = $quiz->quiz_date instanceof Carbon
                ? $quiz->quiz_date
                : Carbon::parse($quiz->quiz_date);
            $streakAfter = $this->streakService->streakAfter($profile, $quizDate);
            $scoreBonus = $quiz->streak_bonus_enabled
                ? $this->streakService->bonusForStreak($streakAfter)
                : 0;
            $scoreTotal = $scored['score_base'] + $scoreBonus;

            $attempt->update([
                'submitted_at' => now(),
                'status' => 'submitted',
                'score_base' => $scored['score_base'],
                'score_bonus' => $scoreBonus,
                'score_total' => $scoreTotal,
                'correct_answers_count' => $scored['correct_answers_count'],
                'wrong_answers_count' => $scored['wrong_answers_count'],
                'unanswered_count' => $scored['unanswered_count'],
                'time_taken_seconds' => $scored['time_taken_seconds'],
                'streak_before' => $streakBefore,
                'streak_after' => $streakAfter,
            ]);

            TriviaAttemptAnswer::query()
                ->where('trivia_attempt_id', $attempt->id)
                ->delete();

            foreach ($scored['answer_rows'] as $answerRow) {
                TriviaAttemptAnswer::create([
                    'trivia_attempt_id' => $attempt->id,
                    ...$answerRow,
                ]);
            }

            $this->updateUserProfile($attempt, $profile);
            $leaderboardImpact = $this->leaderboardService->refreshForAttempt($attempt->load('quiz'));

            $attempt->update(['ranking_snapshot' => $leaderboardImpact]);

            return [
                'attempt_id' => $attempt->id,
                'result' => [
                    'score_base' => $attempt->score_base,
                    'score_bonus' => $attempt->score_bonus,
                    'score_total' => $attempt->score_total,
                    'correct_answers_count' => $attempt->correct_answers_count,
                    'wrong_answers_count' => $attempt->wrong_answers_count,
                    'unanswered_count' => $attempt->unanswered_count,
                    'streak_before' => $attempt->streak_before,
                    'streak_after' => $attempt->streak_after,
                    'new_badges' => [],
                    'leaderboard_impact' => $leaderboardImpact,
                    'rank' => $this->rankPayload($leaderboardImpact),
                ],
                'answer_review' => $scored['answer_review'],
            ];
        });
    }

    public function startResponse(TriviaAttempt $attempt): array
    {
        $attempt->loadMissing('quiz.questions.options');

        return [
            'attempt_id' => $attempt->id,
            'status' => $attempt->status,
            'started_at' => $attempt->started_at->toIso8601String(),
            'expires_at' => $attempt->expires_at->toIso8601String(),
            'already_played' => false,
            'requires_verified_account' => true,
            'quiz' => [
                'id' => $attempt->quiz->id,
                'date' => $attempt->quiz->quiz_date->toDateString(),
                'question_count' => $attempt->quiz->question_count_expected,
                'time_per_question_seconds' => $attempt->quiz->time_per_question_seconds,
            ],
            'questions' => $attempt->quiz->questions->map(fn ($question): array => [
                'id' => $question->id,
                'position' => $question->position,
                'question_text' => $question->question_text,
                'image_url' => $question->image_url,
                'options' => $question->options->map(fn ($option): array => [
                    'id' => $option->id,
                    'position' => $option->position,
                    'option_text' => $option->option_text,
                ])->values()->all(),
            ])->values()->all(),
        ];
    }

    private function rankPayload(array $leaderboardImpact): array
    {
        return [
            'daily' => $leaderboardImpact['daily_rank'] ?? null,
            'weekly' => $leaderboardImpact['weekly_rank'] ?? null,
            'monthly' => $leaderboardImpact['monthly_rank'] ?? null,
            'all_time' => $leaderboardImpact['all_time_rank'] ?? null,
        ];
    }

    private function updateUserProfile(TriviaAttempt $attempt, ?UserTriviaProfile $profile): void
    {
        $profile ??= new UserTriviaProfile(['user_id' => $attempt->user_id]);

        $totalCorrect = ($profile->total_correct_answers ?? 0) + $attempt->correct_answers_count;
        $totalWrong = ($profile->total_wrong_answers ?? 0) + $attempt->wrong_answers_count;
        $accuracyDenominator = $totalCorrect + $totalWrong;

        $profile->fill([
            'user_id' => $attempt->user_id,
            'display_name_snapshot' => $attempt->display_name_snapshot,
            'avatar_url_snapshot' => $attempt->avatar_url_snapshot,
            'current_streak' => $attempt->streak_after,
            'best_streak' => max($profile->best_streak ?? 0, $attempt->streak_after),
            'total_points' => ($profile->total_points ?? 0) + $attempt->score_total,
            'total_correct_answers' => $totalCorrect,
            'total_wrong_answers' => $totalWrong,
            'total_quizzes_played' => ($profile->total_quizzes_played ?? 0) + 1,
            'total_quizzes_completed' => ($profile->total_quizzes_completed ?? 0) + 1,
            'lifetime_accuracy' => $accuracyDenominator > 0
                ? round(($totalCorrect / $accuracyDenominator) * 100, 2)
                : 0,
            'last_played_quiz_date' => $attempt->quiz->quiz_date,
        ]);

        $profile->save();
    }
}
