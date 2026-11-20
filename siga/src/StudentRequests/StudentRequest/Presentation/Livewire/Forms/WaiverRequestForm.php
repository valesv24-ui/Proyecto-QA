<?php

declare(strict_types=1);

namespace Src\StudentRequests\StudentRequest\Presentation\Livewire\Forms;

use Illuminate\Validation\Rule;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\Form;
use Src\StudentRequests\WaiverRule\Domain\Entities\WaiverRule;

/** ES-01: la boleta SLR-002 del lado del estudiante. */
class WaiverRequestForm extends Form
{
    public ?int $courseId = null;

    public ?int $prerequisiteCourseId = null;

    /** La justificación marcada en la boleta. */
    public string $justification = '';

    /** Aceptación de las notas importantes de la boleta. */
    public bool $acceptsTerms = false;

    /**
     * @var TemporaryUploadedFile|null
     */
    public $attachment = null;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'courseId' => ['required', 'integer', 'exists:courses,id'],
            'prerequisiteCourseId' => ['required', 'integer', 'exists:courses,id', 'different:courseId'],
            // Solo las CINCO de la boleta.
            'justification' => ['required', Rule::in(WaiverRule::STUDENT_JUSTIFICATIONS)],
            // 'accepted' y no 'boolean': exige que valga verdadero.
            'acceptsTerms' => ['accepted'],
            // 10240 KB (10 MB) para que coincida con Attachment::MAX_SIZE_BYTES.
            'attachment' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function validationAttributes(): array
    {
        return [
            'justification' => __('justification'),
            'acceptsTerms' => __('acceptance of the important notes'),
        ];
    }
}
