<?php

namespace App\Console\Commands;

use App\Services\TriviaOperationsService;
use Illuminate\Console\Command;

class TriviaExpireAttemptsCommand extends Command
{
    protected $signature = 'trivia:attempts:expire';
    protected $description = 'Expire in-progress trivia attempts whose expiry has passed.';

    public function handle(TriviaOperationsService $operationsService): int
    {
        $count = $operationsService->expireStaleAttempts();
        $this->info("Expired {$count} attempt(s).");

        return self::SUCCESS;
    }
}
