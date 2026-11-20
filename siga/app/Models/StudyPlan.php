<?php

namespace App\Models;

use Database\Factories\StudyPlanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $degree_program_id
 * @property string $name
 * @property int $implementation_year
 * @property string $classification
 * @property Carbon|null $enrollment_closing_date
 * @property-read DegreeProgram $degreeProgram
 * @property-read Collection<int, Level> $levels
 * @property-read Collection<int, Student> $students
 */
#[Fillable(['degree_program_id', 'name', 'implementation_year', 'classification', 'enrollment_closing_date'])]
class StudyPlan extends Model
{
    /** @use HasFactory<StudyPlanFactory> */
    use HasFactory;

    protected $casts = [
        'implementation_year' => 'integer',
        'enrollment_closing_date' => 'date',
    ];

    /**
     * @return BelongsTo<DegreeProgram, $this>
     */
    public function degreeProgram(): BelongsTo
    {
        return $this->belongsTo(DegreeProgram::class);
    }

    /**
     * @return HasMany<Level, $this>
     */
    public function levels(): HasMany
    {
        return $this->hasMany(Level::class);
    }

    /**
     * @return BelongsToMany<Student, $this>
     */
    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'student_study_plan')->withPivot('current_level');
    }
}
