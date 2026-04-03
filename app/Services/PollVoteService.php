<?php

namespace App\Services;

use App\Auth\JwtUser;
use App\Exceptions\ApiException;
use App\Models\Poll;
use App\Models\PollOption;
use App\Models\PollVote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PollVoteService
{
    public function __construct(
        private readonly PollService $pollService,
        private readonly AuthBoxClient $authBoxClient,
    ) {
    }

    public function submit(Poll $poll, array $payload, Request $request): array
    {
        $poll = $this->pollService->ensurePubliclyReadable($poll);
        /** @var JwtUser|null $user */
        $user = $request->user();
        $token = $request->attributes->get('auth_token');

        if (! $user instanceof JwtUser || ! is_string($token) || $token === '') {
            throw ApiException::unauthorized('Login is required to vote in this poll.', 'POLL_LOGIN_REQUIRED');
        }

        if ($this->pollService->surfaceWindowState($poll) === 'scheduled') {
            throw ApiException::conflict('Voting is not open for this poll.', 'POLL_NOT_OPEN');
        }

        if (! $this->pollService->isOpenForVoting($poll)) {
            throw ApiException::conflict('This poll is closed.', 'POLL_CLOSED');
        }

        $option = PollOption::query()
            ->where('poll_id', $poll->id)
            ->where('id', $payload['option_id'])
            ->where('status', 'active')
            ->first();

        if (! $option) {
            throw ApiException::unprocessable('Selected option is invalid for this poll.', 'POLL_OPTION_INVALID');
        }

        $existingVote = PollVote::query()
            ->where('poll_id', $poll->id)
            ->where('user_id', $user->userId())
            ->first();

        if ($existingVote) {
            throw ApiException::conflict('You have already voted in this poll.', 'POLL_ALREADY_VOTED');
        }

        $profile = $this->authBoxClient->currentUserProfile($token, $user->userId());

        if ($poll->verified_account_required && ! $profile->verified) {
            throw ApiException::forbidden(
                'A verified TNBO Sports account is required to vote in this poll.',
                'POLL_VERIFICATION_REQUIRED'
            );
        }

        $vote = DB::transaction(function () use ($poll, $option, $payload, $request, $profile, $user): PollVote {
            return PollVote::create([
                'poll_id' => $poll->id,
                'poll_option_id' => $option->id,
                'user_id' => $user->userId(),
                'display_name_snapshot' => $profile->displayName,
                'avatar_url_snapshot' => $profile->avatarUrl,
                'client' => $payload['client'] ?? null,
                'session_id' => $payload['session_id'] ?? null,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'submitted_at' => now(),
                'metadata' => $payload['metadata'] ?? null,
            ]);
        });

        $freshPoll = $poll->fresh('options');
        $context = $this->pollService->explicitViewerContext(
            poll: $freshPoll,
            user: $user,
            token: $token,
            profile: $profile,
            vote: $vote,
        );

        return array_merge(
            $this->pollService->summaryPayload($freshPoll, $context, $vote),
            [
                'submitted_at' => $vote->submitted_at?->toIso8601String(),
            ],
        );
    }
}
