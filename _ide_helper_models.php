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
 * @property string $pay_mode
 * @property int|null $pay_salary_step_id
 * @property string $pay_salary_basis
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
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Assignment wherePayMode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Assignment wherePaySalaryBasis($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Assignment wherePaySalaryStepId($value)
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
 * @property int $company_id
 * @property int|null $created_by
 * @property string $type
 * @property string|null $title
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Company $company
 * @property-read \App\Models\ChatMessage|null $latestMessage
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ChatMessage> $messages
 * @property-read int|null $messages_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ChatParticipant> $participants
 * @property-read int|null $participants_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatConversation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatConversation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatConversation query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatConversation whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatConversation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatConversation whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatConversation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatConversation whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatConversation whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatConversation whereUpdatedAt($value)
 */
	class ChatConversation extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $company_id
 * @property int $conversation_id
 * @property int $sender_id
 * @property string|null $body
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $attachment_path
 * @property string|null $attachment_name
 * @property-read \App\Models\Company $company
 * @property-read \App\Models\ChatConversation $conversation
 * @property-read \App\Models\User $sender
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatMessage newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatMessage newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatMessage query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatMessage whereAttachmentName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatMessage whereAttachmentPath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatMessage whereBody($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatMessage whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatMessage whereConversationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatMessage whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatMessage whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatMessage whereSenderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatMessage whereUpdatedAt($value)
 */
	class ChatMessage extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $company_id
 * @property int $conversation_id
 * @property int $user_id
 * @property \Illuminate\Support\Carbon|null $last_read_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Company $company
 * @property-read \App\Models\ChatConversation $conversation
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatParticipant newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatParticipant newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatParticipant query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatParticipant whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatParticipant whereConversationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatParticipant whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatParticipant whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatParticipant whereLastReadAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatParticipant whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatParticipant whereUserId($value)
 */
	class ChatParticipant extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $country
 * @property string|null $sector
 * @property string $timezone
 * @property string $locale
 * @property string $currency
 * @property string|null $logo_path
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $trial_ends_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $career_slug
 * @property \Illuminate\Support\Carbon|null $suspended_at
 * @property string|null $suspension_reason
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Department> $departments
 * @property-read int|null $departments_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Employee> $employees
 * @property-read int|null $employees_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Subscription> $subscriptions
 * @property-read int|null $subscriptions_count
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
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereSector($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereSuspendedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereSuspensionReason($value)
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
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\MissionParticipant> $missionParticipations
 * @property-read int|null $mission_participations_count
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
 * @property int $employee_id
 * @property string $type
 * @property string|null $label
 * @property int $principal
 * @property int $installment_amount
 * @property string $first_month
 * @property float|null $interest_rate
 * @property string $status
 * @property int|null $approved_by
 * @property \Illuminate\Support\Carbon|null $approved_at
 * @property \Illuminate\Support\Carbon|null $disbursed_on
 * @property string|null $notes
 * @property int|null $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Company $company
 * @property-read \App\Models\Employee|null $employee
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\LoanInstallment> $installments
 * @property-read int|null $installments_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeLoan newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeLoan newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeLoan query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeLoan whereApprovedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeLoan whereApprovedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeLoan whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeLoan whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeLoan whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeLoan whereDisbursedOn($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeLoan whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeLoan whereFirstMonth($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeLoan whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeLoan whereInstallmentAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeLoan whereInterestRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeLoan whereLabel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeLoan whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeLoan wherePrincipal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeLoan whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeLoan whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeLoan whereUpdatedAt($value)
 */
	class EmployeeLoan extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $company_id
 * @property int $employee_id
 * @property int $pay_item_id
 * @property int|null $amount
 * @property numeric|null $rate
 * @property \Illuminate\Support\Carbon $start_date
 * @property \Illuminate\Support\Carbon|null $end_date
 * @property string|null $reason
 * @property int|null $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Company $company
 * @property-read \App\Models\Employee|null $employee
 * @property-read \App\Models\PayItem $item
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeePayItem inForceOn(string $date)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeePayItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeePayItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeePayItem query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeePayItem whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeePayItem whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeePayItem whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeePayItem whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeePayItem whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeePayItem whereEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeePayItem whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeePayItem wherePayItemId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeePayItem whereRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeePayItem whereReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeePayItem whereStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeePayItem whereUpdatedAt($value)
 */
	class EmployeePayItem extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $company_id
 * @property int $employee_id
 * @property int $pay_profile_id
 * @property \Illuminate\Support\Carbon $effective_from
 * @property \Illuminate\Support\Carbon|null $effective_until
 * @property string|null $notes
 * @property int|null $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Company $company
 * @property-read \App\Models\Employee|null $employee
 * @property-read \App\Models\PayProfile $profile
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeePayProfile inForceOn(string $date)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeePayProfile newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeePayProfile newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeePayProfile query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeePayProfile whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeePayProfile whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeePayProfile whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeePayProfile whereEffectiveFrom($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeePayProfile whereEffectiveUntil($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeePayProfile whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeePayProfile whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeePayProfile whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeePayProfile wherePayProfileId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeePayProfile whereUpdatedAt($value)
 */
	class EmployeePayProfile extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $company_id
 * @property int $employee_id
 * @property int|null $salary_grade_id
 * @property int|null $salary_step_id
 * @property string $mode
 * @property int $base_amount
 * @property int $supplement_amount
 * @property int|null $target_net
 * @property \Illuminate\Support\Carbon $effective_from
 * @property \Illuminate\Support\Carbon|null $effective_until
 * @property string|null $notes
 * @property int|null $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Company $company
 * @property-read \App\Models\Employee|null $employee
 * @property-read \App\Models\SalaryGrade|null $grade
 * @property-read \App\Models\SalaryStep|null $step
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeSalary inForceOn(string $date)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeSalary newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeSalary newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeSalary query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeSalary whereBaseAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeSalary whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeSalary whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeSalary whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeSalary whereEffectiveFrom($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeSalary whereEffectiveUntil($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeSalary whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeSalary whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeSalary whereMode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeSalary whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeSalary whereSalaryGradeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeSalary whereSalaryStepId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeSalary whereSupplementAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeSalary whereTargetNet($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeSalary whereUpdatedAt($value)
 */
	class EmployeeSalary extends \Eloquent {}
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
 * @property int $employee_loan_id
 * @property string $due_month
 * @property int $amount
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $deducted_at
 * @property string|null $deducted_ref
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\EmployeeLoan $loan
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanInstallment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanInstallment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanInstallment query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanInstallment whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanInstallment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanInstallment whereDeductedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanInstallment whereDeductedRef($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanInstallment whereDueMonth($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanInstallment whereEmployeeLoanId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanInstallment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanInstallment whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanInstallment whereUpdatedAt($value)
 */
	class LoanInstallment extends \Eloquent {}
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
 * @property int $id
 * @property int $company_id
 * @property string|null $reference
 * @property int $requested_by
 * @property int $department_id
 * @property string $purpose
 * @property string $destination_zone
 * @property string|null $lodging
 * @property \Illuminate\Support\Carbon $start_date
 * @property \Illuminate\Support\Carbon $end_date
 * @property string|null $program
 * @property \App\Enums\MissionStatus $status
 * @property \Illuminate\Support\Carbon|null $submitted_at
 * @property \Illuminate\Support\Carbon|null $cancelled_at
 * @property string|null $cancellation_reason
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\MissionApproval> $approvals
 * @property-read int|null $approvals_count
 * @property-read \App\Models\Company $company
 * @property-read \App\Models\Department $department
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\MissionDocument> $documents
 * @property-read int|null $documents_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\MissionParticipant> $participants
 * @property-read int|null $participants_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\MissionPayment> $payments
 * @property-read int|null $payments_count
 * @property-read \App\Models\User $requester
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mission newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mission newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mission ongoingOn($date)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mission onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mission query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mission whereCancellationReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mission whereCancelledAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mission whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mission whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mission whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mission whereDepartmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mission whereDestinationZone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mission whereEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mission whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mission whereLodging($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mission whereProgram($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mission wherePurpose($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mission whereReference($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mission whereRequestedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mission whereStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mission whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mission whereSubmittedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mission whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mission withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mission withoutTrashed()
 */
	class Mission extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $mission_id
 * @property int $user_id
 * @property string $step
 * @property string $decision
 * @property string|null $comment
 * @property \Illuminate\Support\Carbon $decided_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Mission|null $mission
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MissionApproval newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MissionApproval newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MissionApproval query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MissionApproval whereComment($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MissionApproval whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MissionApproval whereDecidedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MissionApproval whereDecision($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MissionApproval whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MissionApproval whereMissionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MissionApproval whereStep($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MissionApproval whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MissionApproval whereUserId($value)
 */
	class MissionApproval extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $mission_id
 * @property int $participant_id
 * @property string $kind
 * @property int $version
 * @property string $reference
 * @property string $file_path
 * @property string|null $original_name
 * @property int $size
 * @property string $sha256
 * @property int|null $created_by
 * @property \Illuminate\Support\Carbon|null $superseded_at
 * @property \Illuminate\Support\Carbon|null $voided_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $creator
 * @property-read \App\Models\Mission|null $mission
 * @property-read \App\Models\MissionParticipant $participant
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MissionDocument newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MissionDocument newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MissionDocument query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MissionDocument whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MissionDocument whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MissionDocument whereFilePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MissionDocument whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MissionDocument whereKind($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MissionDocument whereMissionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MissionDocument whereOriginalName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MissionDocument whereParticipantId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MissionDocument whereReference($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MissionDocument whereSha256($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MissionDocument whereSize($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MissionDocument whereSupersededAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MissionDocument whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MissionDocument whereVersion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MissionDocument whereVoidedAt($value)
 */
	class MissionDocument extends \Eloquent {}
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
 * @property int $id
 * @property int $mission_id
 * @property int $employee_id
 * @property int $department_id
 * @property \App\Enums\MissionRole $role
 * @property \App\Enums\PerdiemMode $perdiem_mode
 * @property int $days
 * @property int|null $daily_rate
 * @property int|null $total_amount
 * @property int|null $salary_divisor
 * @property \Illuminate\Support\Carbon|null $rate_locked_at
 * @property string $payment_mode
 * @property string|null $payment_note
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Department $department
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\MissionDocument> $documents
 * @property-read int|null $documents_count
 * @property-read \App\Models\Employee|null $employee
 * @property-read \App\Models\Mission|null $mission
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\MissionPayment> $payments
 * @property-read int|null $payments_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MissionParticipant newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MissionParticipant newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MissionParticipant query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MissionParticipant whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MissionParticipant whereDailyRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MissionParticipant whereDays($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MissionParticipant whereDepartmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MissionParticipant whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MissionParticipant whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MissionParticipant whereMissionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MissionParticipant wherePaymentMode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MissionParticipant wherePaymentNote($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MissionParticipant wherePerdiemMode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MissionParticipant whereRateLockedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MissionParticipant whereRole($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MissionParticipant whereSalaryDivisor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MissionParticipant whereTotalAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MissionParticipant whereUpdatedAt($value)
 */
	class MissionParticipant extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $mission_id
 * @property int $participant_id
 * @property int $sequence
 * @property string $label
 * @property int $planned_amount
 * @property \Illuminate\Support\Carbon|null $planned_date
 * @property string $status
 * @property int|null $paid_amount
 * @property \Illuminate\Support\Carbon|null $paid_at
 * @property string|null $method
 * @property string|null $reference
 * @property string|null $note
 * @property int|null $paid_by
 * @property \Illuminate\Support\Carbon|null $voided_at
 * @property string|null $void_reason
 * @property int|null $voided_by
 * @property string|null $receipt_path
 * @property string|null $receipt_name
 * @property string|null $receipt_sha256
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Mission|null $mission
 * @property-read \App\Models\MissionParticipant $participant
 * @property-read \App\Models\User|null $payer
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MissionPayment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MissionPayment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MissionPayment query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MissionPayment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MissionPayment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MissionPayment whereLabel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MissionPayment whereMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MissionPayment whereMissionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MissionPayment whereNote($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MissionPayment wherePaidAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MissionPayment wherePaidAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MissionPayment wherePaidBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MissionPayment whereParticipantId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MissionPayment wherePlannedAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MissionPayment wherePlannedDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MissionPayment whereReceiptName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MissionPayment whereReceiptPath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MissionPayment whereReceiptSha256($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MissionPayment whereReference($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MissionPayment whereSequence($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MissionPayment whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MissionPayment whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MissionPayment whereVoidReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MissionPayment whereVoidedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MissionPayment whereVoidedBy($value)
 */
	class MissionPayment extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $company_id
 * @property string $label
 * @property float $percent
 * @property int $sort
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Company $company
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OvertimeRate newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OvertimeRate newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OvertimeRate query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OvertimeRate whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OvertimeRate whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OvertimeRate whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OvertimeRate whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OvertimeRate whereLabel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OvertimeRate wherePercent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OvertimeRate whereSort($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OvertimeRate whereUpdatedAt($value)
 */
	class OvertimeRate extends \Eloquent {}
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
 * @property string $code
 * @property string $label
 * @property string $type
 * @property string|null $system_key
 * @property bool $is_system
 * @property bool $subject_to_social
 * @property bool $taxable
 * @property bool $is_compulsory
 * @property string $calc_type
 * @property int|null $default_amount
 * @property numeric|null $default_rate
 * @property int|null $ref_item_id
 * @property int|null $floor_amount
 * @property int|null $cap_amount
 * @property bool $show_on_payslip
 * @property int $sort
 * @property bool $is_active
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Company $company
 * @property-read PayItem|null $refItem
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PayItemScaleRow> $scaleRows
 * @property-read int|null $scale_rows_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayItem query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayItem whereCalcType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayItem whereCapAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayItem whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayItem whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayItem whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayItem whereDefaultAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayItem whereDefaultRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayItem whereFloorAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayItem whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayItem whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayItem whereIsCompulsory($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayItem whereIsSystem($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayItem whereLabel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayItem whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayItem whereRefItemId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayItem whereShowOnPayslip($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayItem whereSort($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayItem whereSubjectToSocial($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayItem whereSystemKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayItem whereTaxable($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayItem whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayItem whereUpdatedAt($value)
 */
	class PayItem extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $pay_item_id
 * @property int $from_years
 * @property numeric $rate
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\PayItem $item
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayItemScaleRow newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayItemScaleRow newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayItemScaleRow query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayItemScaleRow whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayItemScaleRow whereFromYears($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayItemScaleRow whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayItemScaleRow wherePayItemId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayItemScaleRow whereRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayItemScaleRow whereUpdatedAt($value)
 */
	class PayItemScaleRow extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $company_id
 * @property string $name
 * @property string|null $convention
 * @property string|null $description
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Company $company
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PayProfileItem> $items
 * @property-read int|null $items_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayProfile newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayProfile newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayProfile query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayProfile whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayProfile whereConvention($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayProfile whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayProfile whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayProfile whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayProfile whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayProfile whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayProfile whereUpdatedAt($value)
 */
	class PayProfile extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $pay_profile_id
 * @property int $pay_item_id
 * @property int|null $amount
 * @property numeric|null $rate
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\PayItem $item
 * @property-read \App\Models\PayProfile $profile
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayProfileItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayProfileItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayProfileItem query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayProfileItem whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayProfileItem whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayProfileItem whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayProfileItem wherePayItemId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayProfileItem wherePayProfileId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayProfileItem whereRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayProfileItem whereUpdatedAt($value)
 */
	class PayProfileItem extends \Eloquent {}
}

namespace App\Models{
/**
 * Paquet par secteur (espace plateforme). Un paquet validé ne se modifie plus : on crée une nouvelle version.
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string|null $sector
 * @property int $version
 * @property string $status
 * @property string|null $description
 * @property string|null $source
 * @property array<array-key, mixed> $payload
 * @property int|null $created_by
 * @property int|null $validated_by
 * @property \Illuminate\Support\Carbon|null $validated_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PayrollPackageApplication> $applications
 * @property-read int|null $applications_count
 * @property-read \App\Models\User|null $validator
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollPackage newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollPackage newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollPackage query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollPackage whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollPackage whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollPackage whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollPackage whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollPackage whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollPackage whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollPackage wherePayload($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollPackage whereSector($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollPackage whereSource($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollPackage whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollPackage whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollPackage whereValidatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollPackage whereValidatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollPackage whereVersion($value)
 */
	class PayrollPackage extends \Eloquent {}
}

namespace App\Models{
/**
 * Une application de paquet par une entreprise. Pas de scope global : on filtre toujours par company_id.
 *
 * @property int $id
 * @property int $company_id
 * @property int $payroll_package_id
 * @property string $package_code
 * @property string $package_name
 * @property int $package_version
 * @property bool $was_validated
 * @property int|null $applied_by
 * @property array<array-key, mixed> $report
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\PayrollPackage $package
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollPackageApplication newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollPackageApplication newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollPackageApplication query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollPackageApplication whereAppliedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollPackageApplication whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollPackageApplication whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollPackageApplication whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollPackageApplication wherePackageCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollPackageApplication wherePackageName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollPackageApplication wherePackageVersion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollPackageApplication wherePayrollPackageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollPackageApplication whereReport($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollPackageApplication whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollPackageApplication whereWasValidated($value)
 */
	class PayrollPackageApplication extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $company_id
 * @property string $period
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $calculated_at
 * @property int|null $calculated_by
 * @property \Illuminate\Support\Carbon|null $closed_at
 * @property int|null $closed_by
 * @property array<array-key, mixed>|null $summary
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $closer
 * @property-read \App\Models\Company $company
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Payslip> $payslips
 * @property-read int|null $payslips_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollPeriod newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollPeriod newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollPeriod query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollPeriod whereCalculatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollPeriod whereCalculatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollPeriod whereClosedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollPeriod whereClosedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollPeriod whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollPeriod whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollPeriod whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollPeriod whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollPeriod wherePeriod($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollPeriod whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollPeriod whereSummary($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollPeriod whereUpdatedAt($value)
 */
	class PayrollPeriod extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $country
 * @property string $code
 * @property string $label
 * @property string $party
 * @property string $kind
 * @property float|null $rate
 * @property array<array-key, mixed>|null $params
 * @property string $base
 * @property bool $deductible_from_its
 * @property int|null $ceiling_monthly
 * @property \Illuminate\Support\Carbon $effective_from
 * @property \Illuminate\Support\Carbon|null $effective_until
 * @property string $status
 * @property int|null $validated_by
 * @property \Illuminate\Support\Carbon|null $validated_at
 * @property string|null $source
 * @property string|null $notes
 * @property int $sort
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $validator
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollRule newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollRule newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollRule query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollRule whereBase($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollRule whereCeilingMonthly($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollRule whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollRule whereCountry($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollRule whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollRule whereDeductibleFromIts($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollRule whereEffectiveFrom($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollRule whereEffectiveUntil($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollRule whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollRule whereKind($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollRule whereLabel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollRule whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollRule whereParams($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollRule whereParty($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollRule whereRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollRule whereSort($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollRule whereSource($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollRule whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollRule whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollRule whereValidatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollRule whereValidatedBy($value)
 */
	class PayrollRule extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $company_id
 * @property string $country
 * @property string|null $convention_name
 * @property int $days_basis
 * @property float $hours_month
 * @property string $rounding
 * @property bool $show_employer_part
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollSetting newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollSetting newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollSetting query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollSetting whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollSetting whereConventionName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollSetting whereCountry($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollSetting whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollSetting whereDaysBasis($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollSetting whereHoursMonth($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollSetting whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollSetting whereRounding($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollSetting whereShowEmployerPart($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollSetting whereUpdatedAt($value)
 */
	class PayrollSetting extends \Eloquent {}
}

namespace App\Models{
/**
 * État de l'assistant « Démarrer la paie » (une ligne par entreprise).
 *
 * Comme PayrollSetting, ce modèle n'utilise pas le scope global : on filtre toujours explicitement par company_id.
 *
 * @property int $id
 * @property int $company_id
 * @property bool $is_hidden
 * @property array<array-key, mixed>|null $confirmed
 * @property array<array-key, mixed>|null $skipped
 * @property \Illuminate\Support\Carbon|null $welcomed_at
 * @property \Illuminate\Support\Carbon|null $simulation_done_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollSetupState newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollSetupState newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollSetupState query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollSetupState whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollSetupState whereConfirmed($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollSetupState whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollSetupState whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollSetupState whereIsHidden($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollSetupState whereSimulationDoneAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollSetupState whereSkipped($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollSetupState whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollSetupState whereWelcomedAt($value)
 */
	class PayrollSetupState extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $company_id
 * @property int $employee_id
 * @property string $period
 * @property string $kind
 * @property int|null $pay_item_id
 * @property float|null $quantity
 * @property string|null $unit
 * @property float|null $rate_percent
 * @property int|null $amount_override
 * @property string|null $days_mode
 * @property string|null $source_type
 * @property int|null $source_id
 * @property string|null $source_ref
 * @property string $status
 * @property bool $is_edited
 * @property string|null $note
 * @property int|null $created_by
 * @property int|null $confirmed_by
 * @property \Illuminate\Support\Carbon|null $confirmed_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Company $company
 * @property-read \App\Models\Employee|null $employee
 * @property-read \App\Models\PayItem|null $item
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollVariable newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollVariable newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollVariable query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollVariable whereAmountOverride($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollVariable whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollVariable whereConfirmedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollVariable whereConfirmedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollVariable whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollVariable whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollVariable whereDaysMode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollVariable whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollVariable whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollVariable whereIsEdited($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollVariable whereKind($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollVariable whereNote($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollVariable wherePayItemId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollVariable wherePeriod($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollVariable whereQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollVariable whereRatePercent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollVariable whereSourceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollVariable whereSourceRef($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollVariable whereSourceType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollVariable whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollVariable whereUnit($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollVariable whereUpdatedAt($value)
 */
	class PayrollVariable extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $company_id
 * @property string $pay_basis
 * @property string $suspension_count_mode
 * @property array<array-key, mixed>|null $rest_weekdays
 * @property bool $unjustified_absence_unpaid
 * @property numeric|null $loan_effort_alert_percent
 * @property int $net_tolerance
 * @property string $arrival_rule
 * @property int $arrival_grace_days
 * @property string $departure_rule
 * @property int $departure_grace_days
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string $ui_mode
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollVariableSetting newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollVariableSetting newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollVariableSetting query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollVariableSetting whereArrivalGraceDays($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollVariableSetting whereArrivalRule($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollVariableSetting whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollVariableSetting whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollVariableSetting whereDepartureGraceDays($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollVariableSetting whereDepartureRule($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollVariableSetting whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollVariableSetting whereLoanEffortAlertPercent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollVariableSetting whereNetTolerance($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollVariableSetting wherePayBasis($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollVariableSetting whereRestWeekdays($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollVariableSetting whereSuspensionCountMode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollVariableSetting whereUiMode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollVariableSetting whereUnjustifiedAbsenceUnpaid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollVariableSetting whereUpdatedAt($value)
 */
	class PayrollVariableSetting extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $company_id
 * @property int $payroll_period_id
 * @property int $employee_id
 * @property \App\Models\PayrollPeriod $period
 * @property string $status
 * @property string $employee_name
 * @property string|null $matricule
 * @property int|null $department_id
 * @property string|null $department_name
 * @property int $gross
 * @property int $net_to_pay
 * @property int $net_contract
 * @property int|null $net_known
 * @property int $variables_effect
 * @property int|null $structure_gap
 * @property string $check_status
 * @property int $employee_contributions
 * @property int $its
 * @property int $employer_total
 * @property int $employer_cost
 * @property int|null $acknowledged_by
 * @property \Illuminate\Support\Carbon|null $acknowledged_at
 * @property string|null $acknowledged_note
 * @property string $input_hash
 * @property array<array-key, mixed> $snapshot
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Company $company
 * @property-read \App\Models\Employee|null $employee
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payslip newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payslip newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payslip query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payslip whereAcknowledgedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payslip whereAcknowledgedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payslip whereAcknowledgedNote($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payslip whereCheckStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payslip whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payslip whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payslip whereDepartmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payslip whereDepartmentName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payslip whereEmployeeContributions($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payslip whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payslip whereEmployeeName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payslip whereEmployerCost($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payslip whereEmployerTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payslip whereGross($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payslip whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payslip whereInputHash($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payslip whereIts($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payslip whereMatricule($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payslip whereNetContract($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payslip whereNetKnown($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payslip whereNetToPay($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payslip wherePayrollPeriodId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payslip wherePeriod($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payslip whereSnapshot($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payslip whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payslip whereStructureGap($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payslip whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payslip whereVariablesEffect($value)
 */
	class Payslip extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $company_id
 * @property int $payslip_id
 * @property int $employee_id
 * @property string $channel
 * @property string|null $address
 * @property string $status
 * @property int $attempts
 * @property \Illuminate\Support\Carbon|null $sent_at
 * @property string|null $error
 * @property string $token
 * @property \Illuminate\Support\Carbon|null $opened_at
 * @property \Illuminate\Support\Carbon|null $viewed_at
 * @property int|null $triggered_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Company $company
 * @property-read \App\Models\Payslip $payslip
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayslipDelivery newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayslipDelivery newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayslipDelivery query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayslipDelivery whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayslipDelivery whereAttempts($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayslipDelivery whereChannel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayslipDelivery whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayslipDelivery whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayslipDelivery whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayslipDelivery whereError($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayslipDelivery whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayslipDelivery whereOpenedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayslipDelivery wherePayslipId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayslipDelivery whereSentAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayslipDelivery whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayslipDelivery whereToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayslipDelivery whereTriggeredBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayslipDelivery whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayslipDelivery whereViewedAt($value)
 */
	class PayslipDelivery extends \Eloquent {}
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
 * @property int $company_id
 * @property string $name
 * @property string|null $convention
 * @property bool $show_employer_part
 * @property bool $show_item_codes
 * @property bool $group_lines
 * @property string|null $footer_text
 * @property bool $is_default
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Company $company
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayslipTemplate newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayslipTemplate newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayslipTemplate query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayslipTemplate whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayslipTemplate whereConvention($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayslipTemplate whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayslipTemplate whereFooterText($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayslipTemplate whereGroupLines($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayslipTemplate whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayslipTemplate whereIsDefault($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayslipTemplate whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayslipTemplate whereShowEmployerPart($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayslipTemplate whereShowItemCodes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayslipTemplate whereUpdatedAt($value)
 */
	class PayslipTemplate extends \Eloquent {}
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
 * @property string $code
 * @property string $name
 * @property string|null $description
 * @property string $color
 * @property int|null $max_employees
 * @property int $storage_gb
 * @property int $audit_retention_months
 * @property int $sort
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PlanFeature> $features
 * @property-read int|null $features_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PlanPrice> $prices
 * @property-read int|null $prices_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Subscription> $subscriptions
 * @property-read int|null $subscriptions_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Plan newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Plan newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Plan query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Plan whereAuditRetentionMonths($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Plan whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Plan whereColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Plan whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Plan whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Plan whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Plan whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Plan whereMaxEmployees($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Plan whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Plan whereSort($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Plan whereStorageGb($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Plan whereUpdatedAt($value)
 */
	class Plan extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $plan_id
 * @property string $feature
 * @property bool $enabled
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanFeature newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanFeature newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanFeature query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanFeature whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanFeature whereEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanFeature whereFeature($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanFeature whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanFeature wherePlanId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanFeature whereUpdatedAt($value)
 */
	class PlanFeature extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $plan_id
 * @property int $months
 * @property int|null $price
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanPrice newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanPrice newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanPrice query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanPrice whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanPrice whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanPrice whereMonths($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanPrice wherePlanId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanPrice wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanPrice whereUpdatedAt($value)
 */
	class PlanPrice extends \Eloquent {}
}

namespace App\Models{
/**
 * Journal en ajout seul : aucune ligne ne se modifie ni ne se supprime depuis l'application.
 *
 * @property int $id
 * @property int|null $user_id
 * @property int|null $company_id
 * @property string $action
 * @property string $summary
 * @property array<array-key, mixed>|null $meta
 * @property string|null $ip
 * @property string|null $user_agent
 * @property \Illuminate\Support\Carbon $created_at
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlatformAuditLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlatformAuditLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlatformAuditLog query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlatformAuditLog whereAction($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlatformAuditLog whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlatformAuditLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlatformAuditLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlatformAuditLog whereIp($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlatformAuditLog whereMeta($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlatformAuditLog whereSummary($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlatformAuditLog whereUserAgent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlatformAuditLog whereUserId($value)
 */
	class PlatformAuditLog extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int|null $company_id
 * @property int|null $subscription_id
 * @property int|null $payment_id
 * @property string $type
 * @property \Illuminate\Support\Carbon|null $period_end
 * @property string $recipients
 * @property string $subject
 * @property string $status
 * @property string|null $error
 * @property int|null $triggered_by
 * @property \Illuminate\Support\Carbon $created_at
 * @property-read \App\Models\User|null $triggeredBy
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlatformMailLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlatformMailLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlatformMailLog query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlatformMailLog whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlatformMailLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlatformMailLog whereError($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlatformMailLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlatformMailLog wherePaymentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlatformMailLog wherePeriodEnd($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlatformMailLog whereRecipients($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlatformMailLog whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlatformMailLog whereSubject($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlatformMailLog whereSubscriptionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlatformMailLog whereTriggeredBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlatformMailLog whereType($value)
 */
	class PlatformMailLog extends \Eloquent {}
}

namespace App\Models{
/**
 * Identité de l'éditeur de la plateforme. Mêmes noms de champs que Letterhead : le composant x-letterhead-header la lit tel quel.
 *
 * @property int $id
 * @property string $legal_name
 * @property string|null $address
 * @property string|null $city
 * @property string|null $phone
 * @property string|null $email
 * @property string|null $website
 * @property string|null $rccm
 * @property string|null $nif
 * @property string|null $bank_details
 * @property string|null $tax_note
 * @property string|null $footer_text
 * @property string|null $logo_path
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlatformProfile newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlatformProfile newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlatformProfile query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlatformProfile whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlatformProfile whereBankDetails($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlatformProfile whereCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlatformProfile whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlatformProfile whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlatformProfile whereFooterText($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlatformProfile whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlatformProfile whereLegalName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlatformProfile whereLogoPath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlatformProfile whereNif($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlatformProfile wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlatformProfile whereRccm($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlatformProfile whereTaxNote($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlatformProfile whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlatformProfile whereWebsite($value)
 */
	class PlatformProfile extends \Eloquent {}
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
 * @property int|null $salary_grade_id
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
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Position whereSalaryGradeId($value)
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
 * @property int $salary_grid_id
 * @property string $code
 * @property string|null $label
 * @property int $sort
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\SalaryGrid $grid
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\SalaryStep> $steps
 * @property-read int|null $steps_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalaryGrade newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalaryGrade newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalaryGrade query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalaryGrade whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalaryGrade whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalaryGrade whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalaryGrade whereLabel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalaryGrade whereSalaryGridId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalaryGrade whereSort($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalaryGrade whereUpdatedAt($value)
 */
	class SalaryGrade extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $company_id
 * @property string $name
 * @property string|null $convention
 * @property \Illuminate\Support\Carbon|null $effective_from
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Company $company
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\SalaryGrade> $grades
 * @property-read int|null $grades_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalaryGrid newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalaryGrid newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalaryGrid query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalaryGrid whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalaryGrid whereConvention($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalaryGrid whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalaryGrid whereEffectiveFrom($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalaryGrid whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalaryGrid whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalaryGrid whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalaryGrid whereUpdatedAt($value)
 */
	class SalaryGrid extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $salary_grade_id
 * @property string $label
 * @property int $amount
 * @property int $sort
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\SalaryGrade $grade
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalaryStep newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalaryStep newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalaryStep query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalaryStep whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalaryStep whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalaryStep whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalaryStep whereLabel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalaryStep whereSalaryGradeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalaryStep whereSort($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalaryStep whereUpdatedAt($value)
 */
	class SalaryStep extends \Eloquent {}
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
 * @property int $plan_id
 * @property int $period_months
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $starts_at
 * @property \Illuminate\Support\Carbon|null $ends_at
 * @property \Illuminate\Support\Carbon|null $trial_ends_at
 * @property int $list_price
 * @property int $discount_percent
 * @property int $price
 * @property string $currency
 * @property string|null $notes
 * @property int|null $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Company $company
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\SubscriptionPayment> $payments
 * @property-read int|null $payments_count
 * @property-read \App\Models\Plan $plan
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription whereCurrency($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription whereDiscountPercent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription whereEndsAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription whereListPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription wherePeriodMonths($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription wherePlanId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription whereStartsAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription whereTrialEndsAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription whereUpdatedAt($value)
 */
	class Subscription extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $subscription_id
 * @property int $company_id
 * @property string $invoice_number
 * @property int $amount
 * @property \Illuminate\Support\Carbon $paid_at
 * @property string $method
 * @property string|null $reference
 * @property \Illuminate\Support\Carbon|null $period_start
 * @property \Illuminate\Support\Carbon|null $period_end
 * @property string|null $note
 * @property int|null $recorded_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $invoice_sent_at
 * @property string|null $invoice_sent_to
 * @property int $cycle
 * @property int $tranche
 * @property int $balance_after
 * @property-read \App\Models\Company $company
 * @property-read \App\Models\User|null $recorder
 * @property-read \App\Models\Subscription $subscription
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionPayment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionPayment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionPayment query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionPayment whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionPayment whereBalanceAfter($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionPayment whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionPayment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionPayment whereCycle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionPayment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionPayment whereInvoiceNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionPayment whereInvoiceSentAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionPayment whereInvoiceSentTo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionPayment whereMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionPayment whereNote($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionPayment wherePaidAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionPayment wherePeriodEnd($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionPayment wherePeriodStart($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionPayment whereRecordedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionPayment whereReference($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionPayment whereSubscriptionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionPayment whereTranche($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionPayment whereUpdatedAt($value)
 */
	class SubscriptionPayment extends \Eloquent {}
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
 * @property string|null $platform_role
 * @property string|null $platform_permissions
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
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePlatformPermissions($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePlatformRole($value)
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

