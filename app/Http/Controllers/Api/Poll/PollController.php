<?php

namespace App\Http\Controllers\Api\Poll;

use App\Http\Controllers\Controller;
use App\Models\Poll;
use App\Services\PollService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PollController extends Controller
{
    public function __construct(private readonly PollService $pollService)
    {
    }

    public function summary(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'poll_slug' => ['nullable', 'string', 'max:255'],
        ]);

        $poll = $this->pollService->resolveSummaryPoll($validated['poll_slug'] ?? null);
        $context = $this->pollService->viewerContext($request, $poll);

        return response()->json($this->pollService->summaryPayload($poll, $context));
    }

    public function show(Request $request, Poll $poll): JsonResponse
    {
        $poll = $this->pollService->ensurePubliclyReadable($poll);
        $context = $this->pollService->viewerContext($request, $poll);

        return response()->json($this->pollService->summaryPayload($poll, $context));
    }

    public function results(Request $request, Poll $poll): JsonResponse
    {
        $poll = $this->pollService->ensurePubliclyReadable($poll);
        $context = $this->pollService->viewerContext($request, $poll);

        return response()->json($this->pollService->resultsPayloadForEndpoint($poll, $context));
    }
}
