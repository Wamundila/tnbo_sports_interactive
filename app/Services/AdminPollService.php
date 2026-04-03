<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\Admin;
use App\Models\Poll;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdminPollService
{
    public function create(array $payload, Admin $admin): Poll
    {
        return DB::transaction(function () use ($payload, $admin): Poll {
            $poll = Poll::create($this->pollAttributes($payload, $admin));
            $this->syncOptions($poll, $payload['options'] ?? []);

            return $poll->load('options');
        });
    }

    public function update(Poll $poll, array $payload, Admin $admin): Poll
    {
        return DB::transaction(function () use ($poll, $payload, $admin): Poll {
            $poll->update($this->pollAttributes($payload, $admin, false));
            $this->syncOptions($poll, $payload['options'] ?? []);

            return $poll->load('options');
        });
    }

    public function publish(Poll $poll, Admin $admin): Poll
    {
        $poll->loadMissing('options');
        $activeOptions = $poll->options->where('status', 'active');

        if ($activeOptions->count() < 2) {
            throw ApiException::unprocessable('Poll must have at least two active options before publishing.', 'POLL_CONFIGURATION_ERROR');
        }

        if ($poll->open_at && $poll->close_at && $poll->close_at->lessThanOrEqualTo($poll->open_at)) {
            throw ApiException::unprocessable('Poll close time must be after the open time.', 'POLL_CONFIGURATION_ERROR');
        }

        $status = $poll->open_at && $poll->open_at->isFuture() ? 'scheduled' : 'live';

        $poll->update([
            'status' => $status,
            'published_at' => now(),
            'published_by_admin_id' => $admin->id,
            'updated_by_admin_id' => $admin->id,
        ]);

        return $poll->fresh('options');
    }

    public function close(Poll $poll, Admin $admin): Poll
    {
        $poll->update([
            'status' => 'closed',
            'close_at' => $poll->close_at && $poll->close_at->isPast() ? $poll->close_at : now(),
            'updated_by_admin_id' => $admin->id,
        ]);

        return $poll->fresh('options');
    }

    private function pollAttributes(array $payload, Admin $admin, bool $creating = true): array
    {
        $slug = $payload['slug'] ?: Str::slug($payload['title']);

        $attributes = [
            'type' => 'single_choice',
            'category' => $payload['category'] ?? null,
            'title' => $payload['title'],
            'question' => $payload['question'],
            'slug' => $slug,
            'description' => $payload['description'] ?? null,
            'short_description' => $payload['short_description'] ?? null,
            'status' => $payload['status'] ?? 'draft',
            'visibility' => $payload['visibility'] ?? 'public',
            'open_at' => $payload['open_at'] ?? null,
            'close_at' => $payload['close_at'] ?? null,
            'login_required' => (bool) ($payload['login_required'] ?? true),
            'verified_account_required' => (bool) ($payload['verified_account_required'] ?? false),
            'allow_result_view_before_vote' => (bool) ($payload['allow_result_view_before_vote'] ?? false),
            'result_visibility_mode' => $payload['result_visibility_mode'] ?? 'hidden_until_end',
            'context_type' => $payload['context_type'] ?? null,
            'context_id' => $payload['context_id'] ?? null,
            'sponsor_name' => $payload['sponsor_name'] ?? null,
            'cover_image_url' => $payload['cover_image_url'] ?? null,
            'banner_image_url' => $payload['banner_image_url'] ?? null,
            'metadata' => $payload['metadata'] ?? null,
            'updated_by_admin_id' => $admin->id,
        ];

        if ($creating) {
            $attributes['created_by_admin_id'] = $admin->id;
        }

        return $attributes;
    }

    private function syncOptions(Poll $poll, array $options): void
    {
        $existingIds = $poll->options()->pluck('id')->all();
        $keptIds = [];

        foreach (array_values($options) as $index => $option) {
            $record = $poll->options()->updateOrCreate(
                ['id' => $option['id'] ?? null],
                [
                    'title' => $option['title'],
                    'subtitle' => $option['subtitle'] ?? null,
                    'description' => $option['description'] ?? null,
                    'image_url' => $option['image_url'] ?? null,
                    'video_url' => $option['video_url'] ?? null,
                    'thumbnail_url' => $option['thumbnail_url'] ?? null,
                    'badge_text' => $option['badge_text'] ?? null,
                    'stats_summary' => $option['stats_summary'] ?? null,
                    'entity_type' => $option['entity_type'] ?? null,
                    'entity_id' => $option['entity_id'] ?? null,
                    'display_order' => $index + 1,
                    'status' => $option['status'] ?? 'active',
                    'metadata' => $option['metadata'] ?? null,
                ],
            );

            $keptIds[] = $record->id;
        }

        $idsToDelete = array_diff($existingIds, $keptIds);

        if ($idsToDelete !== []) {
            $poll->options()->whereIn('id', $idsToDelete)->delete();
        }
    }
}

