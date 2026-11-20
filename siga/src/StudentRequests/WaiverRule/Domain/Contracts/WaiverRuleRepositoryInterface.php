<?php

declare(strict_types=1);

namespace Src\StudentRequests\WaiverRule\Domain\Contracts;

use Src\StudentRequests\WaiverRule\Domain\Entities\WaiverRule;

interface WaiverRuleRepositoryInterface
{
    public function find(int $id): ?WaiverRule;

    /**
     * Reglas activas de un curso.
     *
     * @return array<int, WaiverRule>
     */
    public function activeForCourseOrdered(int $courseId): array;

    /**
     * Catálogo completo para la pantalla de administración de la Coordinadora.
     *
     * @return array<int, WaiverRule>
     */
    public function all(?string $search = null, ?string $sortBy = null, string $sortDir = 'asc'): array;

    /**
     * @return array{items: array<int, WaiverRule>, total: int}
     */
    public function paginate(?string $search, int $perPage, int $page, ?string $sortBy = null, string $sortDir = 'asc'): array;

    public function save(WaiverRule $waiverRule): WaiverRule;

    public function delete(int $id): void;
}
