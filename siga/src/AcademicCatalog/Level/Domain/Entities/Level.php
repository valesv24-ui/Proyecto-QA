<?php

declare(strict_types=1);

namespace Src\AcademicCatalog\Level\Domain\Entities;

use InvalidArgumentException;

/** Level — Aggregate Root. */
final class Level
{
    private function __construct(
        private readonly ?int $id,
        private readonly int $studyPlanId,
        private int $number,
    ) {
        if ($number < 1) {
            throw new InvalidArgumentException('El número de nivel debe ser mayor que cero.');
        }
    }

    public static function create(int $studyPlanId, int $number): self
    {
        return new self(id: null, studyPlanId: $studyPlanId, number: $number);
    }

    public static function reconstitute(int $id, int $studyPlanId, int $number): self
    {
        return new self($id, $studyPlanId, $number);
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function studyPlanId(): int
    {
        return $this->studyPlanId;
    }

    public function number(): int
    {
        return $this->number;
    }
}
