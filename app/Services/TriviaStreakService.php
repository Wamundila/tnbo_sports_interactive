<?php

namespace App\Services;

use App\Models\UserTriviaProfile;
use Illuminate\Support\Carbon;

class TriviaStreakService
{
    public function streakBefore(?UserTriviaProfile $profile): int
    {
        return $profile?->current_streak ?? 0;
    }

    public function streakAfter(?UserTriviaProfile $profile, Carbon $quizDate): int
    {
        $lastPlayed = $profile?->last_played_quiz_date;

        if ($lastPlayed && $lastPlayed->toDateString() === $quizDate->copy()->subDay()->toDateString()) {
            return ($profile?->current_streak ?? 0) + 1;
        }

        return 1;
    }

    public function bonusForStreak(int $streakAfter): int
    {
        return match ($streakAfter) {
            3 => 3,
            7 => 6,
            14 => 9,
            default => 0,
        };
    }
}
