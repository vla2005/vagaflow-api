<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SourceJob extends Model
{
    protected $fillable = [
        'source', 'source_key', 'level', 'area', 'title', 'company', 'location',
        'work_mode', 'platform', 'employment_type', 'is_easy_apply',
        'is_ai_verified', 'is_closed', 'url', 'payload', 'last_seen_at',
    ];

    protected function casts(): array
    {
        return [
            'is_easy_apply' => 'boolean',
            'is_ai_verified' => 'boolean',
            'is_closed' => 'boolean',
            'payload' => 'array',
            'last_seen_at' => 'datetime',
        ];
    }

    public function opportunities(): HasMany
    {
        return $this->hasMany(Job::class);
    }
}
