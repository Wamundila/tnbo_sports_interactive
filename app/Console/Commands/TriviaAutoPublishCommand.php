<?php

namespace App\Console\Commands;

use App\Services\TriviaOperationsService;
use Illuminate\Console\Command;

class TriviaAutoPublishCommand extends Command
{
    protected $signature = 'trivia:quizzes:auto-publish';
    protected $description = 'Publish quizzes whose open time has been reached.';

    public function handle(TriviaOperationsService $operationsService): int
    {
        $count = $operationsService->autoPublishDueQuizzes();
        $this->info("Published {$count} quiz(es).");

        return self::SUCCESS;
    }
}
