<?php

namespace App\Models;

use Database\Factories\DegreeProgramFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property bool $is_active
 * @property-read Collection<int, Course> $courses
 * @property-read Collection<int, StudyPlan> $studyPlans
 */
#[Fillable(['name', 'is_active'])]
class DegreeProgram extends Model
{
    /** @use HasFactory<DegreeProgramFactory> */
    use HasFactory;

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * @return HasMany<Course, $this>
     */
    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }

    /**
     * @return HasMany<StudyPlan, $this>
     */
    public function studyPlans(): HasMany
    {
        return $this->hasMany(StudyPlan::class);
    }
}
