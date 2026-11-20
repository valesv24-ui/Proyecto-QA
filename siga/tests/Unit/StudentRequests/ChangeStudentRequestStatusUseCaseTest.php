<?php

declare(strict_types=1);

namespace Tests\Unit\StudentRequests;

use DateTimeImmutable;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Src\Shared\Clock\Domain\Contracts\ClockInterface;
use Src\Shared\Holiday\Domain\Contracts\HolidayProviderInterface;
use Src\StudentRequests\StudentRequest\Application\Contracts\StudentRequestNotifierInterface;
use Src\StudentRequests\StudentRequest\Application\UseCases\ChangeStudentRequestStatusUseCase;
use Src\StudentRequests\StudentRequest\Application\UseCases\EnsureEstimatedResolutionDateUseCase;
use Src\StudentRequests\StudentRequest\Domain\Contracts\StudentRequestRepositoryInterface;
use Src\StudentRequests\StudentRequest\Domain\Entities\StudentRequest;
use Src\StudentRequests\StudentRequest\Domain\Services\BusinessDayCalculator;
use Src\StudentRequests\StudentRequestStatusHistory\Application\UseCases\RecordStudentRequestStatusHistoryUseCase;
use Src\StudentRequests\StudentRequestStatusHistory\Domain\Contracts\StudentRequestStatusHistoryRepositoryInterface;

/** ES-03, tal como lo describió Registro Universitario. */
final class ChangeStudentRequestStatusUseCaseTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private StudentRequestRepositoryInterface $repository;

    private StudentRequestNotifierInterface $notifier;

    private ChangeStudentRequestStatusUseCase $useCase;

    protected function setUp(): void
    {
        $this->repository = Mockery::mock(StudentRequestRepositoryInterface::class);
        $this->notifier = Mockery::mock(StudentRequestNotifierInterface::class);

        $holidays = Mockery::mock(HolidayProviderInterface::class);
        $holidays->shouldReceive('isHoliday')->andReturn(false);

        $clock = Mockery::mock(ClockInterface::class);
        $clock->shouldReceive('now')->andReturn(new DateTimeImmutable('2026-08-18 09:00:00'));

        $historyRepository = Mockery::mock(StudentRequestStatusHistoryRepositoryInterface::class);
        $historyRepository->shouldReceive('save')->andReturnUsing(fn ($entry) => $entry);

        $this->useCase = new ChangeStudentRequestStatusUseCase(
            $this->repository,
            new EnsureEstimatedResolutionDateUseCase(
                $this->repository,
                new BusinessDayCalculator($holidays),
                $clock,
            ),
            new RecordStudentRequestStatusHistoryUseCase($historyRepository),
            $this->notifier,
        );
    }

    public function test_publishing_a_resolution_notifies_the_student_with_the_reviewer_comment(): void
    {
        // Desde "Resuelta por Docencia" y no desde "En revisión".
        $decided = $this->requestWith(StudentRequest::STATUS_DECIDED);
        $approved = $this->requestWith(StudentRequest::STATUS_APPROVED);

        $this->repository->shouldReceive('find')->andReturn($decided);
        $this->repository->shouldReceive('save')->andReturn($approved);

        // El comentario que se guarda en el historial.
        $this->notifier
            ->shouldReceive('notifyResolved')
            ->once()
            ->with($approved, 'Cumple el requisito.');

        $this->useCase->handle(
            id: 1,
            newStatus: StudentRequest::STATUS_APPROVED,
            reviewerId: 7,
            comment: 'Cumple el requisito.',
        );
    }

    public function test_the_registrar_verification_does_not_email_the_student(): void
    {
        $pending = $this->requestWith(StudentRequest::STATUS_PENDING);
        $verified = $this->requestWith(StudentRequest::STATUS_VERIFIED);

        $this->repository->shouldReceive('find')->andReturn($pending);
        $this->repository->shouldReceive('save')->andReturn($verified);

        // Sin `shouldReceive`.
        $this->notifier->shouldNotReceive('notifyResolved');
        $this->notifier->shouldNotReceive('notifySubmitted');

        $this->useCase->handle(
            id: 1,
            newStatus: StudentRequest::STATUS_VERIFIED,
            reviewerId: 7,
        );
    }

    public function test_the_pass_to_docencia_does_not_email_the_student_either(): void
    {
        $verified = $this->requestWith(StudentRequest::STATUS_VERIFIED);
        $inReview = $this->requestWith(StudentRequest::STATUS_IN_REVIEW);

        $this->repository->shouldReceive('find')->andReturn($verified);
        $this->repository->shouldReceive('save')->andReturn($inReview);

        $this->notifier->shouldNotReceive('notifyResolved');

        $this->useCase->handle(
            id: 1,
            newStatus: StudentRequest::STATUS_IN_REVIEW,
            reviewerId: 7,
        );
    }

    public function test_the_decision_of_docencia_does_not_email_the_student(): void
    {
        // La decisión ya está tomada pero todavía no es pública.
        $inReview = $this->requestWith(StudentRequest::STATUS_IN_REVIEW);
        $decided = $this->requestWith(StudentRequest::STATUS_DECIDED);

        $this->repository->shouldReceive('find')->andReturn($inReview);
        $this->repository->shouldReceive('save')->andReturn($decided);

        $this->notifier->shouldNotReceive('notifyResolved');

        $this->useCase->handle(
            id: 1,
            newStatus: StudentRequest::STATUS_DECIDED,
            reviewerId: 7,
        );
    }

    public function test_a_denial_notifies_with_a_null_comment_when_the_reviewer_left_none(): void
    {
        $pending = $this->requestWith(StudentRequest::STATUS_PENDING);
        $denied = $this->requestWith(StudentRequest::STATUS_DENIED);

        $this->repository->shouldReceive('find')->andReturn($pending);
        $this->repository->shouldReceive('save')->andReturn($denied);

        $this->notifier
            ->shouldReceive('notifyResolved')
            ->once()
            ->with($denied, null);

        $this->useCase->handle(
            id: 1,
            newStatus: StudentRequest::STATUS_DENIED,
            reviewerId: 7,
        );
    }

    private function requestWith(string $status): StudentRequest
    {
        return StudentRequest::reconstitute(
            id: 1,
            studentId: 1,
            type: StudentRequest::TYPE_WAIVER,
            courseId: 10,
            prerequisiteCourseId: 5,
            sourceInstitution: null,
            externalCourse: null,
            transferCreditPrecedentId: null,
            engineResult: StudentRequest::RESULT_MANUAL_REVIEW,
            failedRuleId: null,
            status: $status,
            // Ya asignada: así EnsureEstimatedResolutionDateUseCase no entra a calcular nada.
            estimatedResolutionDate: new DateTimeImmutable('2026-08-27'),
            reviewerId: null,
            createdAt: new DateTimeImmutable('2026-08-14 09:00:00'),
        );
    }
}
