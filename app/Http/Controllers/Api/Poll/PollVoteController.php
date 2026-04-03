<?php

namespace App\Http\Controllers\Api\Poll;

use App\Http\Controllers\Controller;
use App\Models\Poll;
use App\Services\PollVoteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PollVoteController extends Controller
{
    public function __construct(private readonly PollVoteService $pollVoteService)
    {
    }

    public function store(Request $request, Poll $poll): JsonResponse
    {
        $validated = $request->validate([
            'option_id' => ['required', 'integer'],
            'client' => ['nullable', 'string', 'max:64'],
            'session_id' => ['nullable', 'string', 'max:128'],
            'metadata' => ['nullable', 'array'],
        ]);

        return response()->json($this->pollVoteService->submit($poll, $validated, $request));
    }
}
