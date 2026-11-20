<?php

declare(strict_types=1);

namespace Src\AcademicCatalog\Prerequisite\Domain\Entities;

use InvalidArgumentException;

/** Requisito entre dos cursos, dentro de un plan de estudios. */
final class Prerequisite
{
    private function __construct(
        private readonly ?int $id,
        private readonly int $studyPlanId,
        private readonly int $requiredCourseId,
        private readonly int $targetCourseId,
    ) {
        // Invariante de EXISTENCIA y no solo de creación.
        if ($requiredCourseId === $targetCourseId) {
            throw new InvalidArgumentException('Un curso no puede ser requisito de sí mismo.');
        }
    }

    public static function create(int $studyPlanId, int $requiredCourseId, int $targetCourseId): self
    {
        return new self(null, $studyPlanId, $requiredCourseId, $targetCourseId);
    }

    public static function reconstitute(
        int $id,
        int $studyPlanId,
        int $requiredCourseId,
        int $targetCourseId,
    ): self {
        return new self($id, $studyPlanId, $requiredCourseId, $targetCourseId);
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function studyPlanId(): int
    {
        return $this->studyPlanId;
    }

    public function requiredCourseId(): int
    {
        return $this->requiredCourseId;
    }

    public function targetCourseId(): int
    {
        return $this->targetCourseId;
    }
}
