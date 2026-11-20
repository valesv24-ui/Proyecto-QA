<?php

declare(strict_types=1);

namespace Src\StudentRequests\StudentRequest\Domain\Contracts;

/** Puerto. */
interface ExternalInstitutionDirectoryInterface
{
    /**
     * Nombres de instituciones que coinciden con el texto dado.
     *
     * @return array<int, string>
     */
    public function search(string $query): array;
}
