<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JobSummaryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'level' => $this->level,
            'title' => $this->title,
            'company' => $this->company,
            'location' => $this->location,
            'work_mode' => $this->work_mode,
            'url' => $this->url,
            'score' => $this->score,
            'status' => $this->status,
            'has_personalized_resume' => filled($this->resume_latex),
            'viewed' => filled($this->viewed_at),
            'discovered_at' => $this->discovered_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
