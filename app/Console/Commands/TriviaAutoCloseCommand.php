<?php

namespace App\Console\Commands;

use App\Services\TriviaOperationsService;
use Illuminate\Console\Command;

class TriviaAutoCloseCommand extends Command
{
    protected $signature = 'trivia:quizzes:auto-close';
    protected $description = 'Close quizzes whose close time has passed.';

    public function handle(TriviaOperationsService $operationsService): int
    {
        $count = $operationsService->autoCloseExpiredQuizzes();
        $this->info("Closed {$count} quiz(es).");

        return self::SUCCESS;
    }
}
