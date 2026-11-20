<?php

declare(strict_types=1);

namespace Src\AcademicCatalog\StudyPlan\Domain\Entities;

use DateTimeImmutable;
use InvalidArgumentException;

/** StudyPlan — Aggregate Root. */
final class StudyPlan
{
    private const CLASSIFICATIONS = ['Vigente', 'Terminal'];

    private function __construct(
        private readonly ?int $id,
        private readonly int $degreeProgramId,
        private string $name,
        private int $implementationYear,
        private string $classification,
        private ?DateTimeImmutable $enrollmentClosingDate,
    ) {
        if (! in_array($classification, self::CLASSIFICATIONS, true)) {
            throw new InvalidArgumentException("Clasificación inválida: {$classification}");
        }
        // Corrección del esquema original.
        if ($classification === 'Terminal' && $enrollmentClosingDate === null) {
            throw new InvalidArgumentException('Un plan Terminal exige fecha de cierre de matrícula.');
        }
    }

    public static function create(int $degreeProgramId, string $name, int $implementationYear): self
    {
        return new self(
            id: null,
            degreeProgramId: $degreeProgramId,
            name: $name,
            implementationYear: $implementationYear,
            classification: 'Vigente',
            enrollmentClosingDate: null,
        );
    }

    public static function reconstitute(
        int $id,
        int $degreeProgramId,
        string $name,
        int $implementationYear,
        string $classification,
        ?DateTimeImmutable $enrollmentClosingDate,
    ): self {
        return new self($id, $degreeProgramId, $name, $implementationYear, $classification, $enrollmentClosingDate);
    }

    public function markTerminal(DateTimeImmutable $enrollmentClosingDate): void
    {
        $this->classification = 'Terminal';
        $this->enrollmentClosingDate = $enrollmentClosingDate;
    }

    public function isTerminal(): bool
    {
        return $this->classification === 'Terminal';
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function degreeProgramId(): int
    {
        return $this->degreeProgramId;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function implementationYear(): int
    {
        return $this->implementationYear;
    }

    public function classification(): string
    {
        return $this->classification;
    }

    public function enrollmentClosingDate(): ?DateTimeImmutable
    {
        return $this->enrollmentClosingDate;
    }
}
