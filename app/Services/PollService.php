<?php

namespace App\Services;

use App\Auth\JwtUser;
use App\Data\AuthBoxUserProfile;
use App\Exceptions\ApiException;
use App\Models\Poll;
use App\Models\PollOption;
use App\Models\PollVote;
use Illuminate\Http\Request;

class PollService
{
    public function __construct(private readonly AuthBoxClient $authBoxClient)
    {
    }

    public function resolveSummaryPoll(?string $slug = null): Poll
    {
        $query = Poll::query()
            ->with('options')
            ->where('visibility', 'public')
            ->whereIn('status', ['scheduled', 'live', 'closed']);

        $poll = $slug
            ? $query->where('slug', $slug)->first()
            : $query->orderByRaw("CASE status WHEN 'live' THEN 0 WHEN 'scheduled' THEN 1 WHEN 'closed' THEN 2 ELSE 3 END")
                ->orderByDesc('open_at')
                ->first();

        if (! $poll) {
            throw ApiException::notFound('Poll not found.', 'POLL_NOT_FOUND');
        }

        return $poll;
    }

    public function ensurePubliclyReadable(Poll $poll): Poll
    {
        $poll->loadMissing('options');

        if ($poll->visibility !== 'public' || in_array($poll->status, ['draft', 'archived'], true)) {
            throw ApiException::notFound('Poll not found.', 'POLL_NOT_FOUND');
        }

        return $poll;
    }

    public function viewerContext(Request $request, Poll $poll): array
    {
        /** @var JwtUser|null $user */
        $user = $request->user();
        $token = $request->attributes->get('auth_token');

        return $this->explicitViewerContext(
            poll: $poll,
            user: $user instanceof JwtUser ? $user : null,
            token: is_string($token) ? $token : null,
        );
    }

    public function explicitViewerContext(
        Poll $poll,
        ?JwtUser $user,
        ?string $token,
        ?AuthBoxUserProfile $profile = null,
        ?PollVote $vote = null,
    ): array {
        $poll->loadMissing('options');

        if (! $user instanceof JwtUser || ! is_string($token) || $token === '') {
            return [
                'user' => null,
                'user_id' => null,
                'token' => null,
                'auth_state' => 'signed_out',
                'verified' => null,
                'profile' => null,
                'vote' => null,
            ];
        }

        $vote ??= PollVote::query()
            ->where('poll_id', $poll->id)
            ->where('user_id', $user->userId())
            ->first();

        if ($profile instanceof AuthBoxUserProfile) {
            return [
                'user' => $user,
                'user_id' => $user->userId(),
                'token' => $token,
                'auth_state' => $profile->verified ? 'verified' : 'unverified',
                'verified' => $profile->verified,
                'profile' => $profile,
                'vote' => $vote,
            ];
        }

        try {
            $profile = $this->authBoxClient->currentUserProfile($token, $user->userId());

            return [
                'user' => $user,
                'user_id' => $user->userId(),
                'token' => $token,
                'auth_state' => $profile->verified ? 'verified' : 'unverified',
                'verified' => $profile->verified,
                'profile' => $profile,
                'vote' => $vote,
            ];
        } catch (ApiException) {
            return [
                'user' => $user,
                'user_id' => $user->userId(),
                'token' => $token,
                'auth_state' => 'unknown',
                'verified' => null,
                'profile' => null,
                'vote' => $vote,
            ];
        }
    }

