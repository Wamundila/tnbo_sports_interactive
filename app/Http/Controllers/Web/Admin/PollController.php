<?php

namespace App\Http\Controllers\Web\Admin;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Models\Poll;
use App\Services\AdminPollService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PollController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::in(['draft', 'scheduled', 'live', 'closed', 'archived'])],
        ]);

        $query = Poll::query()
            ->withCount(['options', 'votes'])
            ->orderByDesc('open_at')
            ->orderByDesc('id');

        if (($filters['status'] ?? '') !== '') {
            $query->where('status', $filters['status']);
        }

        return view('admin.polls.index', [
            'filters' => ['status' => $filters['status'] ?? ''],
            'polls' => $query->paginate(12)->withQueryString(),
        ]);
    }

    public function create(): View
    {
        return view('admin.polls.form', [
            'pageTitle' => 'Create Poll',
            'poll' => null,
            'form' => $this->formData(),
        ]);
    }

    public function store(Request $request, AdminPollService $pollService): RedirectResponse
    {
        $payload = $this->validatedPayload($request);

        try {
            $poll = $pollService->create($payload, Auth::guard('admin')->user());
        } catch (ApiException $exception) {
            return back()->withInput()->withErrors(['poll' => $exception->getMessage()]);
        }

        return redirect()->route('admin.polls.edit', $poll)->with('status', 'Poll created successfully.');
    }

    public function edit(Poll $poll): View
    {
        $poll->loadMissing('options');

        return view('admin.polls.form', [
            'pageTitle' => 'Edit Poll',
            'poll' => $poll,
            'form' => $this->formData($poll),
        ]);
    }

    public function update(Request $request, Poll $poll, AdminPollService $pollService): RedirectResponse
    {
        $payload = $this->validatedPayload($request, $poll);

        try {
            $pollService->update($poll, $payload, Auth::guard('admin')->user());
        } catch (ApiException $exception) {
            return back()->withInput()->withErrors(['poll' => $exception->getMessage()]);
        }

        return redirect()->route('admin.polls.edit', $poll)->with('status', 'Poll updated successfully.');
    }

    public function publish(Poll $poll, AdminPollService $pollService): RedirectResponse
    {
        try {
            $pollService->publish($poll, Auth::guard('admin')->user());
        } catch (ApiException $exception) {
            return back()->withErrors(['poll' => $exception->getMessage()]);
        }

        return redirect()->route('admin.polls.edit', $poll)->with('status', 'Poll published successfully.');
    }

    public function close(Poll $poll, AdminPollService $pollService): RedirectResponse
    {
        $pollService->close($poll, Auth::guard('admin')->user());

        return redirect()->route('admin.polls.edit', $poll)->with('status', 'Poll closed successfully.');
    }

    private function validatedPayload(Request $request, ?Poll $poll = null): array
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'question' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('polls', 'slug')->ignore($poll?->id)],
            'category' => ['nullable', 'string', 'max:64'],
            'description' => ['nullable', 'string'],
            'short_description' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(['draft', 'scheduled', 'live', 'closed', 'archived'])],
            'visibility' => ['required', Rule::in(['public', 'private'])],
            'open_at' => ['nullable', 'date'],
            'close_at' => ['nullable', 'date', 'after:open_at'],
            'result_visibility_mode' => ['required', Rule::in(['hidden_until_end', 'live_percentages', 'final_results'])],
            'context_type' => ['nullable', 'string', 'max:64'],
            'context_id' => ['nullable', 'string', 'max:128'],
            'sponsor_name' => ['nullable', 'string', 'max:255'],
            'existing_cover_image_url' => ['nullable', 'string', 'max:255'],
            'existing_banner_image_url' => ['nullable', 'string', 'max:255'],
            'cover_image_upload' => ['nullable', 'file', 'image', 'max:5120'],
            'banner_image_upload' => ['nullable', 'file', 'image', 'max:5120'],
            'metadata' => ['nullable', 'array'],
            'options' => ['required', 'array', 'min:2'],
            'options.*.id' => ['nullable', 'integer'],
            'options.*.title' => ['nullable', 'string', 'max:255'],
            'options.*.subtitle' => ['nullable', 'string', 'max:255'],
            'options.*.description' => ['nullable', 'string'],
            'options.*.existing_image_url' => ['nullable', 'string', 'max:255'],
            'options.*.existing_video_url' => ['nullable', 'string', 'max:255'],
            'options.*.existing_thumbnail_url' => ['nullable', 'string', 'max:255'],
            'options.*.image_upload' => ['nullable', 'file', 'image', 'max:5120'],
            'options.*.video_upload' => ['nullable', 'file', 'mimetypes:video/mp4,video/webm,video/quicktime,video/x-msvideo', 'max:51200'],
            'options.*.thumbnail_upload' => ['nullable', 'file', 'image', 'max:5120'],
            'options.*.badge_text' => ['nullable', 'string', 'max:255'],
            'options.*.stats_summary' => ['nullable', 'string', 'max:255'],
            'options.*.entity_type' => ['nullable', 'string', 'max:64'],
            'options.*.entity_id' => ['nullable', 'string', 'max:128'],
            'options.*.status' => ['nullable', Rule::in(['active', 'inactive'])],
            'options.*.metadata' => ['nullable', 'array'],
        ]);

        $validated['login_required'] = $request->boolean('login_required', true);
        $validated['verified_account_required'] = $request->boolean('verified_account_required');
        $validated['allow_result_view_before_vote'] = $request->boolean('allow_result_view_before_vote');
        $validated['cover_image_url'] = $this->storeMediaUpload(
            $request->file('cover_image_upload'),
            $validated['existing_cover_image_url'] ?? null,
            'covers'
        );
        $validated['banner_image_url'] = $this->storeMediaUpload(
            $request->file('banner_image_upload'),
            $validated['existing_banner_image_url'] ?? null,
            'banners'
        );
        $validated['options'] = $this->normalizeOptions($request);

        unset(
            $validated['existing_cover_image_url'],
            $validated['existing_banner_image_url'],
            $validated['cover_image_upload'],
            $validated['banner_image_upload'],
        );

        return $validated;
    }

    private function formData(?Poll $poll = null): array
    {
        $poll?->loadMissing('options');
        $optionTotal = max(2, $poll?->options->count() ?? 0, 4);
        $options = [];

        for ($index = 0; $index < $optionTotal; $index++) {
            $option = $poll?->options->get($index);
            $options[] = [
                'id' => $option?->id,
                'title' => $option?->title ?? '',
                'subtitle' => $option?->subtitle ?? '',
                'description' => $option?->description ?? '',
                'image_url' => $option?->image_url ?? '',
                'video_url' => $option?->video_url ?? '',
                'thumbnail_url' => $option?->thumbnail_url ?? '',
                'badge_text' => $option?->badge_text ?? '',
                'stats_summary' => $option?->stats_summary ?? '',
                'entity_type' => $option?->entity_type ?? '',
                'entity_id' => $option?->entity_id ?? '',
                'status' => $option?->status ?? 'active',
            ];
        }

        return [
            'title' => $poll?->title ?? 'TNBO Fan Poll',
            'question' => $poll?->question ?? 'Who should win?',
            'slug' => $poll?->slug ?? '',
            'category' => $poll?->category ?? 'fan_vote',
            'description' => $poll?->description ?? '',
            'short_description' => $poll?->short_description ?? 'Cast one vote and follow the results.',
            'status' => $poll?->status ?? 'draft',
            'visibility' => $poll?->visibility ?? 'public',
            'open_at' => $poll?->open_at?->format('Y-m-d\TH:i') ?? now()->addHour()->format('Y-m-d\TH:i'),
            'close_at' => $poll?->close_at?->format('Y-m-d\TH:i') ?? now()->addDays(2)->format('Y-m-d\TH:i'),
            'login_required' => $poll?->login_required ?? true,
            'verified_account_required' => $poll?->verified_account_required ?? false,
            'allow_result_view_before_vote' => $poll?->allow_result_view_before_vote ?? false,
            'result_visibility_mode' => $poll?->result_visibility_mode ?? 'hidden_until_end',
            'context_type' => $poll?->context_type ?? '',
            'context_id' => $poll?->context_id ?? '',
            'sponsor_name' => $poll?->sponsor_name ?? '',
            'cover_image_url' => $poll?->cover_image_url ?? '',
            'banner_image_url' => $poll?->banner_image_url ?? '',
            'options' => $options,
        ];
    }

    private function normalizeOptions(Request $request): array
    {
        $normalized = [];

        foreach ($request->input('options', []) as $index => $option) {
            $title = $this->trimString($option['title'] ?? null);
            $subtitle = $this->trimString($option['subtitle'] ?? null);
            $description = $this->trimString($option['description'] ?? null);
            $badgeText = $this->trimString($option['badge_text'] ?? null);
            $statsSummary = $this->trimString($option['stats_summary'] ?? null);
            $entityType = $this->trimString($option['entity_type'] ?? null);
            $entityId = $this->trimString($option['entity_id'] ?? null);
            $existingImage = $this->trimString($option['existing_image_url'] ?? null);
            $existingVideo = $this->trimString($option['existing_video_url'] ?? null);
            $existingThumbnail = $this->trimString($option['existing_thumbnail_url'] ?? null);
            $hasUpload = $request->file("options.$index.image_upload") instanceof UploadedFile
                || $request->file("options.$index.video_upload") instanceof UploadedFile
                || $request->file("options.$index.thumbnail_upload") instanceof UploadedFile;
            $hasStructuredInput = $title !== null
                || $subtitle !== null
                || $description !== null
                || $badgeText !== null
                || $statsSummary !== null
                || $entityType !== null
                || $entityId !== null
                || $existingImage !== null
                || $existingVideo !== null
                || $existingThumbnail !== null
                || ! empty($option['id']);

            if (! $hasStructuredInput && ! $hasUpload) {
                continue;
            }

            if ($title === null) {
                throw ValidationException::withMessages([
                    "options.$index.title" => 'Option title is required for each option you use.',
                ]);
            }

            $status = $option['status'] ?? 'active';

            if (! in_array($status, ['active', 'inactive'], true)) {
                throw ValidationException::withMessages([
                    "options.$index.status" => 'Option status is invalid.',
                ]);
            }

            $normalized[] = [
                'id' => $option['id'] ?? null,
                'title' => $title,
                'subtitle' => $subtitle,
                'description' => $description,
                'image_url' => $this->storeMediaUpload(
                    $request->file("options.$index.image_upload"),
                    $existingImage,
                    'options/images'
                ),
                'video_url' => $this->storeMediaUpload(
                    $request->file("options.$index.video_upload"),
                    $existingVideo,
                    'options/videos'
                ),
                'thumbnail_url' => $this->storeMediaUpload(
                    $request->file("options.$index.thumbnail_upload"),
                    $existingThumbnail,
                    'options/thumbnails'
                ),
                'badge_text' => $badgeText,
                'stats_summary' => $statsSummary,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'status' => $status,
                'metadata' => $option['metadata'] ?? null,
            ];
        }

        if (count($normalized) < 2) {
            throw ValidationException::withMessages([
                'options' => 'At least two options are required.',
            ]);
        }

        return array_values($normalized);
    }

    private function storeMediaUpload(?UploadedFile $file, ?string $existingPath, string $segment): ?string
    {
        if (! $file instanceof UploadedFile) {
            return is_string($existingPath) && trim($existingPath) !== '' ? $existingPath : null;
        }

        $directory = public_path('uploads/polls/'.$segment);

        if (! is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $extension = $file->getClientOriginalExtension() ?: $file->extension() ?: 'bin';
        $filename = now()->format('YmdHis').'-'.Str::lower(Str::random(16)).'.'.$extension;
        $file->move($directory, $filename);

        return '/uploads/polls/'.$segment.'/'.$filename;
    }

    private function trimString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed !== '' ? $trimmed : null;
    }
}
