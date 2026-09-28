<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AutomationProfileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $resumeText = (string) $this->resume_text;

        return [
            'is_active' => $this->is_active,
            'is_ready' => $this->isReady(),
            'job_titles' => $this->job_titles,
            'areas' => $this->areas,
            'seniorities' => $this->seniorities,
            'technologies' => $this->technologies,
            'excluded_keywords' => $this->excluded_keywords,
            'work_modes' => $this->work_modes,
            'platforms' => $this->platforms,
            'employment_types' => $this->employment_types,
            'easy_apply_only' => $this->easy_apply_only,
            'locations' => $this->locations,
            'minimum_score' => $this->minimum_score,
            'has_resume' => filled($resumeText),
            'resume_characters' => mb_strlen($resumeText),
            'resume_preview' => filled($resumeText) ? mb_substr($resumeText, 0, 600) : null,
            'resume_updated_at' => $this->resume_updated_at,
            'resume_parse_status' => $this->resume_parse_status,
            'resume_parse_error' => $this->resume_parse_error,
            'resume_parsed_at' => $this->resume_parsed_at,
            'resume_sections' => $this->resume_data ? [
                'skills' => count($this->resume_data['skills'] ?? []),
                'experiences' => count($this->resume_data['experiences'] ?? []),
                'projects' => count($this->resume_data['projects'] ?? []),
                'education' => count($this->resume_data['education'] ?? []),
                'optional' => collect($this->resume_data['optional_sections'] ?? [])->pluck('title')->values(),
            ] : null,
        ];
    }
}
