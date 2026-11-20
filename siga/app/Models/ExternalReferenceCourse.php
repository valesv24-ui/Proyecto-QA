<?php

namespace App\Models;

use Database\Factories\ExternalReferenceCourseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Catálogo de referencia de cursos de otras instituciones.
 *
 * @property int $id
 * @property string $institution
 * @property string $program_reference
 * @property string|null $track
 * @property string $code
 * @property string $name
 * @property int|null $cycle
 * @property int|null $credits
 */
#[Fillable(['institution', 'program_reference', 'track', 'code', 'name', 'cycle', 'credits'])]
class ExternalReferenceCourse extends Model
{
    /** @use HasFactory<ExternalReferenceCourseFactory> */
    use HasFactory;
}
