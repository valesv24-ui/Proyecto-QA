<?php

namespace App\Models;

use Database\Factories\LevelFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property int $study_plan_id
 * @property int $number
 * @property-read StudyPlan $studyPlan
 * @property-read Collection<int, Course> $courses
 */
#[Fillable(['study_plan_id', 'number'])]
class Level extends Model
{
    /** @use HasFactory<LevelFactory> */
    use HasFactory;

    protected $casts = [
        'number' => 'integer',
    ];

    /**
     * @return BelongsTo<StudyPlan, $this>
     */
    public function studyPlan(): BelongsTo
    {
        return $this->belongsTo(StudyPlan::class);
    }

    /**
     * @return BelongsToMany<Course, $this>
     */
    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'course_level')->withPivot('credits');
    }
}
