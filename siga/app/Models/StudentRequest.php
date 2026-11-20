<?php

namespace App\Models;

use Database\Factories\StudentRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Solicitud estudiantil: levantamiento de requisito (ES-01) o convalidación (ES-02).
 *
 * @property int $id
 * @property int $student_id
 * @property string $type
 * @property int $course_id
 * @property int|null $prerequisite_course_id
 * @property string|null $justification
 * @property Carbon|null $terms_accepted_at
 * @property string|null $source_institution
 * @property string|null $external_course
 * @property int|null $transfer_credit_precedent_id
 * @property string|null $engine_result
 * @property int|null $failed_rule_id
 * @property string $status
 * @property string|null $teaching_decision
 * @property Carbon|null $estimated_resolution_date
 * @property int|null $reviewer_id
 * @property-read Student $student
 * @property-read Course $course
 * @property-read Course|null $prerequisiteCourse
 * @property-read TransferCreditPrecedent|null $transferCreditPrecedent
 * @property-read WaiverRule|null $failedRule
 * @property-read User|null $reviewer
 * @property-read Collection<int, StudentRequestStatusHistory> $statusHistory
 */
#[Fillable([
    'student_id', 'type', 'course_id', 'prerequisite_course_id', 'justification',
    'terms_accepted_at', 'source_institution',
    'external_course', 'transfer_credit_precedent_id', 'engine_result', 'failed_rule_id',
    'status', 'estimated_resolution_date', 'reviewer_id',
])]
class StudentRequest extends Model
{
    /** @use HasFactory<StudentRequestFactory> */
    use HasFactory;

    protected $casts = [
        'estimated_resolution_date' => 'date',
        'session_date' => 'date',
        'terms_accepted_at' => 'datetime',
    ];

    /**
     * Los cursos de una convalidación.
     *
     * @return HasMany<StudentRequestCourse, $this>
     */
    public function requestedCourses(): HasMany
    {
        return $this->hasMany(StudentRequestCourse::class);
    }

    /**
     * @return BelongsTo<Student, $this>
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * @return BelongsTo<Course, $this>
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * @return BelongsTo<Course, $this>
     */
    public function prerequisiteCourse(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'prerequisite_course_id');
    }

    /**
     * @return BelongsTo<TransferCreditPrecedent, $this>
     */
    public function transferCreditPrecedent(): BelongsTo
    {
        return $this->belongsTo(TransferCreditPrecedent::class);
    }

    /**
     * @return BelongsTo<WaiverRule, $this>
     */
    public function failedRule(): BelongsTo
    {
        return $this->belongsTo(WaiverRule::class, 'failed_rule_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    /**
     * @return HasMany<StudentRequestStatusHistory, $this>
     */
    public function statusHistory(): HasMany
    {
        return $this->hasMany(StudentRequestStatusHistory::class)->latest('created_at');
    }
}
