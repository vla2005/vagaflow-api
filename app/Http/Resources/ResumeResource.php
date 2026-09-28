<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ResumeResource extends JsonResource
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
            'has_resume' => filled($resumeText),
            'resume_characters' => mb_strlen($resumeText),
            'resume_preview' => filled($resumeText) ? mb_substr($resumeText, 0, 600) : null,
            'resume_updated_at' => $this->resume_updated_at,
            'resume_parse_status' => $this->resume_parse_status,
            'resume_parse_error' => $this->resume_parse_error,
            'resume_parsed_at' => $this->resume_parsed_at,
        ];
    }
}
