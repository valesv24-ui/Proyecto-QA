<?php

declare(strict_types=1);

namespace Src\StudentRequests\TransferCreditPrecedent\Application\UseCases;

use Src\StudentRequests\TransferCreditPrecedent\Domain\Contracts\TransferCreditPrecedentRepositoryInterface;
use Src\StudentRequests\TransferCreditPrecedent\Domain\Exceptions\TransferCreditPrecedentNotFoundException;

final class DeleteTransferCreditPrecedentUseCase
{
    public function __construct(
        private readonly TransferCreditPrecedentRepositoryInterface $repository,
    ) {}

    public function handle(int $id): void
    {
        $this->repository->find($id) ?? throw TransferCreditPrecedentNotFoundException::withId($id);

        $this->repository->delete($id);
    }
}
