<?php

declare(strict_types=1);

namespace Src\AcademicCatalog\Course\Domain\Entities;

use InvalidArgumentException;

/** Course — Aggregate Root. */
final class Course
{
    private const LAB_TYPES = ['Laboratorio de cómputo', 'Laboratorio de ciencias', 'Laboratorio de idiomas'];

    private function __construct(
        private readonly ?int $id,
        private readonly ?int $degreeProgramId,
        private string $code,
        private string $name,
        private bool $isServiceCourse,
        private bool $requiresLab,
        private ?string $labType,
        private bool $isActive,
    ) {
        // Corrección del esquema original.
        if (! $isServiceCourse && $degreeProgramId === null) {
            throw new InvalidArgumentException('Un curso no transversal debe pertenecer a una carrera.');
        }
        if ($requiresLab && $labType === null) {
            throw new InvalidArgumentException('Un curso que requiere laboratorio debe indicar el tipo.');
        }
        if (! $requiresLab && $labType !== null) {
            throw new InvalidArgumentException('Un curso que no requiere laboratorio no debe tener un tipo asignado.');
        }
        if ($labType !== null && ! in_array($labType, self::LAB_TYPES, true)) {
            throw new InvalidArgumentException("Tipo de laboratorio inválido: {$labType}");
        }
    }

    public static function create(
        ?int $degreeProgramId,
        string $code,
        string $name,
        bool $isServiceCourse = false,
        bool $requiresLab = false,
        ?string $labType = null,
    ): self {
        return new self(
            id: null,
            degreeProgramId: $degreeProgramId,
            code: $code,
            name: $name,
            isServiceCourse: $isServiceCourse,
            requiresLab: $requiresLab,
            labType: $labType,
            isActive: true,
        );
    }

    public static function reconstitute(
        int $id,
        ?int $degreeProgramId,
        string $code,
        string $name,
        bool $isServiceCourse,
        bool $requiresLab,
        ?string $labType,
        bool $isActive,
    ): self {
        return new self($id, $degreeProgramId, $code, $name, $isServiceCourse, $requiresLab, $labType, $isActive);
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function degreeProgramId(): ?int
    {
        return $this->degreeProgramId;
    }

    public function code(): string
    {
        return $this->code;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function isServiceCourse(): bool
    {
        return $this->isServiceCourse;
    }

    public function requiresLab(): bool
    {
        return $this->requiresLab;
    }

    public function labType(): ?string
    {
        return $this->labType;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }
}
