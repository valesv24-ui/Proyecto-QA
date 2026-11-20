<?php

declare(strict_types=1);

namespace Src\StudentRequests\WaiverRule\Domain\Entities;

use InvalidArgumentException;

/** WaiverRule — Aggregate Root. */
final class WaiverRule
{
    /** Justificación (a) de la boleta SLR-002 V.3. */
    public const TYPE_ONLY_PENDING_COURSE = 'El curso es el único pendiente para avanzar hacia el siguiente nivel del plan de estudios.';

    /** Justificación (b). */
    public const TYPE_LAST_LEVEL_PARALLEL = 'El estudiante matricula su último nivel del pregrado o grado académico, siempre y cuando curse el requisito en forma paralela.';

    /** Justificación (c). */
    public const TYPE_DELAYED_OFFERING = 'El curso se ofertará dos o tres cuatrimestres después y constituye un requisito importante para el avance en el plan de estudios, lo que significa para el estudiante un atraso de dos cuatrimestres, un año o más en su carrera.';

    /** Justificación (d). */
    public const TYPE_MINIMUM_ACADEMIC_LOAD = 'Situación especial de carga académica mínima para cursar un cuatrimestre.';

    /** Justificación (e). */
    public const TYPE_PRIOR_KNOWLEDGE = 'El estudiante manifiesta formalmente que tiene conocimiento previo del requisito y va a optar por la suficiencia del mismo en este cuatrimestre.';

    /** NO está en la boleta. */
    public const TYPE_SPECIAL_CASE = 'Solicitud de revisión manual por caso especial';

    /**
     * Las cinco que el estudiante puede elegir.
     *
     * @var array<int, string>
     */
    public const STUDENT_JUSTIFICATIONS = [
        self::TYPE_ONLY_PENDING_COURSE,
        self::TYPE_LAST_LEVEL_PARALLEL,
        self::TYPE_DELAYED_OFFERING,
        self::TYPE_MINIMUM_ACADEMIC_LOAD,
        self::TYPE_PRIOR_KNOWLEDGE,
    ];

    /**
     * Todo lo que Docencia puede configurar.
     *
     * @var array<int, string>
     */
    public const TYPES = [
        ...self::STUDENT_JUSTIFICATIONS,
        self::TYPE_SPECIAL_CASE,
    ];

    private function __construct(
        private readonly ?int $id,
        private readonly int $courseId,
        private int $evaluationOrder,
        private string $type,
        private bool $isActive,
    ) {
        if (! in_array($type, self::TYPES, true)) {
            throw new InvalidArgumentException("Tipo de regla inválido: {$type}");
        }

        if ($evaluationOrder < 1) {
            throw new InvalidArgumentException('El orden de evaluación debe ser mayor que cero.');
        }
    }

    public static function create(int $courseId, int $evaluationOrder, string $type): self
    {
        return new self(
            id: null,
            courseId: $courseId,
            evaluationOrder: $evaluationOrder,
            type: $type,
            isActive: true,
        );
    }

    public static function reconstitute(
        int $id,
        int $courseId,
        int $evaluationOrder,
        string $type,
        bool $isActive,
    ): self {
        return new self($id, $courseId, $evaluationOrder, $type, $isActive);
    }

    /** ¿Esta regla admite la justificación que eligió el estudiante?. */
    public function admits(string $justification): bool
    {
        if (! $this->isActive) {
            return false;
        }

        return $this->type === self::TYPE_SPECIAL_CASE || $this->type === $justification;
    }

    public function activate(): void
    {
        $this->isActive = true;
    }

    public function deactivate(): void
    {
        $this->isActive = false;
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function courseId(): int
    {
        return $this->courseId;
    }

    public function evaluationOrder(): int
    {
        return $this->evaluationOrder;
    }

    public function type(): string
    {
        return $this->type;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }
}
