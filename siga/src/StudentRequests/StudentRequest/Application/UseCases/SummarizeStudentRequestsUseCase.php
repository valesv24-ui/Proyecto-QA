<?php

declare(strict_types=1);

namespace Src\StudentRequests\StudentRequest\Application\UseCases;

use Src\Shared\Clock\Domain\Contracts\ClockInterface;
use Src\StudentRequests\StudentRequest\Domain\Contracts\StudentRequestRepositoryInterface;
use Src\StudentRequests\StudentRequest\Domain\Entities\StudentRequest;
use Src\StudentRequests\StudentRequest\Domain\Services\BusinessDayCalculator;

/** Resumen de la bandeja para el panel principal. */
final class SummarizeStudentRequestsUseCase
{
    /** Mismo umbral que StudentRequestStatusBadge usa para pintar en ámbar. */
    private const DUE_SOON_BUSINESS_DAYS = 2;

    public function __construct(
        private readonly StudentRequestRepositoryInterface $repository,
        private readonly BusinessDayCalculator $businessDays,
        private readonly ClockInterface $clock,
    ) {}

    /**
     * @param  array<int, string>|null  $deskStatuses  Si se da, el resumen solo cuenta lo que le toca a ESE escritorio (Docencia o Registro).
     * @return array{
     *     byType: array<string, array{open: int, pending: int, inReview: int, approved: int, denied: int, overdue: int, dueSoon: int}>,
     *     totals: array{open: int, overdue: int, dueSoon: int, resolved: int}
     * }
     */
    public function handle(?array $deskStatuses = null): array
    {
        $today = $this->clock->now();
        $dueSoonLimit = $this->businessDays->addBusinessDays($today, self::DUE_SOON_BUSINESS_DAYS);

        $rows = $this->repository->inboxSummary(
            $today->format('Y-m-d'),
            $dueSoonLimit->format('Y-m-d'),
        );

        // Acumulador de trabajo con claves dinámicas, deliberadamente separado del resultado.
        $tally = [
            StudentRequest::TYPE_WAIVER => $this->emptyBucket(),
            StudentRequest::TYPE_TRANSFER => $this->emptyBucket(),
        ];

        foreach ($rows as $row) {
            $type = $row['type'];

            if (! isset($tally[$type])) {
                continue;
            }

            // Filas de estados que no le tocan a este escritorio, fuera del resumen.
            if ($deskStatuses !== null && ! in_array($row['status'], $deskStatuses, true)) {
                continue;
            }

            $tally[$type][$this->bucketKeyFor($row['status'])] += $row['total'];

            // Vencido y por vencer solo cuentan sobre solicitudes ABIERTAS.
            if (! $this->isOpen($row['status'])) {
                continue;
            }

            $tally[$type]['open'] += $row['total'];
            $tally[$type]['overdue'] += $row['overdue'];
            $tally[$type]['dueSoon'] += $row['dueSoon'];
        }

        $byType = [];
        foreach ($tally as $type => $counts) {
            $byType[$type] = [
                'open' => $counts['open'],
                'pending' => $counts['pending'],
                'inReview' => $counts['inReview'],
                'approved' => $counts['approved'],
                'denied' => $counts['denied'],
                'overdue' => $counts['overdue'],
                'dueSoon' => $counts['dueSoon'],
            ];
        }

        return [
            'byType' => $byType,
            'totals' => [
                'open' => $this->sum($tally, 'open'),
                'overdue' => $this->sum($tally, 'overdue'),
                'dueSoon' => $this->sum($tally, 'dueSoon'),
                'resolved' => $this->sum($tally, 'approved') + $this->sum($tally, 'denied'),
            ],
        ];
    }

    /**
     * Solicitudes abiertas cuyo plazo vence hoy o ya venció.
     *
     * @param  array<int, string>|null  $deskStatuses  Si se da, solo lista lo que le toca a ESE escritorio.
     * @return array<int, StudentRequest>
     */
    public function pastDue(int $limit = 5, ?array $deskStatuses = null): array
    {
        return $this->repository->openPastDue($this->clock->now()->format('Y-m-d'), $limit, $deskStatuses);
    }

    private function isOpen(string $status): bool
    {
        return in_array($status, StudentRequest::OPEN_STATUSES, true);
    }

    /** "Verificada por Registro" cae en el mismo casillero. */
    private function bucketKeyFor(string $status): string
    {
        return match ($status) {
            StudentRequest::STATUS_PENDING => 'pending',
            StudentRequest::STATUS_VERIFIED, StudentRequest::STATUS_IN_REVIEW, StudentRequest::STATUS_DECIDED => 'inReview',
            StudentRequest::STATUS_APPROVED => 'approved',
            default => 'denied',
        };
    }

    /**
     * @return array<string, int>
     */
    private function emptyBucket(): array
    {
        return [
            'open' => 0,
            'pending' => 0,
            'inReview' => 0,
            'approved' => 0,
            'denied' => 0,
            'overdue' => 0,
            'dueSoon' => 0,
        ];
    }

    /**
     * @param  array<string, array<string, int>>  $byType
     */
    private function sum(array $byType, string $key): int
    {
        $total = 0;
        foreach ($byType as $bucket) {
            $total += $bucket[$key];
        }

        return $total;
    }
}
