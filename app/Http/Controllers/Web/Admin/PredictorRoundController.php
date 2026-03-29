<?php

namespace App\Http\Controllers\Web\Admin;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpsertPredictorRoundRequest;
use App\Models\PredictorRound;
use App\Models\PredictorSeason;
use App\Services\AdminPredictorManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PredictorRoundController extends Controller
{
    public function create(PredictorSeason $season): View
    {
        $season->loadMissing('campaign');

        return view('admin.predictor.round-form', [
            'pageTitle' => 'Create Round',
            'season' => $season,
            'campaign' => $season->campaign,
            'round' => null,
            'form' => $this->formData($season),
        ]);
    }

    public function store(UpsertPredictorRoundRequest $request, PredictorSeason $season, AdminPredictorManagementService $service): RedirectResponse
    {
        try {
            $round = $service->createRound($season->loadMissing('campaign'), $request->validated());
        } catch (ApiException $exception) {
            return back()->withInput()->withErrors(['round' => $exception->getMessage()]);
        }

        return redirect()
            ->route('admin.predictor.rounds.edit', $round)
            ->with('status', 'Predictor round created successfully.');
    }

    public function edit(PredictorRound $round): View
    {
        $round->loadMissing(['fixtures', 'season.campaign']);

        return view('admin.predictor.round-form', [
            'pageTitle' => 'Edit Round',
            'season' => $round->season,
            'campaign' => $round->season->campaign,
            'round' => $round,
            'form' => $this->formData($round->season, $round),
        ]);
    }

    public function update(UpsertPredictorRoundRequest $request, PredictorRound $round, AdminPredictorManagementService $service): RedirectResponse
    {
        try {
            $service->updateRound($round->loadMissing(['fixtures', 'season.campaign']), $request->validated());
        } catch (ApiException $exception) {
            return back()->withInput()->withErrors(['round' => $exception->getMessage()]);
        }

        return redirect()
            ->route('admin.predictor.rounds.edit', $round)
            ->with('status', 'Predictor round updated successfully.');
    }

    public function transition(Request $request, PredictorRound $round, AdminPredictorManagementService $service): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['draft', 'open', 'locked', 'scoring', 'completed', 'cancelled'])],
        ]);

        try {
            $service->transitionRound($round, $validated['status']);
        } catch (ApiException $exception) {
            return back()->withErrors(['round' => $exception->getMessage()]);
        }

        return redirect()
            ->route('admin.predictor.rounds.edit', $round)
            ->with('status', 'Round status updated successfully.');
    }

    public function score(PredictorRound $round, AdminPredictorManagementService $service): RedirectResponse
    {
        try {
            $summary = $service->scoreRound($round, false);
        } catch (ApiException $exception) {
            return back()->withErrors(['round' => $exception->getMessage()]);
        }

        return redirect()
            ->route('admin.predictor.rounds.edit', $round)
            ->with('status', sprintf(
                'Round scored successfully. %d entries updated. Leaderboards refreshed for round, month %s, season, and all-time.',
                $summary['entries_scored'],
                $summary['monthly_key']
            ));
    }

    public function recalculate(PredictorRound $round, AdminPredictorManagementService $service): RedirectResponse
    {
        try {
            $summary = $service->scoreRound($round, true);
        } catch (ApiException $exception) {
            return back()->withErrors(['round' => $exception->getMessage()]);
        }

        return redirect()
            ->route('admin.predictor.rounds.edit', $round)
            ->with('status', sprintf(
                'Round recalculated successfully. %d entries updated. Leaderboards refreshed for round, month %s, season, and all-time.',
                $summary['entries_scored'],
                $summary['monthly_key']
            ));
    }

    private function formData(PredictorSeason $season, ?PredictorRound $round = null): array
    {
        $existingFixtures = $round?->fixtures ?? collect();
        $fixtureRows = $existingFixtures->map(fn ($fixture) => [
            'id' => $fixture->id,
            'display_order' => $fixture->display_order,
            'source_fixture_id' => $fixture->source_fixture_id,
            'competition_id' => $fixture->competition_id,
            'competition_name_snapshot' => $fixture->competition_name_snapshot,
            'home_team_id' => $fixture->home_team_id,
            'away_team_id' => $fixture->away_team_id,
            'home_team_name_snapshot' => $fixture->home_team_name_snapshot,
            'away_team_name_snapshot' => $fixture->away_team_name_snapshot,
            'home_team_logo_url' => $fixture->home_team_logo_url,
            'away_team_logo_url' => $fixture->away_team_logo_url,
            'kickoff_at' => $fixture->kickoff_at?->format('Y-m-d\\TH:i'),
            'result_status' => $fixture->result_status,
            'actual_home_score' => $fixture->actual_home_score,
            'actual_away_score' => $fixture->actual_away_score,
            'result_source' => $fixture->result_source,
        ]);

        $fixtureTarget = max(
            $season->campaign->default_fixture_count,
            $fixtureRows->count(),
            4
        );

        while ($fixtureRows->count() < $fixtureTarget) {
            $fixtureRows->push([
                'id' => null,
                'display_order' => $fixtureRows->count() + 1,
                'source_fixture_id' => null,
                'competition_id' => null,
                'competition_name_snapshot' => '',
                'home_team_id' => null,
                'away_team_id' => null,
                'home_team_name_snapshot' => '',
                'away_team_name_snapshot' => '',
                'home_team_logo_url' => '',
                'away_team_logo_url' => '',
                'kickoff_at' => '',
                'result_status' => 'pending',
                'actual_home_score' => null,
                'actual_away_score' => null,
                'result_source' => '',
            ]);
        }

        return [
            'name' => $round?->name ?? 'Round '.(($season->rounds()->max('round_number') ?? 0) + 1),
            'round_number' => $round?->round_number ?? (($season->rounds()->max('round_number') ?? 0) + 1),
            'opens_at' => $round?->opens_at?->format('Y-m-d\\TH:i') ?? now()->addHour()->format('Y-m-d\\TH:i'),
            'prediction_closes_at' => $round?->prediction_closes_at?->format('Y-m-d\\TH:i') ?? now()->addDay()->format('Y-m-d\\TH:i'),
            'round_closes_at' => $round?->round_closes_at?->format('Y-m-d\\TH:i') ?? now()->addDays(2)->format('Y-m-d\\TH:i'),
            'status' => $round?->status ?? 'draft',
            'allow_partial_submission' => $round?->allow_partial_submission ?? false,
            'notes' => $round?->notes ?? '',
            'fixtures' => $fixtureRows->all(),
        ];
    }
}
