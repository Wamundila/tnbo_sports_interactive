<?php

namespace App\Console\Commands;

use App\Services\TriviaOperationsService;
use Illuminate\Console\Command;

class TriviaRefreshLeaderboardsCommand extends Command
{
    protected $signature = 'trivia:leaderboards:refresh {board_type?} {period_key?}';
    protected $description = 'Refresh leaderboard snapshots for one or more periods.';

    public function handle(TriviaOperationsService $operationsService): int
    {
        $count = $operationsService->refreshLeaderboards(
            $this->argument('board_type'),
            $this->argument('period_key'),
        );

        $this->info("Refreshed {$count} leaderboard target(s).");

        return self::SUCCESS;
    }
}
