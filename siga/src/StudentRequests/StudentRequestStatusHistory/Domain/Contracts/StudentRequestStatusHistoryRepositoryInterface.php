<?php

declare(strict_types=1);

namespace Src\StudentRequests\StudentRequestStatusHistory\Domain\Contracts;

use Src\StudentRequests\StudentRequestStatusHistory\Domain\Entities\StudentRequestStatusHistory;

interface StudentRequestStatusHistoryRepositoryInterface
{
    public function find(int $id): ?StudentRequestStatusHistory;

    /**
     * @return array<int, StudentRequestStatusHistory>
     */
    public function forStudentRequest(int $studentRequestId): array;

    public function save(StudentRequestStatusHistory $entry): StudentRequestStatusHistory;
}
