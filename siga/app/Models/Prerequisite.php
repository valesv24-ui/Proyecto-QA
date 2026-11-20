<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Requisito entre dos cursos dentro de un plan de estudios.
 *
 * @property int $id
 * @property int $study_plan_id
 * @property int $required_course_id
 * @property int $target_course_id
 * @property-read StudyPlan $studyPlan
 * @property-read Course $requiredCourse
 * @property-read Course $targetCourse
 */
#[Fillable(['study_plan_id', 'required_course_id', 'target_course_id'])]
class Prerequisite extends Model
{
    /** Solo created_at: un requisito no se edita. */
    public const UPDATED_AT = null;

    /**
     * @return BelongsTo<StudyPlan, $this>
     */
    public function studyPlan(): BelongsTo
    {
        return $this->belongsTo(StudyPlan::class);
    }

    /**
     * @return BelongsTo<Course, $this>
     */
    public function requiredCourse(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'required_course_id');
    }

    /**
     * @return BelongsTo<Course, $this>
     */
    public function targetCourse(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'target_course_id');
    }
}
