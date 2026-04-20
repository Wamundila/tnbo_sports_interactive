<?php

namespace App\Http\Controllers\Web\Admin;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpsertPredictorCampaignRequest;
use App\Models\PredictorCampaign;
use App\Services\AdminPredictorManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PredictorCampaignController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::in(['draft', 'active', 'archived'])],
        ]);

        $query = PredictorCampaign::query()
            ->withCount('seasons')
            ->orderByDesc('starts_at')
            ->orderBy('display_name');

        if (($filters['status'] ?? '') !== '') {
            $query->where('status', $filters['status']);
        }

        return view('admin.predictor.index', [
            'campaigns' => $query->paginate(12)->withQueryString(),
            'filters' => [
                'status' => $filters['status'] ?? '',
            ],
        ]);
    }

    public function create(): View
    {
        return view('admin.predictor.campaign-form', [
            'pageTitle' => 'Create Campaign',
            'campaign' => null,
            'form' => $this->formData(),
            'seasons' => collect(),
        ]);
    }

    public function store(UpsertPredictorCampaignRequest $request, AdminPredictorManagementService $service): RedirectResponse
    {
        try {
            $campaign = $service->createCampaign($this->normalizedPayload($request), Auth::guard('admin')->user());
        } catch (ApiException $exception) {
            return back()->withInput()->withErrors(['campaign' => $exception->getMessage()]);
        }

        return redirect()
            ->route('admin.predictor.campaigns.edit', $campaign)
            ->with('status', 'Predictor campaign created successfully.');
    }

    public function edit(PredictorCampaign $campaign): View
    {
        return view('admin.predictor.campaign-form', [
            'pageTitle' => 'Edit Campaign',
            'campaign' => $campaign,
            'form' => $this->formData($campaign),
            'seasons' => $campaign->seasons()->withCount('rounds')->orderByDesc('is_current')->orderByDesc('start_date')->get(),
        ]);
    }

    public function update(UpsertPredictorCampaignRequest $request, PredictorCampaign $campaign, AdminPredictorManagementService $service): RedirectResponse
    {
        try {
            $service->updateCampaign($campaign, $this->normalizedPayload($request), Auth::guard('admin')->user());
        } catch (ApiException $exception) {
            return back()->withInput()->withErrors(['campaign' => $exception->getMessage()]);
        }

        return redirect()
            ->route('admin.predictor.campaigns.edit', $campaign)
            ->with('status', 'Predictor campaign updated successfully.');
    }

    private function formData(?PredictorCampaign $campaign = null): array
    {
        return [
            'name' => $campaign?->name ?? 'Super League Predictor',
            'slug' => $campaign?->slug ?? 'super_league_predictor',
            'display_name' => $campaign?->display_name ?? 'MTN Super League Predictor',
            'sponsor_name' => $campaign?->sponsor_name ?? '',
            'description' => $campaign?->description ?? '',
            'banner_image_url' => $campaign?->banner_image_url ?? '',
            'scope_type' => $campaign?->scope_type ?? 'single_competition',
            'default_fixture_count' => $campaign?->default_fixture_count ?? 4,
            'banker_enabled' => $campaign?->banker_enabled ?? true,
            'status' => $campaign?->status ?? 'draft',
            'visibility' => $campaign?->visibility ?? 'public',
            'starts_at' => $campaign?->starts_at?->format('Y-m-d\\TH:i') ?? now()->format('Y-m-d\\TH:i'),
            'ends_at' => $campaign?->ends_at?->format('Y-m-d\\TH:i') ?? now()->addMonths(3)->format('Y-m-d\\TH:i'),
        ];
    }

    private function normalizedPayload(UpsertPredictorCampaignRequest $request): array
    {
        $validated = $request->validated();
        $validated['banner_image_url'] = $this->storeMediaUpload(
            $request->file('banner_image_upload'),
            $validated['existing_banner_image_url'] ?? null,
            'campaign-banners'
        );

        unset(
            $validated['existing_banner_image_url'],
            $validated['banner_image_upload'],
        );

        return $validated;
    }

    private function storeMediaUpload(?UploadedFile $file, ?string $existingPath, string $segment): ?string
    {
        if (! $file instanceof UploadedFile) {
            return is_string($existingPath) && trim($existingPath) !== '' ? $existingPath : null;
        }

        $extension = $file->getClientOriginalExtension() ?: $file->extension() ?: 'bin';
        $filename = now()->format('YmdHis').'-'.Str::lower(Str::random(16)).'.'.$extension;
        $path = Storage::disk('public')->putFileAs('uploads/predictor/'.$segment, $file, $filename);

        return $path ? '/storage/'.$path : null;
    }
}
