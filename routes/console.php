<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('trivia:quizzes:auto-publish')->everyMinute()->withoutOverlapping();
Schedule::command('trivia:quizzes:auto-close')->everyMinute()->withoutOverlapping();
Schedule::command('trivia:attempts:expire')->everyMinute()->withoutOverlapping();
Schedule::command('trivia:leaderboards:refresh')->everyFiveMinutes()->withoutOverlapping();
