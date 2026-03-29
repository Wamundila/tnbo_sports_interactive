<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminTriviaReportService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function __invoke(Request $request, AdminTriviaReportService $reportService): View
    {
        $filters = $request->validate([
            'quiz_date' => ['nullable', 'date'],
            'status' => ['nullable', Rule::in(['started', 'submitted', 'expired'])],
            'client' => ['nullable', 'string', 'max:50'],
            'min_score' => ['nullable', 'integer', 'min:0'],
            'max_score' => ['nullable', 'integer', 'min:0'],
            'board_type' => ['nullable', Rule::in(['daily', 'weekly', 'monthly', 'all_time'])],
            'period_key' => ['nullable', 'string', 'max:50'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $attemptFilters = [
            'quiz_date' => $filters['quiz_date'] ?? null,
            'status' => $filters['status'] ?? null,
            'client' => $filters['client'] ?? null,
            'min_score' => $filters['min_score'] ?? null,
            'max_score' => $filters['max_score'] ?? null,
            'limit' => $filters['limit'] ?? 25,
        ];

        $boardType = $filters['board_type'] ?? 'daily';
        $periodKey = ($filters['period_key'] ?? '') !== '' ? $filters['period_key'] : null;

        return view('admin.reports.index', [
            'filters' => [
                'quiz_date' => $filters['quiz_date'] ?? '',
                'status' => $filters['status'] ?? '',
                'client' => $filters['client'] ?? '',
                'min_score' => $filters['min_score'] ?? '',
                'max_score' => $filters['max_score'] ?? '',
                'board_type' => $boardType,
                'period_key' => $filters['period_key'] ?? '',
                'limit' => $attemptFilters['limit'],
            ],
            'attempts' => $reportService->attempts($attemptFilters)['items'],
            'leaderboard' => $reportService->leaderboard($boardType, $periodKey, 25),
            'activity' => $reportService->activity(20)['items'],
        ]);
    }
}
