<?php

// @formatter:off
// phpcs:ignoreFile
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */


namespace App\Models{
/**
 * @property int $id
 * @property int $company_id
 * @property int $employee_id
 * @property \Illuminate\Support\Carbon $start_date
 * @property \Illuminate\Support\Carbon $end_date
 * @property float $days
 * @property bool $justified
 * @property string|null $reason
 * @property float $deducted_days
 * @property \Illuminate\Support\Carbon|null $justified_at
 * @property int|null $recorded_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Company $company
 * @property-read \App\Models\Employee|null $employee
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Absence newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Absence newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Absence query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Absence whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Absence whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Absence whereDays($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Absence whereDeductedDays($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Absence whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Absence whereEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Absence whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Absence whereJustified($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Absence whereJustifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Absence whereReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Absence whereRecordedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Absence whereStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Absence whereUpdatedAt($value)
 */
	class Absence extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $company_id
 * @property int $employee_id
 * @property string $date
 * @property string|null $start_time
 * @property string|null $end_time
 * @property numeric|null $hours
 * @property string $reason
 * @property string $status
 * @property int|null $approved_by
 * @property string|null $decided_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AbsencePermission newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AbsencePermission newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AbsencePermission query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AbsencePermission whereApprovedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AbsencePermission whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AbsencePermission whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AbsencePermission whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AbsencePermission whereDecidedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AbsencePermission whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AbsencePermission whereEndTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AbsencePermission whereHours($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AbsencePermission whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AbsencePermission whereReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AbsencePermission whereStartTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AbsencePermission whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AbsencePermission whereUpdatedAt($value)
 */
	class AbsencePermission extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $company_id
 * @property int $job_offer_id
 * @property int $candidate_id
 * @property string $stage
 * @property int|null $rating
 * @property int|null $match_score
 * @property array<array-key, mixed>|null $match_details
 * @property string $channel
 * @property string|null $cover_letter_path
 * @property string|null $rejection_reason
 * @property \Illuminate\Support\Carbon|null $applied_at
 * @property \Illuminate\Support\Carbon|null $stage_changed_at
 * @property int|null $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Candidate $candidate
 * @property-read \App\Models\Company $company
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ApplicationEvent> $events
 * @property-read int|null $events_count
 * @property-read \App\Models\JobOffer $jobOffer
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Application newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Application newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Application query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Application whereAppliedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Application whereCandidateId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Application whereChannel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Application whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Application whereCoverLetterPath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Application whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Application whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Application whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Application whereJobOfferId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Application whereMatchDetails($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Application whereMatchScore($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Application whereRating($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Application whereRejectionReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Application whereStage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Application whereStageChangedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Application whereUpdatedAt($value)
 */
	class Application extends \Eloquent {}
}

namespace App\Models{
/**
 * Historique d'une candidature : on y accède toujours via Application (déjà filtrée par entreprise).
 *
 * @property int $id
 * @property int $application_id
 * @property string|null $from_stage
 * @property string $to_stage
 * @property string|null $note
 * @property int|null $user_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApplicationEvent newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApplicationEvent newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApplicationEvent query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApplicationEvent whereApplicationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApplicationEvent whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApplicationEvent whereFromStage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApplicationEvent whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApplicationEvent whereNote($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApplicationEvent whereToStage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApplicationEvent whereUserId($value)
 */
	class ApplicationEvent extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $company_id
 * @property string $approvable_type
 * @property int $approvable_id
 * @property int $position
 * @property string $kind
 * @property string $status
 * @property int|null $decided_by
 * @property string|null $acted_as
 * @property string|null $comment
 * @property \Illuminate\Support\Carbon|null $decided_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Company $company
 * @property-read \App\Models\User|null $decider
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalStep newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalStep newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalStep query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalStep whereActedAs($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalStep whereApprovableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalStep whereApprovableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalStep whereComment($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalStep whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalStep whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalStep whereDecidedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalStep whereDecidedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalStep whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalStep whereKind($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalStep wherePosition($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalStep whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalStep whereUpdatedAt($value)
 */
	class ApprovalStep extends \Eloquent {}
}

namespace App\Models{
/**
 * Poste occupé par un employé en plus (ou à la place) de son poste principal : intérim, détachement dans un autre
 * département ou une autre base, ou cumul de deux postes.
 *
 * @property int $id
 * @property int $company_id
 * @property int $employee_id
 * @property string $type
 * @property int $department_id
 * @property int $position_id
 * @property int|null $work_site_id
 * @property int|null $reports_to_employee_id
 * @property \Illuminate\Support\Carbon $start_date
 * @property \Illuminate\Support\Carbon|null $end_date
 * @property int|null $allocation_percent
 * @property float|null $allowance_amount
 * @property string $allowance_basis
 * @property bool $acts_as_head
 * @property bool $manager_validates
 * @property string|null $reason
 * @property int|null $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Company $company
 * @property-read \App\Models\Department $department
 * @property-read \App\Models\Employee|null $employee
 * @property-read \App\Models\Position $position
 * @property-read \App\Models\Employee|null $reportsTo
 * @property-read \App\Models\WorkSite|null $site
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Assignment active(?\Carbon\Carbon $on = null)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Assignment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Assignment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Assignment query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Assignment whereActsAsHead($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Assignment whereAllocationPercent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Assignment whereAllowanceAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Assignment whereAllowanceBasis($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Assignment whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Assignment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Assignment whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Assignment whereDepartmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Assignment whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Assignment whereEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Assignment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Assignment whereManagerValidates($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Assignment wherePositionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Assignment whereReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Assignment whereReportsToEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Assignment whereStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Assignment whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Assignment whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Assignment whereWorkSiteId($value)
 */
	class Assignment extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int|null $company_id
 * @property int|null $user_id
 * @property string $action
 * @property string $table_name
 * @property int|null $record_id
 * @property string|null $changes
 * @property string|null $ip_address
 * @property \Illuminate\Support\Carbon $created_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditLog query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditLog whereAction($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditLog whereChanges($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditLog whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditLog whereIpAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditLog whereRecordId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditLog whereTableName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditLog whereUserId($value)
 */
	class AuditLog extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $company_id
 * @property string $first_name
 * @property string $last_name
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $city
 * @property string $education_level
 * @property string|null $last_degree
 * @property string|null $degree_field
 * @property string|null $school
 * @property int|null $graduation_year
 * @property float $experience_years
 * @property string|null $current_job_title
 * @property string|null $profile_category
 * @property array<array-key, mixed>|null $skills
 * @property string|null $source
 * @property string|null $cv_path
 * @property string|null $cv_original_name
 * @property string $status
 * @property int|null $employee_id
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $consent_given_at
 * @property \Illuminate\Support\Carbon|null $retention_until
 * @property bool $bank_consent
 * @property int|null $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Application> $applications
 * @property-read int|null $applications_count
 * @property-read \App\Models\Company $company
 * @property-read mixed $full_name
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate whereBankConsent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate whereCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate whereConsentGivenAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate whereCurrentJobTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate whereCvOriginalName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate whereCvPath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate whereDegreeField($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate whereEducationLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate whereExperienceYears($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate whereFirstName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate whereGraduationYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate whereLastDegree($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate whereLastName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate whereProfileCategory($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate whereRetentionUntil($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate whereSchool($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate whereSkills($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate whereSource($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate whereUpdatedAt($value)
 */
	class Candidate extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $company_id
 * @property string $name
 * @property string|null $description
 * @property int $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CandidateCategory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CandidateCategory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CandidateCategory query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CandidateCategory whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CandidateCategory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CandidateCategory whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CandidateCategory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CandidateCategory whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CandidateCategory whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CandidateCategory whereUpdatedAt($value)
 */
	class CandidateCategory extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $country
 * @property string $timezone
 * @property string $locale
 * @property string $currency
 * @property string|null $logo_path
 * @property string $status
 * @property string|null $trial_ends_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $career_slug
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Department> $departments
 * @property-read int|null $departments_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Employee> $employees
 * @property-read int|null $employees_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $users
 * @property-read int|null $users_count
 * @method static \Database\Factories\CompanyFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereCareerSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereCountry($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereCurrency($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereLocale($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereLogoPath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereTimezone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereTrialEndsAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereUpdatedAt($value)
 */
	class Company extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $company_id
 * @property int $employee_id
 * @property string $type
 * @property \Illuminate\Support\Carbon $start_date
 * @property \Illuminate\Support\Carbon|null $end_date
 * @property int $base_salary
 * @property string $currency
 * @property string|null $trial_end_date
 * @property int $renewal_number
 * @property int|null $previous_contract_id
 * @property string $status
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Company $company
 * @property-read \App\Models\Employee|null $employee
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contract newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contract newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contract query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contract whereBaseSalary($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contract whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contract whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contract whereCurrency($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contract whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contract whereEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contract whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contract whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contract wherePreviousContractId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contract whereRenewalNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contract whereStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contract whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contract whereTrialEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contract whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contract whereUpdatedAt($value)
 */
	class Contract extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $company_id
 * @property int $from_employee_id
 * @property int $to_employee_id
 * @property string $start_date
 * @property string $end_date
 * @property string|null $reason
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Delegation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Delegation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Delegation query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Delegation whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Delegation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Delegation whereEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Delegation whereFromEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Delegation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Delegation whereReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Delegation whereStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Delegation whereToEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Delegation whereUpdatedAt($value)
 */
	class Delegation extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $company_id
 * @property string $name
 * @property string $code
 * @property int|null $head_id
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Company $company
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Employee> $employees
 * @property-read int|null $employees_count
 * @property-read \App\Models\Employee|null $head
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Position> $positions
 * @property-read int|null $positions_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department whereHeadId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department whereUpdatedAt($value)
 */
	class Department extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $company_id
 * @property int $employee_id
 * @property int|null $document_template_id
 * @property string $type
 * @property string $title
 * @property string $file_path
 * @property string|null $issued_at
 * @property string|null $expires_at
 * @property int|null $uploaded_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Company $company
 * @property-read \App\Models\Employee|null $employee
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document whereDocumentTemplateId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document whereExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document whereFilePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document whereIssuedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document whereUploadedBy($value)
 */
	class Document extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $company_id
 * @property string $type
 * @property string $name
 * @property string $body
 * @property int $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentTemplate newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentTemplate newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentTemplate query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentTemplate whereBody($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentTemplate whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentTemplate whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentTemplate whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentTemplate whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentTemplate whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentTemplate whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentTemplate whereUpdatedAt($value)
 */
	class DocumentTemplate extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $company_id
 * @property string $matricule
 * @property string $first_name
 * @property string $last_name
 * @property string|null $gender
 * @property \Illuminate\Support\Carbon|null $birth_date
 * @property string|null $birth_place
 * @property string|null $nationality
 * @property bool $is_expatriate
 * @property string|null $marital_status
 * @property bool|null $spouse_works
 * @property int $children_count
 * @property string|null $national_id
 * @property string|null $work_permit_number
 * @property \Illuminate\Support\Carbon|null $work_permit_expires_at
 * @property string|null $phone
 * @property string|null $personal_email
 * @property string|null $work_email
 * @property string|null $address
 * @property string|null $emergency_contact_name
 * @property string|null $emergency_contact_phone
 * @property int $department_id
 * @property int $position_id
 * @property int|null $manager_id
 * @property \Illuminate\Support\Carbon $hire_date
 * @property string $status
 * @property string|null $photo_path
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property numeric|null $leave_days_per_month
 * @property string|null $leave_accrual_from
 * @property int|null $work_schedule_id
 * @property int|null $work_site_id
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\AbsencePermission> $absencePermissions
 * @property-read int|null $absence_permissions_count
 * @property-read \App\Models\Company $company
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Contract> $contracts
 * @property-read int|null $contracts_count
 * @property-read \App\Models\Department $department
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Document> $documents
 * @property-read int|null $documents_count
 * @property-read mixed $full_name
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\LeaveBalance> $leaveBalances
 * @property-read int|null $leave_balances_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\LeaveRequest> $leaveRequests
 * @property-read int|null $leave_requests_count
 * @property-read Employee|null $manager
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\MissionOrderEmployee> $missionOrderEmployees
 * @property-read int|null $mission_order_employees_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Payslip> $payslips
 * @property-read int|null $payslips_count
 * @property-read \App\Models\Position $position
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Employee> $subordinates
 * @property-read int|null $subordinates_count
 * @property-read \App\Models\Termination|null $termination
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee visibleTo(\App\Models\User $user)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereBirthDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereBirthPlace($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereChildrenCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereDepartmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereEmergencyContactName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereEmergencyContactPhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereFirstName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereGender($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereHireDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereIsExpatriate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereLastName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereLeaveAccrualFrom($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereLeaveDaysPerMonth($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereManagerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereMaritalStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereMatricule($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereNationalId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereNationality($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee wherePersonalEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee wherePhotoPath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee wherePositionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereSpouseWorks($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereWorkEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereWorkPermitExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereWorkPermitNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereWorkScheduleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereWorkSiteId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee withoutTrashed()
 */
	class Employee extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $company_id
 * @property string $reference
 * @property int $employee_id
 * @property int|null $issued_by_user_id
 * @property string $signatory_name
 * @property string|null $signatory_title
 * @property string $subject
 * @property string $body
 * @property string|null $copies
 * @property int $response_days
 * @property \Illuminate\Support\Carbon $response_deadline
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $employee_viewed_at
 * @property \Illuminate\Support\Carbon|null $responded_at
 * @property string|null $response_body
 * @property string|null $response_attachment_path
 * @property string|null $response_attachment_name
 * @property string|null $decision_type
 * @property string|null $decision_subject
 * @property string|null $decision_body
 * @property \Illuminate\Support\Carbon|null $decided_at
 * @property int|null $decided_by_user_id
 * @property \Illuminate\Support\Carbon|null $suspension_start
 * @property \Illuminate\Support\Carbon|null $suspension_end
 * @property bool $suspension_paid
 * @property \Illuminate\Support\Carbon|null $dismissal_effective_date
 * @property string|null $withdrawn_reason
 * @property \Illuminate\Support\Carbon|null $reminder_sent_at
 * @property \Illuminate\Support\Carbon|null $overdue_notified_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Company $company
 * @property-read \App\Models\User|null $decider
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ExplanationDocument> $documents
 * @property-read int|null $documents_count
 * @property-read \App\Models\Employee|null $employee
 * @property-read \App\Models\User|null $issuer
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExplanationCase newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExplanationCase newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExplanationCase query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExplanationCase whereBody($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExplanationCase whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExplanationCase whereCopies($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExplanationCase whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExplanationCase whereDecidedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExplanationCase whereDecidedByUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExplanationCase whereDecisionBody($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExplanationCase whereDecisionSubject($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExplanationCase whereDecisionType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExplanationCase whereDismissalEffectiveDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExplanationCase whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExplanationCase whereEmployeeViewedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExplanationCase whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExplanationCase whereIssuedByUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExplanationCase whereOverdueNotifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExplanationCase whereReference($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExplanationCase whereReminderSentAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExplanationCase whereRespondedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExplanationCase whereResponseAttachmentName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExplanationCase whereResponseAttachmentPath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExplanationCase whereResponseBody($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExplanationCase whereResponseDays($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExplanationCase whereResponseDeadline($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExplanationCase whereSignatoryName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExplanationCase whereSignatoryTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExplanationCase whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExplanationCase whereSubject($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExplanationCase whereSuspensionEnd($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExplanationCase whereSuspensionPaid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExplanationCase whereSuspensionStart($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExplanationCase whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExplanationCase whereWithdrawnReason($value)
 */
	class ExplanationCase extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $company_id
 * @property int $case_id
 * @property string $kind
 * @property string $reference
 * @property string $file_path
 * @property int|null $document_id
 * @property int|null $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property-read \App\Models\Company $company
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExplanationDocument newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExplanationDocument newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExplanationDocument query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExplanationDocument whereCaseId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExplanationDocument whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExplanationDocument whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExplanationDocument whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExplanationDocument whereDocumentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExplanationDocument whereFilePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExplanationDocument whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExplanationDocument whereKind($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExplanationDocument whereReference($value)
 */
	class ExplanationDocument extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $company_id
 * @property int $employee_id
 * @property string $subject
 * @property string $content
 * @property string $issued_at
 * @property string|null $due_at
 * @property string|null $response
 * @property string|null $responded_at
 * @property string $status
 * @property int|null $issued_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExplanationLetter newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExplanationLetter newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExplanationLetter query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExplanationLetter whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExplanationLetter whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExplanationLetter whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExplanationLetter whereDueAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExplanationLetter whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExplanationLetter whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExplanationLetter whereIssuedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExplanationLetter whereIssuedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExplanationLetter whereRespondedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExplanationLetter whereResponse($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExplanationLetter whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExplanationLetter whereSubject($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExplanationLetter whereUpdatedAt($value)
 */
	class ExplanationLetter extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $company_id
 * @property string $name
 * @property \Illuminate\Support\Carbon $date
 * @property int $is_recurring
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Company $company
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Holiday newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Holiday newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Holiday query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Holiday whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Holiday whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Holiday whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Holiday whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Holiday whereIsRecurring($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Holiday whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Holiday whereUpdatedAt($value)
 */
	class Holiday extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $company_id
 * @property string $provider
 * @property string $policy_number
 * @property string|null $type
 * @property string $start_date
 * @property string|null $end_date
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Insurance newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Insurance newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Insurance query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Insurance whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Insurance whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Insurance whereEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Insurance whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Insurance whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Insurance wherePolicyNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Insurance whereProvider($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Insurance whereStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Insurance whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Insurance whereUpdatedAt($value)
 */
	class Insurance extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $company_id
 * @property int $employee_id
 * @property int $insurance_id
 * @property string|null $beneficiaries
 * @property string $start_date
 * @property string|null $end_date
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InsuranceEnrollment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InsuranceEnrollment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InsuranceEnrollment query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InsuranceEnrollment whereBeneficiaries($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InsuranceEnrollment whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InsuranceEnrollment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InsuranceEnrollment whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InsuranceEnrollment whereEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InsuranceEnrollment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InsuranceEnrollment whereInsuranceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InsuranceEnrollment whereStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InsuranceEnrollment whereUpdatedAt($value)
 */
	class InsuranceEnrollment extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $company_id
 * @property int $employee_id
 * @property string $school
 * @property string|null $field_of_study
 * @property int|null $supervisor_id
 * @property int $hired_after
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Internship newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Internship newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Internship query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Internship whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Internship whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Internship whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Internship whereFieldOfStudy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Internship whereHiredAfter($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Internship whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Internship whereSchool($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Internship whereSupervisorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Internship whereUpdatedAt($value)
 */
	class Internship extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $company_id
 * @property int $application_id
 * @property \Illuminate\Support\Carbon $scheduled_at
 * @property int $duration_minutes
 * @property string $mode
 * @property string|null $location
 * @property int|null $interviewer_id
 * @property string $status
 * @property string|null $feedback
 * @property int|null $score
 * @property int|null $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Application $application
 * @property-read \App\Models\Company $company
 * @property-read \App\Models\User|null $interviewer
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Interview newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Interview newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Interview query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Interview whereApplicationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Interview whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Interview whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Interview whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Interview whereDurationMinutes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Interview whereFeedback($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Interview whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Interview whereInterviewerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Interview whereLocation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Interview whereMode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Interview whereScheduledAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Interview whereScore($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Interview whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Interview whereUpdatedAt($value)
 */
	class Interview extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $company_id
 * @property int $candidate_id
 * @property int $job_opening_id
 * @property string $stage
 * @property string $applied_at
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobApplication newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobApplication newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobApplication query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobApplication whereAppliedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobApplication whereCandidateId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobApplication whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobApplication whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobApplication whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobApplication whereJobOpeningId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobApplication whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobApplication whereStage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobApplication whereUpdatedAt($value)
 */
	class JobApplication extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $company_id
 * @property string $reference
 * @property string $title
 * @property int|null $department_id
 * @property int|null $position_id
 * @property string|null $profile_category
 * @property string $contract_type
 * @property string|null $description
 * @property string|null $requirements
 * @property array<array-key, mixed>|null $required_skills
 * @property string|null $min_education_level
 * @property float $min_experience_years
 * @property int $vacancies
 * @property string $status
 * @property bool $is_public
 * @property \Illuminate\Support\Carbon|null $published_at
 * @property int|null $is_internal
 * @property int|null $is_external
 * @property \Illuminate\Support\Carbon|null $closes_at
 * @property int|null $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Application> $applications
 * @property-read int|null $applications_count
 * @property-read \App\Models\Company $company
 * @property-read \App\Models\Department|null $department
 * @property-read \App\Models\Position|null $position
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobOffer newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobOffer newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobOffer query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobOffer whereClosesAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobOffer whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobOffer whereContractType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobOffer whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobOffer whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobOffer whereDepartmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobOffer whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobOffer whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobOffer whereIsExternal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobOffer whereIsInternal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobOffer whereIsPublic($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobOffer whereMinEducationLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobOffer whereMinExperienceYears($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobOffer wherePositionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobOffer whereProfileCategory($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobOffer wherePublishedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobOffer whereReference($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobOffer whereRequiredSkills($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobOffer whereRequirements($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobOffer whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobOffer whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobOffer whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobOffer whereVacancies($value)
 */
	class JobOffer extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $company_id
 * @property int $position_id
 * @property string $title
 * @property string|null $description
 * @property int $openings_count
 * @property string $status
 * @property string $opened_at
 * @property string|null $closed_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobOpening newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobOpening newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobOpening query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobOpening whereClosedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobOpening whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobOpening whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobOpening whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobOpening whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobOpening whereOpenedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobOpening whereOpeningsCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobOpening wherePositionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobOpening whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobOpening whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobOpening whereUpdatedAt($value)
 */
	class JobOpening extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $company_id
 * @property int $employee_id
 * @property int $leave_type_id
 * @property int $year
 * @property numeric $entitled
 * @property numeric $carried_over
 * @property numeric $taken
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Company $company
 * @property-read \App\Models\Employee|null $employee
 * @property-read \App\Models\LeaveType $leaveType
 * @property-read mixed $remaining
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveBalance newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveBalance newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveBalance query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveBalance whereCarriedOver($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveBalance whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveBalance whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveBalance whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveBalance whereEntitled($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveBalance whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveBalance whereLeaveTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveBalance whereTaken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveBalance whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveBalance whereYear($value)
 */
	class LeaveBalance extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $company_id
 * @property int $employee_id
 * @property int $leave_type_id
 * @property \Illuminate\Support\Carbon $start_date
 * @property \Illuminate\Support\Carbon $end_date
 * @property float $days
 * @property string|null $reason
 * @property string $status
 * @property int|null $approved_by
 * @property \Illuminate\Support\Carbon|null $decided_at
 * @property string|null $decision_note
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property bool $start_half
 * @property bool $end_half
 * @property string|null $exception_reason
 * @property bool $exceeds_balance
 * @property float|null $balance_before
 * @property float|null $balance_after
 * @property int|null $interim_employee_id
 * @property string|null $attachment_path
 * @property string|null $attachment_name
 * @property int|null $document_id
 * @property int|null $created_by
 * @property-read \App\Models\Company $company
 * @property-read \App\Models\Document|null $document
 * @property-read \App\Models\Employee|null $employee
 * @property-read \App\Models\Employee|null $interim
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ApprovalStep> $steps
 * @property-read int|null $steps_count
 * @property-read \App\Models\LeaveType $type
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveRequest newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveRequest newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveRequest query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveRequest whereApprovedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveRequest whereAttachmentName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveRequest whereAttachmentPath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveRequest whereBalanceAfter($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveRequest whereBalanceBefore($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveRequest whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveRequest whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveRequest whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveRequest whereDays($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveRequest whereDecidedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveRequest whereDecisionNote($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveRequest whereDocumentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveRequest whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveRequest whereEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveRequest whereEndHalf($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveRequest whereExceedsBalance($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveRequest whereExceptionReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveRequest whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveRequest whereInterimEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveRequest whereLeaveTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveRequest whereReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveRequest whereStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveRequest whereStartHalf($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveRequest whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveRequest whereUpdatedAt($value)
 */
	class LeaveRequest extends \Eloquent {}
}

namespace App\Models{
/**
 * Paramètres de congé d'UNE entreprise (lus par identifiant d'entreprise : utilisable sans utilisateur connecté).
 *
 * @property int $id
 * @property int $company_id
 * @property float $negative_tolerance
 * @property float $default_monthly_days
 * @property array<array-key, mixed>|null $workdays
 * @property bool $deduct_unjustified
 * @property int $recovery_validity_days
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string $count_mode
 * @property string $accrual_mode
 * @property int $accrual_per_days
 * @property array<array-key, mixed>|null $seniority_bonuses
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveSetting newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveSetting newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveSetting query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveSetting whereAccrualMode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveSetting whereAccrualPerDays($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveSetting whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveSetting whereCountMode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveSetting whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveSetting whereDeductUnjustified($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveSetting whereDefaultMonthlyDays($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveSetting whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveSetting whereNegativeTolerance($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveSetting whereRecoveryValidityDays($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveSetting whereSeniorityBonuses($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveSetting whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveSetting whereWorkdays($value)
 */
	class LeaveSetting extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $company_id
 * @property string $name
 * @property numeric $days_per_year
 * @property int $is_paid
 * @property int $requires_approval
 * @property int $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $code
 * @property string|null $pool
 * @property bool $requires_document
 * @property float|null $max_days
 * @property bool $paid
 * @property bool $active
 * @property int $sort
 * @property-read \App\Models\Company $company
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveType newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveType newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveType query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveType whereActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveType whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveType whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveType whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveType whereDaysPerYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveType whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveType whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveType whereIsPaid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveType whereMaxDays($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveType whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveType wherePaid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveType wherePool($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveType whereRequiresApproval($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveType whereRequiresDocument($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveType whereSort($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveType whereUpdatedAt($value)
 */
	class LeaveType extends \Eloquent {}
}

namespace App\Models{
/**
 * Papier à en-tête d'UNE entreprise (lu par identifiant d'entreprise : utilisable sans utilisateur connecté).
 *
 * @property int $id
 * @property int $company_id
 * @property string $legal_name
 * @property string|null $address
 * @property string|null $city
 * @property string|null $phone
 * @property string|null $email
 * @property string|null $website
 * @property string|null $rccm
 * @property string|null $nif
 * @property string|null $logo_path
 * @property string|null $footer_text
 * @property int $default_response_days
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Letterhead newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Letterhead newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Letterhead query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Letterhead whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Letterhead whereCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Letterhead whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Letterhead whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Letterhead whereDefaultResponseDays($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Letterhead whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Letterhead whereFooterText($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Letterhead whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Letterhead whereLegalName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Letterhead whereLogoPath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Letterhead whereNif($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Letterhead wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Letterhead whereRccm($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Letterhead whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Letterhead whereWebsite($value)
 */
	class Letterhead extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $company_id
 * @property int $matricule_setting_id
 * @property string $scope_key
 * @property int $last_number
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MatriculeSequence newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MatriculeSequence newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MatriculeSequence query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MatriculeSequence whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MatriculeSequence whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MatriculeSequence whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MatriculeSequence whereLastNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MatriculeSequence whereMatriculeSettingId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MatriculeSequence whereScopeKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MatriculeSequence whereUpdatedAt($value)
 */
	class MatriculeSequence extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $company_id
 * @property string $pattern
 * @property string|null $prefix
 * @property string $counter_scope
 * @property int $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MatriculeSetting newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MatriculeSetting newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MatriculeSetting query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MatriculeSetting whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MatriculeSetting whereCounterScope($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MatriculeSetting whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MatriculeSetting whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MatriculeSetting whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MatriculeSetting wherePattern($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MatriculeSetting wherePrefix($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MatriculeSetting whereUpdatedAt($value)
 */
	class MatriculeSetting extends \Eloquent {}
}

namespace App\Models{
/**
 * @property mixed $status
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\MissionApproval> $approvals
 * @property-read int|null $approvals_count
 * @property-read \App\Models\Company|null $company
 * @property-read \App\Models\Department|null $department
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\MissionParticipant> $participants
 * @property-read int|null $participants_count
 * @property-read \App\Models\User|null $requester
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mission newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mission newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mission ongoingOn($date)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mission onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mission query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mission withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mission withoutTrashed()
 */
	class Mission extends \Eloquent {}
}

namespace App\Models{
/**
 * @property-read \App\Models\Mission|null $mission
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MissionApproval newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MissionApproval newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MissionApproval query()
 */
	class MissionApproval extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $company_id
 * @property string $reference
 * @property string $purpose
 * @property string|null $description
 * @property string|null $departure_place
 * @property string $destination
 * @property string|null $country
 * @property int $is_international
 * @property string $start_date
 * @property string $end_date
 * @property string|null $transport_mode
 * @property int $estimated_cost
 * @property string $currency
 * @property string $status
 * @property int $requested_by
 * @property int|null $approved_by
 * @property string|null $decided_at
 * @property string|null $decision_note
 * @property string|null $report
 * @property string|null $completed_at
 * @property int|null $created_by
 * @property string|null $file_path
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Employee|null $approver
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Employee> $employees
 * @property-read int|null $employees_count
 * @property-read \App\Models\Employee|null $requester
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\MissionOrderEmployee> $travelers
 * @property-read int|null $travelers_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MissionOrder newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MissionOrder newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MissionOrder query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MissionOrder whereApprovedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MissionOrder whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MissionOrder whereCompletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MissionOrder whereCountry($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MissionOrder whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MissionOrder whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MissionOrder whereCurrency($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MissionOrder whereDecidedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MissionOrder whereDecisionNote($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MissionOrder whereDeparturePlace($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MissionOrder whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MissionOrder whereDestination($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MissionOrder whereEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MissionOrder whereEstimatedCost($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MissionOrder whereFilePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MissionOrder whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MissionOrder whereIsInternational($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MissionOrder wherePurpose($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MissionOrder whereReference($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MissionOrder whereReport($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MissionOrder whereRequestedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MissionOrder whereStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MissionOrder whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MissionOrder whereTransportMode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MissionOrder whereUpdatedAt($value)
 */
	class MissionOrder extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $company_id
 * @property int $mission_order_id
 * @property int $employee_id
 * @property int $per_diem
 * @property int $advance_amount
 * @property int|null $actual_expenses
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Employee|null $employee
 * @property-read \App\Models\MissionOrder $missionOrder
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MissionOrderEmployee newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MissionOrderEmployee newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MissionOrderEmployee query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MissionOrderEmployee whereActualExpenses($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MissionOrderEmployee whereAdvanceAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MissionOrderEmployee whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MissionOrderEmployee whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MissionOrderEmployee whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MissionOrderEmployee whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MissionOrderEmployee whereMissionOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MissionOrderEmployee whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MissionOrderEmployee wherePerDiem($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MissionOrderEmployee whereUpdatedAt($value)
 */
	class MissionOrderEmployee extends \Eloquent {}
}

namespace App\Models{
/**
 * @property mixed $role
 * @property mixed $perdiem_mode
 * @property-read \App\Models\Department|null $department
 * @property-read \App\Models\Employee|null $employee
 * @property-read \App\Models\Mission|null $mission
 * @property-read \App\Models\Position|null $position
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MissionParticipant newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MissionParticipant newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MissionParticipant query()
 */
	class MissionParticipant extends \Eloquent {}
}

namespace App\Models{
/**
 * Jour de repos (ou férié) travaillé, à compenser par un jour de récupération ou un ajout au solde de congé.
 *
 * @property int $id
 * @property int $company_id
 * @property int $employee_id
 * @property \Illuminate\Support\Carbon $worked_on
 * @property float $days
 * @property string|null $reason
 * @property string $status
 * @property string|null $compensation
 * @property float|null $credited_days
 * @property \Illuminate\Support\Carbon|null $expires_on
 * @property \Illuminate\Support\Carbon|null $decided_at
 * @property int|null $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Company $company
 * @property-read \App\Models\Employee|null $employee
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OvertimeRecord newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OvertimeRecord newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OvertimeRecord query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OvertimeRecord whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OvertimeRecord whereCompensation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OvertimeRecord whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OvertimeRecord whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OvertimeRecord whereCreditedDays($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OvertimeRecord whereDays($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OvertimeRecord whereDecidedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OvertimeRecord whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OvertimeRecord whereExpiresOn($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OvertimeRecord whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OvertimeRecord whereReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OvertimeRecord whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OvertimeRecord whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OvertimeRecord whereWorkedOn($value)
 */
	class OvertimeRecord extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $company_id
 * @property int $year
 * @property int $month
 * @property string $status
 * @property string|null $closed_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollPeriod newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollPeriod newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollPeriod query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollPeriod whereClosedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollPeriod whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollPeriod whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollPeriod whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollPeriod whereMonth($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollPeriod whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollPeriod whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollPeriod whereYear($value)
 */
	class PayrollPeriod extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $company_id
 * @property int $employee_id
 * @property int $payroll_period_id
 * @property int $gross
 * @property int $deductions
 * @property int $net
 * @property string $currency
 * @property string|null $published_at
 * @property string|null $file_path
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payslip newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payslip newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payslip query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payslip whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payslip whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payslip whereCurrency($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payslip whereDeductions($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payslip whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payslip whereFilePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payslip whereGross($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payslip whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payslip whereNet($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payslip wherePayrollPeriodId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payslip wherePublishedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payslip whereUpdatedAt($value)
 */
	class Payslip extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $company_id
 * @property int $payslip_id
 * @property string $label
 * @property string $type
 * @property int $amount
 * @property int $sort_order
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayslipLine newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayslipLine newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayslipLine query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayslipLine whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayslipLine whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayslipLine whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayslipLine whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayslipLine whereLabel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayslipLine wherePayslipId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayslipLine whereSortOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayslipLine whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayslipLine whereUpdatedAt($value)
 */
	class PayslipLine extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $user_id
 * @property string $resource
 * @property bool $can_create
 * @property bool $can_read
 * @property bool $can_update
 * @property bool $can_delete
 * @property string $scope
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission whereCanCreate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission whereCanDelete($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission whereCanRead($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission whereCanUpdate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission whereResource($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission whereScope($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission whereUserId($value)
 */
	class Permission extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $company_id
 * @property int $employee_id
 * @property int $permission_type_id
 * @property \Illuminate\Support\Carbon $start_date
 * @property \Illuminate\Support\Carbon $end_date
 * @property string|null $start_time
 * @property string|null $end_time
 * @property float $days
 * @property float|null $hours
 * @property float $free_days
 * @property float $deducted_days
 * @property float $unpaid_days
 * @property string|null $reason
 * @property string|null $exception_reason
 * @property bool $exceeds_balance
 * @property float|null $balance_before
 * @property float|null $balance_after
 * @property string|null $attachment_path
 * @property string|null $attachment_name
 * @property string $status
 * @property int|null $document_id
 * @property \Illuminate\Support\Carbon|null $decided_at
 * @property int|null $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Company $company
 * @property-read \App\Models\Document|null $document
 * @property-read \App\Models\Employee|null $employee
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ApprovalStep> $steps
 * @property-read int|null $steps_count
 * @property-read \App\Models\PermissionType $type
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PermissionRequest newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PermissionRequest newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PermissionRequest query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PermissionRequest whereAttachmentName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PermissionRequest whereAttachmentPath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PermissionRequest whereBalanceAfter($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PermissionRequest whereBalanceBefore($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PermissionRequest whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PermissionRequest whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PermissionRequest whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PermissionRequest whereDays($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PermissionRequest whereDecidedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PermissionRequest whereDeductedDays($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PermissionRequest whereDocumentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PermissionRequest whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PermissionRequest whereEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PermissionRequest whereEndTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PermissionRequest whereExceedsBalance($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PermissionRequest whereExceptionReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PermissionRequest whereFreeDays($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PermissionRequest whereHours($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PermissionRequest whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PermissionRequest wherePermissionTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PermissionRequest whereReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PermissionRequest whereStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PermissionRequest whereStartTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PermissionRequest whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PermissionRequest whereUnpaidDays($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PermissionRequest whereUpdatedAt($value)
 */
	class PermissionRequest extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $company_id
 * @property string $code
 * @property string $name
 * @property string $kind
 * @property float $free_days
 * @property string $excess_policy
 * @property float|null $max_days
 * @property float|null $yearly_cap_days
 * @property bool $requires_document
 * @property float|null $hours_free_per_month
 * @property string|null $sheet_message
 * @property bool $active
 * @property int $sort
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Company $company
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PermissionType newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PermissionType newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PermissionType query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PermissionType whereActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PermissionType whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PermissionType whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PermissionType whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PermissionType whereExcessPolicy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PermissionType whereFreeDays($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PermissionType whereHoursFreePerMonth($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PermissionType whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PermissionType whereKind($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PermissionType whereMaxDays($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PermissionType whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PermissionType whereRequiresDocument($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PermissionType whereSheetMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PermissionType whereSort($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PermissionType whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PermissionType whereYearlyCapDays($value)
 */
	class PermissionType extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $company_id
 * @property int $department_id
 * @property string $title
 * @property string|null $grade
 * @property int|null $salary_min
 * @property int|null $salary_max
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Company $company
 * @property-read \App\Models\Department $department
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Employee> $employees
 * @property-read int|null $employees_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Position newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Position newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Position query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Position whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Position whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Position whereDepartmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Position whereGrade($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Position whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Position whereSalaryMax($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Position whereSalaryMin($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Position whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Position whereUpdatedAt($value)
 */
	class Position extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $company_id
 * @property string $key
 * @property string|null $value
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting whereKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting whereValue($value)
 */
	class Setting extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $company_id
 * @property int $employee_id
 * @property string $type
 * @property int $termination_reason_id
 * @property string|null $notice_date
 * @property string $last_day
 * @property string|null $comment
 * @property int $equipment_returned
 * @property string|null $final_settlement_paid_at
 * @property int|null $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Termination newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Termination newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Termination query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Termination whereComment($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Termination whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Termination whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Termination whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Termination whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Termination whereEquipmentReturned($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Termination whereFinalSettlementPaidAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Termination whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Termination whereLastDay($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Termination whereNoticeDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Termination whereTerminationReasonId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Termination whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Termination whereUpdatedAt($value)
 */
	class Termination extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $company_id
 * @property string $label
 * @property string $category
 * @property int $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TerminationReason newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TerminationReason newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TerminationReason query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TerminationReason whereCategory($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TerminationReason whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TerminationReason whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TerminationReason whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TerminationReason whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TerminationReason whereLabel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TerminationReason whereUpdatedAt($value)
 */
	class TerminationReason extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int|null $company_id
 * @property int|null $employee_id
 * @property string $name
 * @property string $email
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property bool $is_platform_admin
 * @property bool $is_company_owner
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $first_login_at
 * @property bool $must_change_password
 * @property-read \App\Models\Company|null $company
 * @property-read \App\Models\Employee|null $employee
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Permission> $permissions
 * @property-read int|null $permissions_count
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereFirstLoginAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereIsCompanyOwner($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereIsPlatformAdmin($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereMustChangePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedAt($value)
 */
	class User extends \Eloquent {}
}

namespace App\Models{
/**
 * Planning de travail : semaine standard, ou rotation (travail N jours puis repos M jours).
 *
 * @property int $id
 * @property int $company_id
 * @property string $name
 * @property string $type
 * @property array<array-key, mixed>|null $workdays
 * @property int|null $on_days
 * @property int|null $off_days
 * @property \Illuminate\Support\Carbon|null $anchor_date
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property bool $grants_annual_leave
 * @property-read \App\Models\Company $company
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkSchedule newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkSchedule newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkSchedule query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkSchedule whereAnchorDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkSchedule whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkSchedule whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkSchedule whereGrantsAnnualLeave($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkSchedule whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkSchedule whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkSchedule whereOffDays($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkSchedule whereOnDays($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkSchedule whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkSchedule whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkSchedule whereWorkdays($value)
 */
	class WorkSchedule extends \Eloquent {}
}

namespace App\Models{
/**
 * Base ou site de travail (Bamako, Sikasso, Ségou…).
 *
 * @property int $id
 * @property int $company_id
 * @property string $name
 * @property string|null $city
 * @property bool $active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Company $company
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkSite newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkSite newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkSite query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkSite whereActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkSite whereCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkSite whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkSite whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkSite whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkSite whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkSite whereUpdatedAt($value)
 */
	class WorkSite extends \Eloquent {}
}

namespace App\Models{
/**
 * @method static \Illuminate\Database\Eloquent\Builder<static>|matricule_sequences_table newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|matricule_sequences_table newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|matricule_sequences_table query()
 */
	class matricule_sequences_table extends \Eloquent {}
}

