<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Support\CandidateProfile;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Candidate extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'first_name', 'last_name', 'email', 'phone', 'city', 'education_level', 'last_degree',
        'degree_field', 'school', 'graduation_year', 'experience_years', 'current_job_title',
        'profile_category', 'skills', 'source', 'cv_path', 'cv_original_name', 'status', 'notes',
        'consent_given_at', 'retention_until', 'created_by', 'bank_consent',
    ];

    protected function casts(): array
    {
        return [
            'skills' => 'array',
            'experience_years' => 'float',
            'graduation_year' => 'integer',
            'consent_given_at' => 'datetime',
            'retention_until' => 'date',
            'bank_consent' => 'boolean',
        ];
    }

    protected function fullName(): Attribute
    {
        return Attribute::get(fn () => trim($this->first_name.' '.$this->last_name));
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    public function seniorityKey(): ?string
    {
        return CandidateProfile::seniority($this->experience_years);
    }

    public function seniorityLabel(): string
    {
        return CandidateProfile::seniorityLabel($this->experience_years);
    }

    public function educationLabel(): string
    {
        return CandidateProfile::educationLabel($this->education_level);
    }
}
