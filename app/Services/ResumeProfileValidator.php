<?php

namespace App\Services;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use RuntimeException;

class ResumeProfileValidator
{
    public const OPTIONAL_SECTION_TYPES = [
        'certifications',
        'courses',
        'publications',
        'awards',
        'volunteering',
        'additional_information',
    ];

    /** @return array<string, array<int, mixed>> */
    public static function rules(): array
    {
        return [
            'identity' => ['required', 'array'],
            'identity.name' => ['required', 'string', 'max:120'],
            'identity.title' => ['nullable', 'string', 'max:160'],
            'identity.location' => ['nullable', 'string', 'max:160'],
            'identity.phone' => ['nullable', 'string', 'max:60'],
            'identity.email' => ['nullable', 'string', 'max:160'],
            'identity.links' => ['present', 'array', 'max:10'],
            'identity.links.*' => ['string', 'max:255'],
            'summary' => ['required', 'string', 'max:2000'],
            'skills' => ['present', 'array', 'max:20'],
            'skills.*.category' => ['required', 'string', 'max:100'],
            'skills.*.items' => ['required', 'array', 'max:40'],
            'skills.*.items.*' => ['string', 'max:100'],
            'experiences' => ['present', 'array', 'max:20'],
            'experiences.*.organization' => ['required', 'string', 'max:180'],
            'experiences.*.role' => ['required', 'string', 'max:180'],
            'experiences.*.location' => ['nullable', 'string', 'max:160'],
            'experiences.*.period' => ['required', 'string', 'max:100'],
            'experiences.*.bullets' => ['required', 'array', 'max:12'],
            'experiences.*.bullets.*' => ['string', 'max:700'],
            'projects' => ['present', 'array', 'max:20'],
            'projects.*.name' => ['required', 'string', 'max:180'],
            'projects.*.period' => ['nullable', 'string', 'max:100'],
            'projects.*.link' => ['nullable', 'string', 'max:255'],
            'projects.*.bullets' => ['required', 'array', 'max:12'],
            'projects.*.bullets.*' => ['string', 'max:700'],
            'education' => ['present', 'array', 'max:15'],
            'education.*.institution' => ['required', 'string', 'max:180'],
            'education.*.course' => ['required', 'string', 'max:180'],
            'education.*.location' => ['nullable', 'string', 'max:160'],
            'education.*.period' => ['nullable', 'string', 'max:100'],
            'education.*.details' => ['present', 'array', 'max:8'],
            'education.*.details.*' => ['string', 'max:500'],
            'optional_sections' => ['present', 'array', 'max:10'],
            'optional_sections.*.type' => ['required', 'string', Rule::in(self::OPTIONAL_SECTION_TYPES)],
            'optional_sections.*.title' => ['required', 'string', 'max:100'],
            'optional_sections.*.items' => ['required', 'array', 'max:20'],
            'optional_sections.*.items.*.heading' => ['required', 'string', 'max:220'],
            'optional_sections.*.items.*.subheading' => ['nullable', 'string', 'max:220'],
            'optional_sections.*.items.*.period' => ['nullable', 'string', 'max:100'],
            'optional_sections.*.items.*.details' => ['present', 'array', 'max:8'],
            'optional_sections.*.items.*.details.*' => ['string', 'max:500'],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function validate(array $data): array
    {
        $validator = Validator::make($data, self::rules());

        if ($validator->fails()) {
            throw new RuntimeException('Gemini retornou um perfil de currículo inválido: '.$validator->errors()->first());
        }

        $validated = $validator->validated();
        $validated['optional_sections'] = collect($validated['optional_sections'])
            ->filter(fn (array $section): bool => $section['items'] !== [])
            ->values()
            ->all();

        return $validated;
    }
}
