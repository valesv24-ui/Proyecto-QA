<?php

declare(strict_types=1);

namespace Src\AcademicCatalog\AcademicTerm\Domain\Entities;

use DateTimeImmutable;
use InvalidArgumentException;

/** AcademicTerm — Aggregate Root. */
final class AcademicTerm
{
    private function __construct(
        private readonly ?int $id,
        private int $year,
        private int $termNumber,
        private DateTimeImmutable $startDate,
        private DateTimeImmutable $endDate,
    ) {
        if ($termNumber < 1 || $termNumber > 3) {
            throw new InvalidArgumentException('El número de cuatrimestre debe estar entre 1 y 3.');
        }
        if ($endDate <= $startDate) {
            throw new InvalidArgumentException('La fecha de fin debe ser posterior a la de inicio.');
        }
    }

    public static function create(int $year, int $termNumber, DateTimeImmutable $startDate, DateTimeImmutable $endDate): self
    {
        return new self(id: null, year: $year, termNumber: $termNumber, startDate: $startDate, endDate: $endDate);
    }

    public static function reconstitute(int $id, int $year, int $termNumber, DateTimeImmutable $startDate, DateTimeImmutable $endDate): self
    {
        return new self(id: $id, year: $year, termNumber: $termNumber, startDate: $startDate, endDate: $endDate);
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function year(): int
    {
        return $this->year;
    }

    public function termNumber(): int
    {
        return $this->termNumber;
    }

    public function startDate(): DateTimeImmutable
    {
        return $this->startDate;
    }

    public function endDate(): DateTimeImmutable
    {
        return $this->endDate;
    }
}
