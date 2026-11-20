<?php

declare(strict_types=1);

namespace Src\StudentRequests\StudentRequest\Application\UseCases;

use Src\Shared\Clock\Domain\Contracts\ClockInterface;
use Src\StudentRequests\StudentRequest\Domain\Contracts\StudentRequestRepositoryInterface;
use Src\StudentRequests\StudentRequest\Domain\Entities\StudentRequest;
use Src\StudentRequests\StudentRequest\Domain\Exceptions\StudentRequestNotFoundException;
use Src\StudentRequests\StudentRequest\Domain\Services\BusinessDayCalculator;

/** ES-03: toda solicitud tiene fecha estimada de resolución. */
final class EnsureEstimatedResolutionDateUseCase
{
    /** ES-03, criterio de aceptación literal. */
    public const BUSINESS_DAYS_TO_ASSIGN = 5;

    public function __construct(
        private readonly StudentRequestRepositoryInterface $repository,
        private readonly BusinessDayCalculator $businessDays,
        private readonly ClockInterface $clock,
    ) {}

    /** Asigna el plazo a una solicitud que se está creando. */
    public function assignOnReception(StudentRequest $studentRequest): void
    {
        if ($studentRequest->estimatedResolutionDate() !== null) {
            return;
        }

        $studentRequest->assignEstimatedResolutionDate(
            $this->businessDays->addBusinessDays($this->clock->now(), self::BUSINESS_DAYS_TO_ASSIGN),
        );
    }

    /** Relleno para solicitudes YA guardadas que no tienen fecha. */
    public function handle(int $id): StudentRequest
    {
        $studentRequest = $this->repository->find($id) ?? throw StudentRequestNotFoundException::withId($id);

        $createdAt = $studentRequest->createdAt();

        if ($studentRequest->estimatedResolutionDate() !== null || $createdAt === null) {
            return $studentRequest;
        }

        // Desde su fecha de recepción real, no desde hoy.
        $studentRequest->assignEstimatedResolutionDate(
            $this->businessDays->addBusinessDays($createdAt, self::BUSINESS_DAYS_TO_ASSIGN),
        );

        return $this->repository->save($studentRequest);
    }
}
