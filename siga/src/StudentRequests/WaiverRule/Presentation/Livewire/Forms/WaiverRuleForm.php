<?php

declare(strict_types=1);

namespace Src\StudentRequests\WaiverRule\Presentation\Livewire\Forms;

use Illuminate\Validation\Rule;
use Livewire\Form;
use Src\StudentRequests\WaiverRule\Application\DTOs\WaiverRuleDTO;
use Src\StudentRequests\WaiverRule\Domain\Entities\WaiverRule;
use Src\StudentRequests\WaiverRule\Presentation\Livewire\WaiverRuleComponent;

/** Formulario de reglas. */
class WaiverRuleForm extends Form
{
    public ?int $courseId = null;

    public int $evaluationOrder = 1;

    public string $type = WaiverRule::TYPE_SPECIAL_CASE;

    public bool $isActive = true;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var WaiverRuleComponent $component */
        $component = $this->component;

        return [
            'courseId' => ['required', 'integer', 'exists:courses,id'],
            'evaluationOrder' => [
                'required',
                'integer',
                'min:1',
                Rule::unique('waiver_rules', 'evaluation_order')
                    ->where(fn ($query) => $query->where('course_id', $this->courseId))
                    ->ignore($component->editingId),
            ],
            // La lista sale de la Entidad, no se reescribe acá.
            'type' => ['required', Rule::in(WaiverRule::TYPES)],
            'isActive' => ['boolean'],
        ];
    }

    public function fromEntity(WaiverRule $waiverRule): void
    {
        $this->courseId = $waiverRule->courseId();
        $this->evaluationOrder = $waiverRule->evaluationOrder();
        $this->type = $waiverRule->type();
        $this->isActive = $waiverRule->isActive();
    }

    public function toDto(): WaiverRuleDTO
    {
        // courseId es nullable en la propiedad.
        return new WaiverRuleDTO(
            courseId: (int) $this->courseId,
            evaluationOrder: $this->evaluationOrder,
            type: $this->type,
            isActive: $this->isActive,
        );
    }
}
