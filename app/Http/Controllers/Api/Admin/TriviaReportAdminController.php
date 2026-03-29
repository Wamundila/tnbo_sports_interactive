<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminTriviaReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TriviaReportAdminController extends Controller
{
    public function __construct(private readonly AdminTriviaReportService $reportService)
    {
    }

    public function overview(): JsonResponse
    {
        return response()->json($this->reportService->overview());
    }

    public function attempts(Request $request): JsonResponse
    {
        return response()->json($this->reportService->attempts($request->only([
            'quiz_date',
            'status',
            'client',
            'min_score',
            'max_score',
            'limit',
        ])));
    }

    public function leaderboards(Request $request): JsonResponse
    {
        return response()->json($this->reportService->leaderboard(
            boardType: (string) $request->query('board_type', 'daily'),
            periodKey: $request->query('period_key'),
            limit: (int) $request->query('limit', 50),
        ));
    }

    public function activity(Request $request): JsonResponse
    {
        return response()->json($this->reportService->activity((int) $request->query('limit', 50)));
    }
}
