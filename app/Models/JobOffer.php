<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Support\CandidateProfile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobOffer extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'reference', 'title', 'department_id', 'position_id', 'profile_category', 'contract_type',
        'description', 'requirements', 'min_education_level', 'min_experience_years', 'vacancies',
        'status', 'published_at', 'closes_at', 'created_by', 'is_public', 'required_skills',
    ];

    protected function casts(): array
    {
        return [
            'min_experience_years' => 'float',
            'vacancies' => 'integer',
            'published_at' => 'datetime',
            'closes_at' => 'date',
            'is_public' => 'boolean',
            'required_skills' => 'array',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    public function statusLabel(): string
    {
        return config("recruitment.offer_statuses.{$this->status}", $this->status);
    }

    /** Le profil répond-il aux critères de l'offre (domaine, études minimales, expérience minimale) ? */
    public function matches(Candidate $c): bool
    {
        return (! $this->profile_category || $this->profile_category === $c->profile_category)
            && CandidateProfile::educationRank($c->education_level) >= CandidateProfile::educationRank($this->min_education_level)
            && (float) $c->experience_years >= (float) $this->min_experience_years;
    }
}
