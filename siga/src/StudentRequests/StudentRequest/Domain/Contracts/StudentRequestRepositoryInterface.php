<?php

declare(strict_types=1);

namespace Src\StudentRequests\StudentRequest\Domain\Contracts;

use Src\StudentRequests\StudentRequest\Domain\Entities\StudentRequest;

interface StudentRequestRepositoryInterface
{
    public function find(int $id): ?StudentRequest;

    /** ES-01. */
    public function findApprovedWaiverDuplicate(int $studentId, int $courseId, int $prerequisiteCourseId): ?StudentRequest;

    /**
     * ES-03: solicitudes activas de un estudiante, más recientes primero.
     *
     * @return array<int, StudentRequest>
     */
    public function forStudent(int $studentId): array;

    /**
     * ES-04: bandeja centralizada, ordenable.
     *
     * @param  array{search?: string, type?: string, status?: string, career?: string, dateFrom?: string, dateTo?: string, openOnly?: bool, resolvedOnly?: bool, deskStatuses?: array<int, string>}  $filters
     * @return array{items: array<int, StudentRequest>, total: int}
     */
    public function paginateForInbox(array $filters, string $sortBy, string $sortDir, int $perPage, int $page): array;

    /**
     * Ids de solicitudes guardadas SIN fecha estimada de resolución.
     *
     * @return array<int, int>
     */
    public function idsWithoutEstimatedResolutionDate(): array;

    /**
     * Conteos de la bandeja para el panel principal.
     *
     * @return array<int, array{type: string, status: string, total: int, overdue: int, dueSoon: int}>
     */
    public function inboxSummary(string $today, string $dueSoonLimit): array;

    /**
     * Solicitudes abiertas cuyo plazo vence hoy o ya venció.
     *
     * @param  array<int, string>|null  $deskStatuses  Si se da, solo cuenta lo que le toca a ESE escritorio.
     * @return array<int, StudentRequest>
     */
    public function openPastDue(string $today, int $limit, ?array $deskStatuses = null): array;

    public function save(StudentRequest $studentRequest): StudentRequest;

    public function delete(int $id): void;
}
