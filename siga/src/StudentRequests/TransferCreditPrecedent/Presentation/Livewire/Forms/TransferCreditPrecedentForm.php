<?php

declare(strict_types=1);

namespace Src\StudentRequests\TransferCreditPrecedent\Presentation\Livewire\Forms;

use Illuminate\Validation\Rule;
use Livewire\Form;
use Src\StudentRequests\TransferCreditPrecedent\Application\DTOs\TransferCreditPrecedentDTO;
use Src\StudentRequests\TransferCreditPrecedent\Domain\Entities\TransferCreditPrecedent;
use Src\StudentRequests\TransferCreditPrecedent\Presentation\Livewire\TransferCreditPrecedentComponent;

class TransferCreditPrecedentForm extends Form
{
    public string $institution = '';

    public string $externalCourse = '';

    public ?int $courseId = null;

    public string $outcome = 'Aprobada';

    public string $resolutionNumber = '';

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var TransferCreditPrecedentComponent $component */
        $component = $this->component;

        return [
            'institution' => ['required', 'string', 'max:150'],
            'externalCourse' => [
                'required',
                'string',
                'max:150',
                // ES-02: la combinación institución + curso externo.
                Rule::unique('transfer_credit_precedents', 'external_course')
                    ->where(fn ($query) => $query->where('institution', $this->institution))
                    ->ignore($component->editingId),
            ],
            'courseId' => ['required', 'integer', 'exists:courses,id'],
            'outcome' => ['required', Rule::in(['Aprobada', 'Denegada'])],
            'resolutionNumber' => ['required', 'string', 'max:60'],
        ];
    }

    public function fromEntity(TransferCreditPrecedent $precedent): void
    {
        $this->institution = $precedent->institution();
        $this->externalCourse = $precedent->externalCourse();
        $this->courseId = $precedent->courseId();
        $this->outcome = $precedent->outcome();
        $this->resolutionNumber = $precedent->resolutionNumber();
    }

    public function toDto(): TransferCreditPrecedentDTO
    {
        // courseId es nullable en la propiedad.
        return new TransferCreditPrecedentDTO(
            institution: $this->institution,
            externalCourse: $this->externalCourse,
            courseId: (int) $this->courseId,
            outcome: $this->outcome,
            resolutionNumber: $this->resolutionNumber,
        );
    }
}
