<?php

declare(strict_types=1);

namespace Src\StudentRequests\StudentRequest\Application\UseCases;

use Src\StudentRequests\StudentRequest\Domain\Contracts\ExternalInstitutionDirectoryInterface;

/** Sugerencias de institución de origen para el formulario de convalidación. */
final class SearchSourceInstitutionsUseCase
{
    public function __construct(
        private readonly ExternalInstitutionDirectoryInterface $directory,
    ) {}

    /**
     * @return array<int, string>
     */
    public function handle(string $query): array
    {
        return $this->directory->search($query);
    }
}
