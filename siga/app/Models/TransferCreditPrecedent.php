<?php

namespace App\Models;

use Database\Factories\TransferCreditPrecedentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Catálogo de convalidaciones históricas (ES-02).
 *
 * @property int $id
 * @property string $institution
 * @property string $external_course
 * @property int $course_id
 * @property string $outcome
 * @property string $resolution_number
 * @property-read Course $course
 */
#[Fillable(['institution', 'external_course', 'course_id', 'outcome', 'resolution_number'])]
class TransferCreditPrecedent extends Model
{
    /** @use HasFactory<TransferCreditPrecedentFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Course, $this>
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}
