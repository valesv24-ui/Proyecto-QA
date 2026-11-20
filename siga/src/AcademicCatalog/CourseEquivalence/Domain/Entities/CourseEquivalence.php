<?php

declare(strict_types=1);

namespace Src\AcademicCatalog\CourseEquivalence\Domain\Entities;

use InvalidArgumentException;

/** CourseEquivalence — Aggregate Root. */
final class CourseEquivalence
{
    private const DIRECTIONS = ['Anterior a nuevo', 'Nuevo a anterior', 'Bidireccional'];

    private const STATUSES = ['Vigente', 'Sustituida'];

    private function __construct(
        private readonly ?int $id,
        private readonly int $sourceCourseId,
        private readonly int $targetCourseId,
        private string $direction,
        private string $resolutionNumber,
        private string $status,
        private ?int $supersededById,
    ) {
        if ($sourceCourseId === $targetCourseId) {
            throw new InvalidArgumentException('El curso origen y destino no pueden ser el mismo.');
        }
        if (! in_array($direction, self::DIRECTIONS, true)) {
            throw new InvalidArgumentException("Dirección inválida: {$direction}");
        }
        if (! in_array($status, self::STATUSES, true)) {
            throw new InvalidArgumentException("Estado inválido: {$status}");
        }
        // Corrección del esquema original.
        if ($status === 'Sustituida' && $supersededById === null) {
            throw new InvalidArgumentException('Una equiparación Sustituida debe referenciar a la que la reemplaza.');
        }
        if ($status === 'Vigente' && $supersededById !== null) {
            throw new InvalidArgumentException('Una equiparación Vigente no debe tener sustituto.');
        }
    }

    public static function create(int $sourceCourseId, int $targetCourseId, string $direction, string $resolutionNumber): self
    {
        return new self(
            id: null,
            sourceCourseId: $sourceCourseId,
            targetCourseId: $targetCourseId,
            direction: $direction,
            resolutionNumber: $resolutionNumber,
            status: 'Vigente',
            supersededById: null,
        );
    }

    public static function reconstitute(
        int $id,
        int $sourceCourseId,
        int $targetCourseId,
        string $direction,
        string $resolutionNumber,
        string $status,
        ?int $supersededById,
    ): self {
        return new self(
            id: $id,
            sourceCourseId: $sourceCourseId,
            targetCourseId: $targetCourseId,
            direction: $direction,
            resolutionNumber: $resolutionNumber,
            status: $status,
            supersededById: $supersededById,
        );
    }

    public function supersedeBy(int $newEquivalenceId): void
    {
        $this->status = 'Sustituida';
        $this->supersededById = $newEquivalenceId;
    }

    public function isSuperseded(): bool
    {
        return $this->status === 'Sustituida';
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function sourceCourseId(): int
    {
        return $this->sourceCourseId;
    }

    public function targetCourseId(): int
    {
        return $this->targetCourseId;
    }

    public function direction(): string
    {
        return $this->direction;
    }

    public function resolutionNumber(): string
    {
        return $this->resolutionNumber;
    }

    public function status(): string
    {
        return $this->status;
    }

    public function supersededById(): ?int
    {
        return $this->supersededById;
    }
}
