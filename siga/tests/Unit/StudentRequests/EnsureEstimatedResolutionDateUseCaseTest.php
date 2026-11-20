<?php

declare(strict_types=1);

namespace Tests\Unit\StudentRequests;

use DateTimeImmutable;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Src\Shared\Clock\Domain\Contracts\ClockInterface;
use Src\Shared\Holiday\Domain\Contracts\HolidayProviderInterface;
use Src\StudentRequests\StudentRequest\Application\UseCases\EnsureEstimatedResolutionDateUseCase;
use Src\StudentRequests\StudentRequest\Domain\Contracts\StudentRequestRepositoryInterface;
use Src\StudentRequests\StudentRequest\Domain\Entities\StudentRequest;
use Src\StudentRequests\StudentRequest\Domain\Services\BusinessDayCalculator;
use Src\StudentRequests\WaiverRule\Domain\Entities\WaiverRule;

/** ES-03 / tarea 7.4. */
final class EnsureEstimatedResolutionDateUseCaseTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private StudentRequestRepositoryInterface $repository;

    private ClockInterface $clock;

    private EnsureEstimatedResolutionDateUseCase $useCase;

    protected function setUp(): void
    {
        $this->repository = Mockery::mock(StudentRequestRepositoryInterface::class);
        $this->clock = Mockery::mock(ClockInterface::class);

        $holidays = Mockery::mock(HolidayProviderInterface::class);
        $holidays->shouldReceive('isHoliday')->andReturn(false);

        $this->useCase = new EnsureEstimatedResolutionDateUseCase(
            $this->repository,
            new BusinessDayCalculator($holidays),
            $this->clock,
        );
    }

    /** El plazo se fija al recibir la solicitud. */
    public function test_it_assigns_the_deadline_on_reception_without_waiting(): void
    {
        // Lunes 10 de agosto de 2026, 09:00.
        $now = new DateTimeImmutable('2026-08-10 09:00:00');

        $studentRequest = StudentRequest::forWaiver(
            studentId: 1,
            courseId: 10,
            prerequisiteCourseId: 5,
            justification: WaiverRule::TYPE_ONLY_PENDING_COURSE,
            termsAcceptedAt: new DateTimeImmutable('2026-08-10 09:00:00'),
        );

        // Una solicitud recién creada todavía no tiene createdAt.
        self::assertNull($studentRequest->createdAt());

        $this->clock->shouldReceive('now')->once()->andReturn($now);
        // No guarda: quien la llama está a punto de hacer.
        $this->repository->shouldNotReceive('save');

        $this->useCase->assignOnReception($studentRequest);

        self::assertSame('2026-08-17', $studentRequest->estimatedResolutionDate()?->format('Y-m-d'));
    }

    public function test_assigning_on_reception_respects_a_date_already_set(): void
    {
        $studentRequest = StudentRequest::forWaiver(
            studentId: 1,
            courseId: 10,
            prerequisiteCourseId: 5,
            justification: WaiverRule::TYPE_ONLY_PENDING_COURSE,
            termsAcceptedAt: new DateTimeImmutable('2026-08-10 09:00:00'),
        );
        $studentRequest->assignEstimatedResolutionDate(new DateTimeImmutable('2026-09-01'));

        $this->clock->shouldNotReceive('now');

        $this->useCase->assignOnReception($studentRequest);

        self::assertSame('2026-09-01', $studentRequest->estimatedResolutionDate()?->format('Y-m-d'));
    }

    /** Relleno de una solicitud vieja sin fecha. */
    public function test_backfilling_computes_the_deadline_from_the_reception_date(): void
    {
        $createdAt = new DateTimeImmutable('2026-08-10 09:00:00');

        $studentRequest = StudentRequest::reconstitute(
            id: 2,
            studentId: 1,
            type: StudentRequest::TYPE_WAIVER,
            courseId: 10,
            prerequisiteCourseId: 5,
            sourceInstitution: null,
            externalCourse: null,
            transferCreditPrecedentId: null,
            engineResult: StudentRequest::RESULT_MANUAL_REVIEW,
            failedRuleId: null,
            status: StudentRequest::STATUS_PENDING,
            estimatedResolutionDate: null,
            reviewerId: null,
            createdAt: $createdAt,
        );

        $this->repository->shouldReceive('find')->once()->with(2)->andReturn($studentRequest);

        // No consulta el reloj: la fecha sale del expediente.
        $this->clock->shouldNotReceive('now');

        $this->repository
            ->shouldReceive('save')
            ->once()
            ->with(Mockery::on(function (StudentRequest $saved): bool {
                return $saved->estimatedResolutionDate()?->format('Y-m-d') === '2026-08-17';
            }))
            ->andReturnUsing(fn (StudentRequest $saved) => $saved);

        $result = $this->useCase->handle(2);

        self::assertSame('2026-08-17', $result->estimatedResolutionDate()?->format('Y-m-d'));
    }

    public function test_it_is_idempotent_when_a_date_was_already_assigned(): void
    {
        $createdAt = new DateTimeImmutable('2026-08-01 09:00:00');
        $alreadyAssigned = new DateTimeImmutable('2026-08-08 09:00:00');

        $studentRequest = StudentRequest::reconstitute(
            id: 3,
            studentId: 1,
            type: StudentRequest::TYPE_WAIVER,
            courseId: 10,
            prerequisiteCourseId: 5,
            sourceInstitution: null,
            externalCourse: null,
            transferCreditPrecedentId: null,
            engineResult: StudentRequest::RESULT_MANUAL_REVIEW,
            failedRuleId: null,
            status: StudentRequest::STATUS_PENDING,
            estimatedResolutionDate: $alreadyAssigned,
            reviewerId: null,
            createdAt: $createdAt,
        );

        $this->repository->shouldReceive('find')->once()->with(3)->andReturn($studentRequest);
        $this->repository->shouldNotReceive('save');

        // Ya tiene fecha asignada.
        $this->clock->shouldNotReceive('now');

        $result = $this->useCase->handle(3);

        self::assertSame($alreadyAssigned, $result->estimatedResolutionDate());
    }

    public function test_it_does_not_auto_assign_when_the_request_has_no_creation_date(): void
    {
        // Caso defensivo.
        $studentRequest = StudentRequest::reconstitute(
            id: 4,
            studentId: 1,
            type: StudentRequest::TYPE_WAIVER,
            courseId: 10,
            prerequisiteCourseId: 5,
            sourceInstitution: null,
            externalCourse: null,
            transferCreditPrecedentId: null,
            engineResult: StudentRequest::RESULT_MANUAL_REVIEW,
            failedRuleId: null,
            status: StudentRequest::STATUS_PENDING,
            estimatedResolutionDate: null,
            reviewerId: null,
            createdAt: null,
        );

        $this->repository->shouldReceive('find')->once()->with(4)->andReturn($studentRequest);
        $this->repository->shouldNotReceive('save');

        // Sin fecha de creación.
        $this->clock->shouldNotReceive('now');

        $result = $this->useCase->handle(4);

        self::assertNull($result->estimatedResolutionDate());
    }
}
