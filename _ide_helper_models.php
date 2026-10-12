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
 * @property int|null $candidate_category_id
 * @property string $first_name
 * @property string $last_name
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $city
 * @property string|null $nationality
 * @property int $is_expatriate
 * @property string|null $education_level
 * @property string|null $diploma
 * @property string|null $field_of_study
 * @property int|null $years_experience
 * @property string|null $skills
 * @property string $source
 * @property string|null $channel
 * @property string $received_at
 * @property string|null $cv_path
 * @property string $status
 * @property int|null $rating
 * @property string|null $notes
 * @property string|null $retain_until
 * @property int|null $employee_id
 * @property int|null $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate whereCandidateCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate whereChannel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate whereCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate whereCvPath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate whereDiploma($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate whereEducationLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate whereFieldOfStudy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate whereFirstName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate whereIsExpatriate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate whereLastName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate whereNationality($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate whereRating($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate whereReceivedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate whereRetainUntil($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate whereSkills($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate whereSource($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate whereYearsExperience($value)
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
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company query()
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
 * @property string $start_date
 * @property string|null $end_date
 * @property int $base_salary
 * @property string $currency
 * @property string|null $trial_end_date
 * @property int $renewal_number
 * @property int|null $previous_contract_id
 * @property string $status
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
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
 * @property int $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
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
 * @property string|null $birth_date
 * @property string|null $birth_place
 * @property string|null $nationality
 * @property int $is_expatriate
 * @property string|null $marital_status
 * @property string|null $national_id
 * @property string|null $work_permit_number
 * @property string|null $work_permit_expires_at
 * @property string|null $phone
 * @property string|null $personal_email
 * @property string|null $address
 * @property string|null $emergency_contact_name
 * @property string|null $emergency_contact_phone
 * @property int $department_id
 * @property int $position_id
 * @property int|null $manager_id
 * @property string $hire_date
 * @property string $status
 * @property string|null $photo_path
 * @property string|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereBirthDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereBirthPlace($value)
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
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereManagerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereMaritalStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereMatricule($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereNationalId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereNationality($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee wherePersonalEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee wherePhotoPath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee wherePositionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereWorkPermitExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereWorkPermitNumber($value)
 */
	class Employee extends \Eloquent {}
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
 * @property string $date
 * @property int $is_recurring
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
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
 * @property string $start_date
 * @property string $end_date
 * @property numeric $days
 * @property string|null $reason
 * @property string $status
 * @property int|null $approved_by
 * @property string|null $decided_at
 * @property string|null $decision_note
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveRequest newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveRequest newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveRequest query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveRequest whereApprovedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveRequest whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveRequest whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveRequest whereDays($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveRequest whereDecidedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveRequest whereDecisionNote($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveRequest whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveRequest whereEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveRequest whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveRequest whereLeaveTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveRequest whereReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveRequest whereStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveRequest whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveRequest whereUpdatedAt($value)
 */
	class LeaveRequest extends \Eloquent {}
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
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveType newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveType newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveType query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveType whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveType whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveType whereDaysPerYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveType whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveType whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveType whereIsPaid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveType whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveType whereRequiresApproval($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveType whereUpdatedAt($value)
 */
	class LeaveType extends \Eloquent {}
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
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
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
 * @property int $can_create
 * @property int $can_read
 * @property int $can_update
 * @property int $can_delete
 * @property string $scope
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
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
 * @property int $department_id
 * @property string $title
 * @property string|null $grade
 * @property int|null $salary_min
 * @property int|null $salary_max
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
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
 * @property int $is_platform_admin
 * @property int $is_company_owner
 * @property int $is_active
 * @property string|null $first_login_at
 * @property int $must_change_password
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
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
 * @method static \Illuminate\Database\Eloquent\Builder<static>|matricule_sequences_table newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|matricule_sequences_table newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|matricule_sequences_table query()
 */
	class matricule_sequences_table extends \Eloquent {}
}

