<?php

namespace App\Models;

use Database\Factories\CourseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int|null $degree_program_id
 * @property string $code
 * @property string $name
 * @property bool $is_service_course
 * @property bool $is_bottleneck
 * @property bool $requires_lab
 * @property string|null $lab_type
 * @property bool $is_active
 * @property-read DegreeProgram|null $degreeProgram
 * @property-read Collection<int, Level> $levels
 * @property-read Collection<int, WaiverRule> $waiverRules
 * @property-read Collection<int, TransferCreditPrecedent> $transferCreditPrecedents
 */
#[Fillable(['degree_program_id', 'code', 'name', 'is_service_course', 'is_bottleneck', 'requires_lab', 'lab_type', 'is_active'])]
class Course extends Model
{
    /** @use HasFactory<CourseFactory> */
    use HasFactory;

    protected $casts = [
        'is_service_course' => 'boolean',
        'is_bottleneck' => 'boolean',
        'requires_lab' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * @return BelongsTo<DegreeProgram, $this>
     */
    public function degreeProgram(): BelongsTo
    {
        return $this->belongsTo(DegreeProgram::class);
    }

    /**
     * @return BelongsToMany<Level, $this>
     */
    public function levels(): BelongsToMany
    {
        return $this->belongsToMany(Level::class, 'course_level')->withPivot('credits');
    }

    /**
     * @return HasMany<WaiverRule, $this>
     */
    public function waiverRules(): HasMany
    {
        return $this->hasMany(WaiverRule::class)->orderBy('evaluation_order');
    }

    /**
     * @return HasMany<TransferCreditPrecedent, $this>
     */
    public function transferCreditPrecedents(): HasMany
    {
        return $this->hasMany(TransferCreditPrecedent::class);
    }
}
