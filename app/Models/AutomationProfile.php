<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutomationProfile extends Model
{
    protected $fillable = [
        'user_id', 'is_active', 'job_titles', 'areas', 'seniorities', 'technologies',
        'excluded_keywords', 'work_modes', 'platforms', 'employment_types',
        'easy_apply_only', 'locations', 'minimum_score',
        'resume_text', 'resume_data', 'resume_parse_status', 'resume_parse_error',
        'resume_updated_at', 'resume_parsed_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'job_titles' => 'array',
            'areas' => 'array',
            'seniorities' => 'array',
            'technologies' => 'array',
            'excluded_keywords' => 'array',
            'work_modes' => 'array',
            'platforms' => 'array',
            'employment_types' => 'array',
            'easy_apply_only' => 'boolean',
            'locations' => 'array',
            'minimum_score' => 'integer',
            'resume_text' => 'encrypted',
            'resume_data' => 'encrypted:array',
            'resume_updated_at' => 'datetime',
            'resume_parsed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isReady(): bool
    {
        return filled($this->resume_text)
            && $this->resume_parse_status === 'ready'
            && is_array($this->resume_data)
            && $this->seniorities !== []
            && ($this->job_titles !== [] || $this->technologies !== []);
    }
}
