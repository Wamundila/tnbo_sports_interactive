<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpsertPredictorCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'banker_enabled' => $this->boolean('banker_enabled'),
        ]);
    }

    public function rules(): array
    {
        $campaignId = $this->route('campaign')?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:100', 'alpha_dash', Rule::unique('predictor_campaigns', 'slug')->ignore($campaignId)],
            'display_name' => ['required', 'string', 'max:255'],
            'sponsor_name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'scope_type' => ['required', Rule::in(['single_competition', 'multi_competition', 'curated'])],
            'default_fixture_count' => ['required', 'integer', 'min:1', 'max:50'],
            'banker_enabled' => ['nullable', 'boolean'],
            'status' => ['required', Rule::in(['draft', 'active', 'archived'])],
            'visibility' => ['required', Rule::in(['public', 'private'])],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ];
    }
}
