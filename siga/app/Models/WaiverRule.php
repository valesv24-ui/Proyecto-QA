<?php

namespace App\Models;

use Database\Factories\WaiverRuleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Regla de levantamiento configurada por la Coordinadora (ES-01).
 *
 * @property int $id
 * @property int $course_id
 * @property int $evaluation_order
 * @property string $type
 * @property bool $is_active
 * @property-read Course $course
 */
#[Fillable(['course_id', 'evaluation_order', 'type', 'is_active'])]
class WaiverRule extends Model
{
    /** @use HasFactory<WaiverRuleFactory> */
    use HasFactory;

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * @return BelongsTo<Course, $this>
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}
