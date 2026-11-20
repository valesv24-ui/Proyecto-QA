<?php

namespace App\Models;

use Database\Factories\StudentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int|null $user_id
 * @property string $national_id
 * @property string $first_name
 * @property string $last_name
 * @property string|null $second_last_name
 * @property bool $is_active
 * @property-read User|null $user
 * @property-read Collection<int, StudyPlan> $studyPlans
 * @property-read Collection<int, AcademicRecord> $academicRecords
 * @property-read Collection<int, StudentRequest> $studentRequests
 */
#[Fillable(['user_id', 'national_id', 'first_name', 'last_name', 'second_last_name', 'is_active'])]
class Student extends Model
{
    /** @use HasFactory<StudentFactory> */
    use HasFactory;

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsToMany<StudyPlan, $this>
     */
    public function studyPlans(): BelongsToMany
    {
        return $this->belongsToMany(StudyPlan::class, 'student_study_plan')->withPivot('current_level');
    }

    /**
     * @return HasMany<AcademicRecord, $this>
     */
    public function academicRecords(): HasMany
    {
        return $this->hasMany(AcademicRecord::class);
    }

    /**
     * @return HasMany<StudentRequest, $this>
     */
    public function studentRequests(): HasMany
    {
        return $this->hasMany(StudentRequest::class);
    }
}
