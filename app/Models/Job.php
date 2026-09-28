<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Job extends Model
{
    protected $table = 'opportunities';

    protected $fillable = [
        'user_id', 'source_job_id', 'fingerprint', 'level', 'title', 'company', 'location',
        'work_mode', 'url', 'score', 'status', 'payload', 'discovered_at',
        'analysis_state', 'sent_at', 'viewed_at', 'push_notified_at', 'resume_latex',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'resume_latex' => 'encrypted',
            'discovered_at' => 'datetime',
            'sent_at' => 'datetime',
            'viewed_at' => 'datetime',
            'push_notified_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sourceJob(): BelongsTo
    {
        return $this->belongsTo(SourceJob::class);
    }
}
