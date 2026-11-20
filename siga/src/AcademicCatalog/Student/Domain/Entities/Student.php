<?php

declare(strict_types=1);

namespace Src\AcademicCatalog\Student\Domain\Entities;

/** Student — Aggregate Root. */
final class Student
{
    private function __construct(
        private readonly ?int $id,
        private readonly ?int $userId,
        private string $nationalId,
        private string $firstName,
        private string $lastName,
        private ?string $secondLastName,
        private bool $isActive,
    ) {}

    public static function create(
        ?int $userId,
        string $nationalId,
        string $firstName,
        string $lastName,
        ?string $secondLastName = null,
    ): self {
        return new self(
            id: null,
            userId: $userId,
            nationalId: $nationalId,
            firstName: $firstName,
            lastName: $lastName,
            secondLastName: $secondLastName,
            isActive: true,
        );
    }

    public static function reconstitute(
        int $id,
        ?int $userId,
        string $nationalId,
        string $firstName,
        string $lastName,
        ?string $secondLastName,
        bool $isActive,
    ): self {
        return new self($id, $userId, $nationalId, $firstName, $lastName, $secondLastName, $isActive);
    }

    public function fullName(): string
    {
        return trim("{$this->firstName} {$this->lastName} ".($this->secondLastName ?? ''));
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function userId(): ?int
    {
        return $this->userId;
    }

    public function nationalId(): string
    {
        return $this->nationalId;
    }

    public function firstName(): string
    {
        return $this->firstName;
    }

    public function lastName(): string
    {
        return $this->lastName;
    }

    public function secondLastName(): ?string
    {
        return $this->secondLastName;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }
}
