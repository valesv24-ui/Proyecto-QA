<?php

declare(strict_types=1);

namespace Src\StudentRequests\WaiverRule\Application\UseCases;

use Src\StudentRequests\WaiverRule\Application\DTOs\WaiverRuleDTO;
use Src\StudentRequests\WaiverRule\Domain\Contracts\WaiverRuleRepositoryInterface;
use Src\StudentRequests\WaiverRule\Domain\Entities\WaiverRule;

/** Antes este caso de uso tenía un `match` que elegía. */
final class CreateWaiverRuleUseCase
{
    public function __construct(
        private readonly WaiverRuleRepositoryInterface $repository,
    ) {}

    public function handle(WaiverRuleDTO $dto): WaiverRule
    {
        $waiverRule = WaiverRule::create(
            courseId: $dto->courseId,
            evaluationOrder: $dto->evaluationOrder,
            type: $dto->type,
        );

        if (! $dto->isActive) {
            $waiverRule->deactivate();
        }

        return $this->repository->save($waiverRule);
    }
}
