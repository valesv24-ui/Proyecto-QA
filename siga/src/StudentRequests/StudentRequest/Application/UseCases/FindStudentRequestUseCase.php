<?php

declare(strict_types=1);

namespace Src\StudentRequests\StudentRequest\Application\UseCases;

use Src\StudentRequests\StudentRequest\Domain\Contracts\StudentRequestRepositoryInterface;
use Src\StudentRequests\StudentRequest\Domain\Entities\StudentRequest;
use Src\StudentRequests\StudentRequest\Domain\Exceptions\StudentRequestNotFoundException;

final class FindStudentRequestUseCase
{
    public function __construct(
        private readonly StudentRequestRepositoryInterface $repository,
    ) {}

    public function handle(int $id): StudentRequest
    {
        return $this->repository->find($id) ?? throw StudentRequestNotFoundException::withId($id);
    }
}
