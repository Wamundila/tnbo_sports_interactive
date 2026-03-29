<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpsertPredictorSeasonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_current' => $this->boolean('is_current'),
        ]);
    }

    public function rules(): array
    {
        $seasonId = $this->route('season')?->id;
        $campaignId = $this->route('campaign')?->id ?? $this->route('season')?->campaign_id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:100',
                'alpha_dash',
                Rule::unique('predictor_seasons', 'slug')
                    ->where(fn ($query) => $query->where('campaign_id', $campaignId))
                    ->ignore($seasonId),
            ],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'status' => ['required', Rule::in(['draft', 'active', 'completed', 'archived'])],
            'rules_text' => ['nullable', 'string'],
            'is_current' => ['nullable', 'boolean'],
            'scoring_outcome_points' => ['required', 'numeric', 'min:0', 'max:100'],
            'scoring_exact_score_points' => ['required', 'numeric', 'min:0', 'max:100'],
            'scoring_close_score_points' => ['required', 'numeric', 'min:0', 'max:100'],
            'scoring_banker_multiplier' => ['required', 'numeric', 'min:1', 'max:20'],
        ];
    }
}
