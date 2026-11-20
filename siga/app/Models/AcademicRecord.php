<?php

namespace App\Models;

use Database\Factories\AcademicRecordFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $student_id
 * @property int $course_id
 * @property int|null $academic_term_id
 * @property string $status
 * @property float|null $grade
 * @property int|null $course_equivalence_id
 * @property-read Student $student
 * @property-read Course $course
 * @property-read AcademicTerm|null $academicTerm
 * @property-read CourseEquivalence|null $courseEquivalence
 */
#[Fillable(['student_id', 'course_id', 'academic_term_id', 'status', 'grade', 'course_equivalence_id'])]
class AcademicRecord extends Model
{
    /** @use HasFactory<AcademicRecordFactory> */
    use HasFactory;

    protected $casts = [
        'grade' => 'decimal:2',
    ];

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
     * @return BelongsTo<AcademicTerm, $this>
     */
    public function academicTerm(): BelongsTo
    {
        return $this->belongsTo(AcademicTerm::class);
    }

    /**
     * @return BelongsTo<CourseEquivalence, $this>
     */
    public function courseEquivalence(): BelongsTo
    {
        return $this->belongsTo(CourseEquivalence::class);
    }
}
