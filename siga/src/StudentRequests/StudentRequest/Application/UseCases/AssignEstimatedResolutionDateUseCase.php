<?php

declare(strict_types=1);

namespace Src\StudentRequests\StudentRequest\Application\UseCases;

use DateTimeImmutable;
use Src\StudentRequests\StudentRequest\Domain\Contracts\StudentRequestRepositoryInterface;
use Src\StudentRequests\StudentRequest\Domain\Entities\StudentRequest;
use Src\StudentRequests\StudentRequest\Domain\Exceptions\StudentRequestNotFoundException;

/** ES-03. */
final class AssignEstimatedResolutionDateUseCase
{
    public function __construct(
        private readonly StudentRequestRepositoryInterface $repository,
    ) {}

    public function handle(int $id, DateTimeImmutable $date): StudentRequest
    {
        $studentRequest = $this->repository->find($id) ?? throw StudentRequestNotFoundException::withId($id);

        $studentRequest->assignEstimatedResolutionDate($date);

        return $this->repository->save($studentRequest);
    }
}
