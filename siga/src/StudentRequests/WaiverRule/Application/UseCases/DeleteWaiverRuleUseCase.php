<?php

declare(strict_types=1);

namespace Src\StudentRequests\WaiverRule\Application\UseCases;

use Src\StudentRequests\WaiverRule\Domain\Contracts\WaiverRuleRepositoryInterface;
use Src\StudentRequests\WaiverRule\Domain\Exceptions\WaiverRuleNotFoundException;

final class DeleteWaiverRuleUseCase
{
    public function __construct(
        private readonly WaiverRuleRepositoryInterface $repository,
    ) {}

    public function handle(int $id): void
    {
        $this->repository->find($id) ?? throw WaiverRuleNotFoundException::withId($id);

        $this->repository->delete($id);
    }
}
