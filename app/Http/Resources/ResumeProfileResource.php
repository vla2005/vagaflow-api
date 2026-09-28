<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ResumeProfileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'status' => $this->resume_parse_status,
            'error' => $this->resume_parse_error,
            'parsed_at' => $this->resume_parsed_at,
            'profile' => $this->resume_data,
        ];
    }
}
