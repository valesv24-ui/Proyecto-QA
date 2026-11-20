<?php

declare(strict_types=1);

namespace Src\StudentRequests\StudentRequestStatusHistory\Domain\Entities;

use DateTimeImmutable;

/** StudentRequestStatusHistory — Aggregate Root. */
final class StudentRequestStatusHistory
{
    private function __construct(
        private readonly ?int $id,
        private readonly int $studentRequestId,
        private readonly ?string $previousStatus,
        private readonly string $newStatus,
        private readonly ?string $comment,
        private readonly ?int $userId,
        private ?DateTimeImmutable $notifiedAt,
        private readonly ?DateTimeImmutable $occurredAt = null,
    ) {}

    public static function record(int $studentRequestId, ?string $previousStatus, string $newStatus, ?int $userId, ?string $comment = null): self
    {
        return new self(
            id: null,
            studentRequestId: $studentRequestId,
            previousStatus: $previousStatus,
            newStatus: $newStatus,
            comment: $comment,
            userId: $userId,
            notifiedAt: null,
            occurredAt: null,
        );
    }

    public static function reconstitute(
        int $id,
        int $studentRequestId,
        ?string $previousStatus,
        string $newStatus,
        ?string $comment,
        ?int $userId,
        ?DateTimeImmutable $notifiedAt,
        ?DateTimeImmutable $occurredAt = null,
    ): self {
        return new self($id, $studentRequestId, $previousStatus, $newStatus, $comment, $userId, $notifiedAt, $occurredAt);
    }

    public function markNotified(DateTimeImmutable $when): void
    {
        $this->notifiedAt = $when;
    }

    public function wasNotified(): bool
    {
        return $this->notifiedAt !== null;
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function studentRequestId(): int
    {
        return $this->studentRequestId;
    }

    public function previousStatus(): ?string
    {
        return $this->previousStatus;
    }

    public function newStatus(): string
    {
        return $this->newStatus;
    }

    public function comment(): ?string
    {
        return $this->comment;
    }

    public function userId(): ?int
    {
        return $this->userId;
    }

    public function notifiedAt(): ?DateTimeImmutable
    {
        return $this->notifiedAt;
    }

    /** Momento en que ocurrió el cambio de estado. */
    public function occurredAt(): ?DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
