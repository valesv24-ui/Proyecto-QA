<?php

declare(strict_types=1);

namespace Src\StudentRequests\TransferCreditPrecedent\Application\UseCases;

use Src\StudentRequests\TransferCreditPrecedent\Domain\Contracts\TransferCreditPrecedentRepositoryInterface;
use Src\StudentRequests\TransferCreditPrecedent\Domain\Entities\TransferCreditPrecedent;
use Src\StudentRequests\TransferCreditPrecedent\Domain\Exceptions\TransferCreditPrecedentNotFoundException;

final class FindTransferCreditPrecedentUseCase
{
    public function __construct(
        private readonly TransferCreditPrecedentRepositoryInterface $repository,
    ) {}

    public function handle(int $id): TransferCreditPrecedent
    {
        return $this->repository->find($id) ?? throw TransferCreditPrecedentNotFoundException::withId($id);
    }
}
