<?php

declare(strict_types=1);

namespace Src\AcademicCatalog\DegreeProgram\Domain\Entities;

/** DegreeProgram — Aggregate Root of the DegreeProgram bounded context. */
final class DegreeProgram
{
    private function __construct(
        private readonly ?int $id,
        private string $name,
        private bool $isActive,
    ) {}

    public static function create(string $name): self
    {
        return new self(id: null, name: $name, isActive: true);
    }

    public static function reconstitute(int $id, string $name, bool $isActive): self
    {
        return new self(id: $id, name: $name, isActive: $isActive);
    }

    public function rename(string $name): void
    {
        $this->name = $name;
    }

    public function deactivate(): void
    {
        $this->isActive = false;
    }

    public function activate(): void
    {
        $this->isActive = true;
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }
}
