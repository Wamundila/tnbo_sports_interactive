<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\TriviaQuiz;
use App\Services\AdminTriviaReportService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(AdminTriviaReportService $reportService): View
    {
        return view('admin.dashboard', [
            'overview' => $reportService->overview(),
            'recentAttempts' => $reportService->attempts(['limit' => 8])['items'],
            'leaderboard' => $reportService->leaderboard('daily', now()->toDateString(), 5),
            'recentActivity' => $reportService->activity(8)['items'],
            'recentQuizzes' => TriviaQuiz::query()
                ->withCount(['questions', 'attempts'])
                ->orderByDesc('quiz_date')
                ->limit(5)
                ->get(),
        ]);
    }
}
