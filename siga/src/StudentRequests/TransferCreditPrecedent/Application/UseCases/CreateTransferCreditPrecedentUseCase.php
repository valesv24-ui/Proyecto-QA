<?php

declare(strict_types=1);

namespace Src\StudentRequests\TransferCreditPrecedent\Application\UseCases;

use Src\StudentRequests\TransferCreditPrecedent\Application\DTOs\TransferCreditPrecedentDTO;
use Src\StudentRequests\TransferCreditPrecedent\Domain\Contracts\TransferCreditPrecedentRepositoryInterface;
use Src\StudentRequests\TransferCreditPrecedent\Domain\Entities\TransferCreditPrecedent;

final class CreateTransferCreditPrecedentUseCase
{
    public function __construct(
        private readonly TransferCreditPrecedentRepositoryInterface $repository,
    ) {}

    public function handle(TransferCreditPrecedentDTO $dto): TransferCreditPrecedent
    {
        $precedent = TransferCreditPrecedent::create(
            institution: $dto->institution,
            externalCourse: $dto->externalCourse,
            courseId: $dto->courseId,
            outcome: $dto->outcome,
            resolutionNumber: $dto->resolutionNumber,
        );

        return $this->repository->save($precedent);
    }
}
