<?php

declare(strict_types=1);

namespace Src\StudentRequests\TransferCreditPrecedent\Domain\Contracts;

use Src\StudentRequests\TransferCreditPrecedent\Domain\Entities\TransferCreditPrecedent;

interface TransferCreditPrecedentRepositoryInterface
{
    public function find(int $id): ?TransferCreditPrecedent;

    /** ES-02: busca si la combinación institución + curso externo ya. */
    public function findByInstitutionAndExternalCourse(string $institution, string $externalCourse): ?TransferCreditPrecedent;

    public function save(TransferCreditPrecedent $precedent): TransferCreditPrecedent;

    public function delete(int $id): void;

    /**
     * @return array<int, TransferCreditPrecedent>
     */
    public function all(?string $search = null, ?string $sortBy = null, string $sortDir = 'asc'): array;

    /**
     * @return array{items: array<int, TransferCreditPrecedent>, total: int}
     */
    public function paginate(?string $search, int $perPage, int $page, ?string $sortBy = null, string $sortDir = 'asc'): array;
}
