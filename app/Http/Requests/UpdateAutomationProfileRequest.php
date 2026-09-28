<?php

namespace App\Http\Requests;

use App\Support\OpportunityTaxonomy;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAutomationProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'job_titles' => ['required', 'array', 'max:20'],
            'job_titles.*' => ['string', 'max:80'],
            'areas' => ['sometimes', 'array', 'max:12'],
            'areas.*' => ['string', Rule::in(OpportunityTaxonomy::AREAS)],
            'seniorities' => ['required', 'array', 'min:1'],
            'seniorities.*' => ['string', Rule::in(OpportunityTaxonomy::LEVELS)],
            'technologies' => ['required', 'array', 'max:40'],
            'technologies.*' => ['string', 'max:60'],
            'excluded_keywords' => ['required', 'array', 'max:40'],
            'excluded_keywords.*' => ['string', 'max:80'],
            'work_modes' => ['required', 'array', 'min:1'],
            'work_modes.*' => ['string', Rule::in(['remoto', 'hibrido', 'presencial'])],
            'platforms' => ['sometimes', 'array', 'max:10'],
            'platforms.*' => ['string', Rule::in(OpportunityTaxonomy::PLATFORMS)],
            'employment_types' => ['sometimes', 'array', 'max:2'],
            'employment_types.*' => ['string', Rule::in(OpportunityTaxonomy::EMPLOYMENT_TYPES)],
            'easy_apply_only' => ['sometimes', 'boolean'],
            'locations' => ['required', 'array', 'max:20'],
            'locations.*' => ['string', 'max:80'],
            'minimum_score' => ['required', 'integer', 'between:50,100'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