    public function summaryPayload(Poll $poll, array $context, ?PollVote $submittedVote = null): array
    {
        $poll->loadMissing('options');
        $vote = $submittedVote ?? $context['vote'] ?? null;
        $state = $this->surfaceState($poll, $context, $vote);
        $resultsMeta = $this->resultsMeta($poll, $state, $vote instanceof PollVote);

        return [
            'poll' => $this->pollPayload($poll),
            'poll_surface' => [
                'title' => $poll->title,
                'short_description' => $poll->short_description ?: $poll->description,
                'state' => $state,
                'auth_state' => $context['auth_state'] ?? 'signed_out',
                'available' => $state === 'available',
                'can_vote' => $state === 'available' && ($context['user'] ?? null) instanceof JwtUser,
                'has_voted' => $vote instanceof PollVote,
                'my_vote_option_id' => $vote?->poll_option_id,
                'requires_login' => (bool) $poll->login_required,
                'requires_verified_account' => (bool) $poll->verified_account_required,
                'results_visible' => $resultsMeta['visible'],
                'results_state' => $resultsMeta['state'],
                'open_at' => $poll->open_at?->toIso8601String(),
                'close_at' => $poll->close_at?->toIso8601String(),
                'cta' => $this->ctaPayload($state),
            ],
            'options' => $this->optionsPayload($poll, $vote, $resultsMeta['visible']),
            'results' => $resultsMeta['visible'] ? $this->resultsPayload($poll, $vote) : null,
        ];
    }

    public function resultsPayloadForEndpoint(Poll $poll, array $context): array
    {
        $poll->loadMissing('options');
        $vote = $context['vote'] ?? null;
        $state = $this->surfaceState($poll, $context, $vote instanceof PollVote ? $vote : null);
        $resultsMeta = $this->resultsMeta($poll, $state, $vote instanceof PollVote);

        if (! $resultsMeta['visible']) {
            throw ApiException::forbidden('Poll results are not visible yet.', 'POLL_RESULTS_HIDDEN');
        }

        return [
            'poll' => $this->pollPayload($poll),
            'poll_surface' => [
                'state' => $state,
                'auth_state' => $context['auth_state'] ?? 'signed_out',
                'has_voted' => $vote instanceof PollVote,
                'my_vote_option_id' => $vote?->poll_option_id,
                'results_visible' => true,
                'results_state' => $resultsMeta['state'],
            ],
            'options' => $this->optionsPayload($poll, $vote instanceof PollVote ? $vote : null, true),
            'results' => $this->resultsPayload($poll, $vote instanceof PollVote ? $vote : null),
        ];
    }

    public function isOpenForVoting(Poll $poll): bool
    {
        return $this->surfaceWindowState($poll) === 'live';
    }

    public function surfaceWindowState(Poll $poll): string
    {
        if (in_array($poll->status, ['draft', 'archived'], true)) {
            return 'unavailable';
        }

        if ($poll->status === 'closed') {
            return 'closed';
        }

        if ($poll->open_at && $poll->open_at->isFuture()) {
            return 'scheduled';
        }

        if ($poll->close_at && $poll->close_at->isPast()) {
            return 'closed';
        }

        return 'live';
    }

    private function surfaceState(Poll $poll, array $context, ?PollVote $vote): string
    {
        $windowState = $this->surfaceWindowState($poll);

        if ($windowState === 'unavailable') {
            return 'unavailable';
        }

        if ($windowState === 'scheduled') {
            return 'scheduled';
        }

        if ($windowState === 'closed') {
            return $this->resultsVisibleAfterClose($poll) ? 'results_only' : 'closed';
        }

        if ($vote instanceof PollVote) {
            return 'already_voted';
        }

        if (($context['user'] ?? null) === null) {
            return 'signed_out';
        }

        if ($poll->verified_account_required && ($context['auth_state'] ?? null) === 'unverified') {
            return 'verification_required';
        }

        if ($poll->verified_account_required && ($context['auth_state'] ?? null) === 'unknown') {
            return 'unavailable';
        }

        return 'available';
    }

    private function resultsMeta(Poll $poll, string $state, bool $hasVoted): array
    {
        if (in_array($state, ['closed', 'results_only'], true)) {
            return [
                'visible' => $this->resultsVisibleAfterClose($poll),
                'state' => $this->resultsVisibleAfterClose($poll) ? 'final' : 'hidden',
            ];
        }

        if ($this->surfaceWindowState($poll) === 'live' && $poll->result_visibility_mode === 'live_percentages') {
            $visible = $poll->allow_result_view_before_vote || $hasVoted;

            return [
                'visible' => $visible,
                'state' => $visible ? 'live' : 'hidden',
            ];
        }

        return [
            'visible' => false,
            'state' => 'hidden',
        ];
    }

