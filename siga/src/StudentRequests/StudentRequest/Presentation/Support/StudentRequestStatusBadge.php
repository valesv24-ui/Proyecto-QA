<?php

declare(strict_types=1);

namespace Src\StudentRequests\StudentRequest\Presentation\Support;

use DateTimeImmutable;
use Src\StudentRequests\StudentRequest\Domain\Entities\StudentRequest;
use Src\StudentRequests\StudentRequest\Domain\Services\BusinessDayCalculator;

/** Traduce (estado + plazo) a la variante de color. */
final class StudentRequestStatusBadge
{
    /** Umbral de "por vencer", en días hábiles restantes. */
    private const DUE_SOON_BUSINESS_DAYS = 2;

    public function __construct(
        private readonly BusinessDayCalculator $businessDays,
    ) {}

    /** Clase CSS de .status-badge para una solicitud. */
    public function variantFor(
        string $status,
        ?string $engineResult,
        ?DateTimeImmutable $estimatedResolutionDate,
        DateTimeImmutable $now,
    ): string {
        if ($status === StudentRequest::STATUS_APPROVED) {
            return $engineResult === StudentRequest::RESULT_AUTOMATIC ? 'success' : 'info';
        }

        if ($status === StudentRequest::STATUS_DENIED) {
            return 'danger';
        }

        // Abierta sin fecha estimada todavía.
        if ($estimatedResolutionDate === null) {
            return 'neutral';
        }

        $remaining = $this->businessDays->businessDaysBetween($now, $estimatedResolutionDate);

        if ($remaining < 0) {
            return 'danger-solid';
        }

        return $remaining <= self::DUE_SOON_BUSINESS_DAYS ? 'warning' : 'neutral';
    }

    /** Texto corto de plazo para acompañar al badge. */
    public function deadlineLabel(
        string $status,
        ?DateTimeImmutable $estimatedResolutionDate,
        DateTimeImmutable $now,
    ): ?string {
        if (in_array($status, [StudentRequest::STATUS_APPROVED, StudentRequest::STATUS_DENIED], true)) {
            return null;
        }

        if ($estimatedResolutionDate === null) {
            return null;
        }

        $remaining = $this->businessDays->businessDaysBetween($now, $estimatedResolutionDate);

        if ($remaining < 0) {
            return __('Overdue by :days business days', ['days' => abs($remaining)]);
        }

        if ($remaining === 0) {
            return __('Due today');
        }

        return __('Due in :days business days', ['days' => $remaining]);
    }
}
