<?php

namespace App\Models;

use Database\Factories\CourseEquivalenceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $source_course_id
 * @property int $target_course_id
 * @property string $direction
 * @property string $resolution_number
 * @property string $status
 * @property int|null $superseded_by_id
 * @property-read Course $sourceCourse
 * @property-read Course $targetCourse
 * @property-read CourseEquivalence|null $supersededBy
 */
#[Fillable(['source_course_id', 'target_course_id', 'direction', 'resolution_number', 'status', 'superseded_by_id'])]
class CourseEquivalence extends Model
{
    /** @use HasFactory<CourseEquivalenceFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Course, $this>
     */
    public function sourceCourse(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'source_course_id');
    }

    /**
     * @return BelongsTo<Course, $this>
     */
    public function targetCourse(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'target_course_id');
    }

    /**
     * @return BelongsTo<CourseEquivalence, $this>
     */
    public function supersededBy(): BelongsTo
    {
        return $this->belongsTo(CourseEquivalence::class, 'superseded_by_id');
    }
}
