<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Un curso dentro de una solicitud de convalidación.
 *
 * @property int $id
 * @property int $student_request_id
 * @property int $course_id
 * @property string $external_institution
 * @property string $external_course_name
 * @property string|null $external_course_code
 * @property int|null $external_credits
 * @property float|null $external_grade
 * @property string $outcome
 * @property string|null $denial_reason
 * @property-read StudentRequest $studentRequest
 * @property-read Course $course
 */
#[Fillable([
    'student_request_id',
    'course_id',
    'external_institution',
    'external_course_name',
    'external_course_code',
    'external_credits',
    'external_grade',
    'outcome',
    'denial_reason',
])]
class StudentRequestCourse extends Model
{
    protected $casts = [
        'external_credits' => 'integer',
        // Sin 'decimal:2': ese cast devuelve una CADENA con dos decimales.
        'external_grade' => 'float',
    ];

    /**
     * @return BelongsTo<StudentRequest, $this>
     */
    public function studentRequest(): BelongsTo
    {
        return $this->belongsTo(StudentRequest::class);
    }

    /**
     * @return BelongsTo<Course, $this>
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}
