<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpsertPredictorRoundRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $fixtures = collect($this->input('fixtures', []))
            ->values()
            ->map(function (array $fixture, int $index): array {
                return [
                    'id' => $fixture['id'] ?? null,
                    'display_order' => $fixture['display_order'] ?? ($index + 1),
                    'source_fixture_id' => $fixture['source_fixture_id'] ?? null,
                    'competition_id' => $fixture['competition_id'] ?? null,
                    'competition_name_snapshot' => $fixture['competition_name_snapshot'] ?? null,
                    'home_team_id' => $fixture['home_team_id'] ?? null,
                    'away_team_id' => $fixture['away_team_id'] ?? null,
                    'home_team_name_snapshot' => $fixture['home_team_name_snapshot'] ?? null,
                    'away_team_name_snapshot' => $fixture['away_team_name_snapshot'] ?? null,
                    'home_team_logo_url' => $fixture['home_team_logo_url'] ?? null,
                    'away_team_logo_url' => $fixture['away_team_logo_url'] ?? null,
                    'kickoff_at' => $fixture['kickoff_at'] ?? null,
                    'result_status' => $fixture['result_status'] ?? 'pending',
                    'actual_home_score' => ($fixture['actual_home_score'] ?? '') === '' ? null : $fixture['actual_home_score'],
                    'actual_away_score' => ($fixture['actual_away_score'] ?? '') === '' ? null : $fixture['actual_away_score'],
                    'result_source' => $fixture['result_source'] ?? null,
                ];
            })
            ->filter(function (array $fixture): bool {
                return collect([
                    $fixture['competition_name_snapshot'],
                    $fixture['home_team_name_snapshot'],
                    $fixture['away_team_name_snapshot'],
                    $fixture['kickoff_at'],
                ])->filter(fn ($value) => filled($value))->isNotEmpty();
            })
            ->values()
            ->all();

        $this->merge([
            'allow_partial_submission' => $this->boolean('allow_partial_submission'),
            'fixtures' => $fixtures,
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'round_number' => ['nullable', 'integer', 'min:1', 'max:500'],
            'opens_at' => ['required', 'date'],
            'prediction_closes_at' => ['required', 'date', 'after:opens_at'],
            'round_closes_at' => ['required', 'date', 'after:prediction_closes_at'],
            'status' => ['required', Rule::in(['draft', 'open', 'locked', 'scoring', 'completed', 'cancelled'])],
            'allow_partial_submission' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
            'fixtures' => ['nullable', 'array', 'max:50'],
            'fixtures.*.id' => ['nullable', 'integer', 'exists:predictor_round_fixtures,id'],
            'fixtures.*.display_order' => ['required', 'integer', 'min:1', 'distinct'],
            'fixtures.*.source_fixture_id' => ['nullable', 'integer', 'min:1'],
            'fixtures.*.competition_id' => ['nullable', 'integer', 'min:1'],
            'fixtures.*.competition_name_snapshot' => ['required', 'string', 'max:255'],
            'fixtures.*.home_team_id' => ['nullable', 'integer', 'min:1'],
            'fixtures.*.away_team_id' => ['nullable', 'integer', 'min:1'],
            'fixtures.*.home_team_name_snapshot' => ['required', 'string', 'max:255'],
            'fixtures.*.away_team_name_snapshot' => ['required', 'string', 'max:255'],
            'fixtures.*.home_team_logo_url' => ['nullable', 'url'],
            'fixtures.*.away_team_logo_url' => ['nullable', 'url'],
            'fixtures.*.kickoff_at' => ['required', 'date'],
            'fixtures.*.result_status' => ['required', Rule::in(['pending', 'live', 'completed', 'postponed', 'cancelled'])],
            'fixtures.*.actual_home_score' => ['nullable', 'integer', 'min:0', 'max:99'],
            'fixtures.*.actual_away_score' => ['nullable', 'integer', 'min:0', 'max:99'],
            'fixtures.*.result_source' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            foreach ($this->validatedFixtures() as $index => $fixture) {
                if ($fixture['result_status'] === 'completed' && ($fixture['actual_home_score'] === null || $fixture['actual_away_score'] === null)) {
                    $validator->errors()->add("fixtures.$index.actual_home_score", 'Completed fixtures require both final scores.');
                }
            }
        });
    }

    private function validatedFixtures(): array
    {
        return $this->input('fixtures', []);
    }
}
