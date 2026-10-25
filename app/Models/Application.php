<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Application extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'job_offer_id', 'candidate_id', 'stage', 'rating', 'rejection_reason',
        'applied_at', 'stage_changed_at', 'created_by', 'match_score', 'match_details', 'channel', 'cover_letter_path',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'applied_at' => 'datetime',
            'stage_changed_at' => 'datetime',
            'match_score' => 'integer',
            'match_details' => 'array',
        ];
    }

    public function jobOffer(): BelongsTo
    {
        return $this->belongsTo(JobOffer::class);
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(ApplicationEvent::class)->latest('id');
    }

    public function stageLabel(): string
    {
        return config("recruitment.stages.{$this->stage}.label", $this->stage);
    }
}
