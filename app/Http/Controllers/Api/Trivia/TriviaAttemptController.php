<?php

namespace App\Http\Controllers\Api\Trivia;

use App\Auth\JwtUser;
use App\Data\AuthBoxUserProfile;
use App\Http\Controllers\Controller;
use App\Http\Requests\StartTriviaAttemptRequest;
use App\Http\Requests\SubmitTriviaAttemptRequest;
use App\Models\TriviaAttempt;
use App\Services\TriviaAttemptService;
use Illuminate\Http\JsonResponse;

class TriviaAttemptController extends Controller
{
    public function __construct(private readonly TriviaAttemptService $attemptService)
    {
    }

    public function start(StartTriviaAttemptRequest $request): JsonResponse
    {
        /** @var JwtUser $user */
        $user = $request->user();
        /** @var AuthBoxUserProfile|null $profile */
        $profile = $request->attributes->get('current_user_profile');

        $attempt = $this->attemptService->startTodayAttempt(
            userId: $user->userId(),
            profile: $profile,
            clientType: $request->validated('client'),
        );

        return response()->json($this->attemptService->startResponse($attempt));
    }

    public function submit(SubmitTriviaAttemptRequest $request, TriviaAttempt $attempt): JsonResponse
    {
        /** @var JwtUser $user */
        $user = $request->user();

        return response()->json(
            $this->attemptService->submitAttempt(
                attempt: $attempt,
                userId: $user->userId(),
                answers: $request->validated('answers'),
            )
        );
    }
}
