<?php

namespace App\Http\Controllers\Web\Admin;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpsertPredictorSeasonRequest;
use App\Models\PredictorCampaign;
use App\Models\PredictorSeason;
use App\Services\AdminPredictorManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PredictorSeasonController extends Controller
{
    public function create(PredictorCampaign $campaign): View
    {
        return view('admin.predictor.season-form', [
            'pageTitle' => 'Create Season',
            'campaign' => $campaign,
            'season' => null,
            'form' => $this->formData($campaign),
            'rounds' => collect(),
        ]);
    }

    public function store(UpsertPredictorSeasonRequest $request, PredictorCampaign $campaign, AdminPredictorManagementService $service): RedirectResponse
    {
        try {
            $season = $service->createSeason($campaign, $this->normalizedPayload($request));
        } catch (ApiException $exception) {
            return back()->withInput()->withErrors(['season' => $exception->getMessage()]);
        }

        return redirect()
            ->route('admin.predictor.seasons.edit', $season)
            ->with('status', 'Predictor season created successfully.');
    }

    public function edit(PredictorSeason $season): View
    {
        $season->loadMissing('campaign');

        return view('admin.predictor.season-form', [
            'pageTitle' => 'Edit Season',
            'campaign' => $season->campaign,
            'season' => $season,
            'form' => $this->formData($season->campaign, $season),
            'rounds' => $season->rounds()->withCount('fixtures')->orderBy('opens_at')->get(),
        ]);
    }

    public function update(UpsertPredictorSeasonRequest $request, PredictorSeason $season, AdminPredictorManagementService $service): RedirectResponse
    {
        try {
            $service->updateSeason($season->loadMissing('campaign'), $this->normalizedPayload($request));
        } catch (ApiException $exception) {
            return back()->withInput()->withErrors(['season' => $exception->getMessage()]);
        }

        return redirect()
            ->route('admin.predictor.seasons.edit', $season)
            ->with('status', 'Predictor season updated successfully.');
    }

    private function formData(PredictorCampaign $campaign, ?PredictorSeason $season = null): array
    {
        $config = array_merge(config('predictor.default_scoring', []), $season?->scoring_config ?? []);

        return [
            'name' => $season?->name ?? now()->year.' Season',
            'slug' => $season?->slug ?? now()->year.'-season',
            'start_date' => $season?->start_date?->toDateString() ?? now()->toDateString(),
            'end_date' => $season?->end_date?->toDateString() ?? now()->addMonths(3)->toDateString(),
            'status' => $season?->status ?? 'draft',
            'rules_text' => $season?->rules_text ?? '',
            'is_current' => $season?->is_current ?? true,
            'scoring_outcome_points' => $config['outcome_points'] ?? 3,
            'scoring_exact_score_points' => $config['exact_score_points'] ?? 5,
            'scoring_close_score_points' => $config['close_score_points'] ?? 1.5,
            'scoring_banker_multiplier' => $config['banker_multiplier'] ?? 2,
            'banker_enabled' => $campaign->banker_enabled,
        ];
    }

    private function normalizedPayload(UpsertPredictorSeasonRequest $request): array
    {
        $validated = $request->validated();

        return [
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'status' => $validated['status'],
            'rules_text' => $validated['rules_text'] ?? null,
            'is_current' => (bool) ($validated['is_current'] ?? false),
            'scoring_config' => [
                'outcome_points' => (float) $validated['scoring_outcome_points'],
                'exact_score_points' => (float) $validated['scoring_exact_score_points'],
                'close_score_points' => (float) $validated['scoring_close_score_points'],
                'banker_enabled' => $request->route('campaign')?->banker_enabled ?? $request->route('season')?->campaign?->banker_enabled ?? true,
                'banker_multiplier' => (float) $validated['scoring_banker_multiplier'],
            ],
        ];
    }
}
