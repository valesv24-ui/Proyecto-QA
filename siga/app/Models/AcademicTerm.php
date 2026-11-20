<?php

namespace App\Models;

use Database\Factories\AcademicTermFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $year
 * @property int $term_number
 * @property Carbon $start_date
 * @property Carbon $end_date
 * @property-read Collection<int, AcademicRecord> $academicRecords
 */
#[Fillable(['year', 'term_number', 'start_date', 'end_date'])]
class AcademicTerm extends Model
{
    /** @use HasFactory<AcademicTermFactory> */
    use HasFactory;

    protected $casts = [
        'year' => 'integer',
        'term_number' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    /**
     * @return HasMany<AcademicRecord, $this>
     */
    public function academicRecords(): HasMany
    {
        return $this->hasMany(AcademicRecord::class);
    }
}