    private function resultsVisibleAfterClose(Poll $poll): bool
    {
        return in_array($poll->result_visibility_mode, ['hidden_until_end', 'live_percentages', 'final_results'], true);
    }

    private function pollPayload(Poll $poll): array
    {
        return [
            'id' => $poll->id,
            'slug' => $poll->slug,
            'type' => $poll->type,
            'category' => $poll->category,
            'title' => $poll->title,
            'question' => $poll->question,
            'description' => $poll->description,
            'status' => $this->surfaceWindowState($poll) === 'live' ? 'live' : $poll->status,
            'open_at' => $poll->open_at?->toIso8601String(),
            'close_at' => $poll->close_at?->toIso8601String(),
            'login_required' => (bool) $poll->login_required,
            'verified_account_required' => (bool) $poll->verified_account_required,
            'result_visibility_mode' => $poll->result_visibility_mode,
            'sponsor_name' => $poll->sponsor_name,
            'cover_image_url' => $poll->cover_image_url,
            'banner_image_url' => $poll->banner_image_url,
            'context_type' => $poll->context_type,
            'context_id' => $poll->context_id,
        ];
    }

    private function optionsPayload(Poll $poll, ?PollVote $vote, bool $resultsVisible): array
    {
        $counts = PollVote::query()
            ->where('poll_id', $poll->id)
            ->selectRaw('poll_option_id, COUNT(*) as vote_count')
            ->groupBy('poll_option_id')
            ->pluck('vote_count', 'poll_option_id');

        $totalVotes = (int) $counts->sum();

        return $poll->options
            ->where('status', 'active')
            ->values()
            ->map(function (PollOption $option) use ($counts, $resultsVisible, $totalVotes, $vote): array {
                $voteCount = (int) ($counts[$option->id] ?? 0);
                $percentage = $totalVotes > 0 ? round(($voteCount / $totalVotes) * 100, 2) : 0.0;

                return [
                    'id' => $option->id,
                    'title' => $option->title,
                    'subtitle' => $option->subtitle,
                    'description' => $option->description,
                    'image_url' => $option->image_url,
                    'video_url' => $option->video_url,
                    'thumbnail_url' => $option->thumbnail_url,
                    'badge_text' => $option->badge_text,
                    'stats_summary' => $option->stats_summary,
                    'display_order' => $option->display_order,
                    'entity_type' => $option->entity_type,
                    'entity_id' => $option->entity_id,
                    'is_selected' => $vote?->poll_option_id === $option->id,
                    'vote_count' => $resultsVisible ? $voteCount : null,
                    'percentage' => $resultsVisible ? $percentage : null,
                ];
            })
            ->all();
    }

    private function resultsPayload(Poll $poll, ?PollVote $vote = null): array
    {
        $options = $this->optionsPayload($poll, $vote, true);
        $winnerOption = collect($options)->sortByDesc('vote_count')->first();

        return [
            'total_votes' => (int) collect($options)->sum('vote_count'),
            'winner_option_id' => $winnerOption['id'] ?? null,
            'options' => $options,
        ];
    }

    private function ctaPayload(string $state): array
    {
        return match ($state) {
            'signed_out' => ['label' => 'Sign In to Vote', 'action' => 'sign_in', 'destination' => 'login', 'disabled' => false],
            'verification_required' => ['label' => 'Verify Account', 'action' => 'verify_account', 'destination' => 'account_verification', 'disabled' => false],
            'available' => ['label' => 'Vote Now', 'action' => 'open_poll', 'destination' => 'poll_detail', 'disabled' => false],
            'already_voted' => ['label' => 'View Results', 'action' => 'open_poll', 'destination' => 'poll_detail', 'disabled' => false],
            'scheduled' => ['label' => 'Opens Soon', 'action' => 'open_poll', 'destination' => 'poll_detail', 'disabled' => false],
            'closed', 'results_only' => ['label' => 'View Results', 'action' => 'open_poll', 'destination' => 'poll_detail', 'disabled' => false],
            default => ['label' => 'Unavailable', 'action' => 'none', 'destination' => null, 'disabled' => true],
        };
    }
}

