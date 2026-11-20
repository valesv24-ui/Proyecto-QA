<?php

declare(strict_types=1);

namespace Src\StudentRequests\WaiverRule\Application\UseCases;

use Src\StudentRequests\WaiverRule\Application\DTOs\WaiverRuleDTO;
use Src\StudentRequests\WaiverRule\Domain\Contracts\WaiverRuleRepositoryInterface;
use Src\StudentRequests\WaiverRule\Domain\Entities\WaiverRule;
use Src\StudentRequests\WaiverRule\Domain\Exceptions\WaiverRuleNotFoundException;

/** A diferencia de Role::rename(), WaiverRule no tiene un mutador genérico. */
final class UpdateWaiverRuleUseCase
{
    public function __construct(
        private readonly WaiverRuleRepositoryInterface $repository,
    ) {}

    public function handle(int $id, WaiverRuleDTO $dto): WaiverRule
    {
        $this->repository->find($id) ?? throw WaiverRuleNotFoundException::withId($id);

        $waiverRule = WaiverRule::reconstitute(
            id: $id,
            courseId: $dto->courseId,
            evaluationOrder: $dto->evaluationOrder,
            type: $dto->type,
            isActive: $dto->isActive,
        );

        return $this->repository->save($waiverRule);
    }
}
