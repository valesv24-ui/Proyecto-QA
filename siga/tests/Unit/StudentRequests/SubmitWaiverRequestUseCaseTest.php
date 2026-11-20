<?php

declare(strict_types=1);

namespace Tests\Unit\StudentRequests;

use DateTimeImmutable;
use InvalidArgumentException;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Src\Shared\Clock\Domain\Contracts\ClockInterface;
use Src\Shared\Holiday\Domain\Contracts\HolidayProviderInterface;
use Src\StudentRequests\StudentRequest\Application\Contracts\StudentRequestNotifierInterface;
use Src\StudentRequests\StudentRequest\Application\DTOs\SubmitWaiverRequestDTO;
use Src\StudentRequests\StudentRequest\Application\Support\ResolutionStatement;
use Src\StudentRequests\StudentRequest\Application\UseCases\EnsureEstimatedResolutionDateUseCase;
use Src\StudentRequests\StudentRequest\Application\UseCases\SubmitWaiverRequestUseCase;
use Src\StudentRequests\StudentRequest\Domain\Contracts\StudentRequestRepositoryInterface;
use Src\StudentRequests\StudentRequest\Domain\Entities\StudentRequest;
use Src\StudentRequests\StudentRequest\Domain\Services\BusinessDayCalculator;
use Src\StudentRequests\StudentRequest\Domain\Services\WaiverEvaluationEngine;
use Src\StudentRequests\StudentRequestStatusHistory\Application\UseCases\RecordStudentRequestStatusHistoryUseCase;
use Src\StudentRequests\StudentRequestStatusHistory\Domain\Contracts\StudentRequestStatusHistoryRepositoryInterface;
use Src\StudentRequests\WaiverRule\Domain\Contracts\WaiverRuleRepositoryInterface;
use Src\StudentRequests\WaiverRule\Domain\Entities\WaiverRule;
use Tests\TestCase;

/** ES-01 con la boleta SLR-002. */
final class SubmitWaiverRequestUseCaseTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private StudentRequestRepositoryInterface $studentRequestRepository;

    private WaiverRuleRepositoryInterface $waiverRuleRepository;

    private StudentRequestNotifierInterface $notifier;

    private SubmitWaiverRequestUseCase $useCase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->studentRequestRepository = Mockery::mock(StudentRequestRepositoryInterface::class);
        $this->waiverRuleRepository = Mockery::mock(WaiverRuleRepositoryInterface::class);
        $this->notifier = Mockery::mock(StudentRequestNotifierInterface::class);

        $historyRepository = Mockery::mock(StudentRequestStatusHistoryRepositoryInterface::class);
        $historyRepository->shouldReceive('save')->andReturnUsing(fn ($entry) => $entry);

        $holidays = Mockery::mock(HolidayProviderInterface::class);
        $holidays->shouldReceive('isHoliday')->andReturn(false);

        $clock = Mockery::mock(ClockInterface::class);
        $clock->shouldReceive('now')->andReturn(new DateTimeImmutable('2026-08-10 09:00:00'));

        $this->useCase = new SubmitWaiverRequestUseCase(
            $this->studentRequestRepository,
            $this->waiverRuleRepository,
            new WaiverEvaluationEngine,
            new RecordStudentRequestStatusHistoryUseCase($historyRepository),
            new ResolutionStatement,
            $this->notifier,
            new EnsureEstimatedResolutionDateUseCase(
                $this->studentRequestRepository,
                new BusinessDayCalculator($holidays),
                $clock,
            ),
            $clock,
        );
    }

    public function test_a_duplicate_waiver_short_circuits_without_creating_a_request(): void
    {
        $existing = $this->waiver();

        $this->studentRequestRepository
            ->shouldReceive('findApprovedWaiverDuplicate')
            ->once()
            ->with(1, 10, 5)
            ->andReturn($existing);

        $this->studentRequestRepository->shouldNotReceive('save');
        $this->waiverRuleRepository->shouldNotReceive('activeForCourseOrdered');
        // Un duplicado no crea nada.
        $this->notifier->shouldNotReceive('notifySubmitted');
        $this->notifier->shouldNotReceive('notifyResolved');

        $result = $this->useCase->handle($this->dto());

        self::assertTrue($result->isDuplicate);
        self::assertNull($result->studentRequest);
    }

    public function test_a_course_without_rules_reaches_the_repository_as_manual_review(): void
    {
        $this->studentRequestRepository
            ->shouldReceive('findApprovedWaiverDuplicate')
            ->once()
            ->andReturn(null);

        $this->waiverRuleRepository
            ->shouldReceive('activeForCourseOrdered')
            ->once()
            ->with(10)
            ->andReturn([]);

        // Lo que importa.
        $this->studentRequestRepository
            ->shouldReceive('save')
            ->once()
            ->with(Mockery::on(function (StudentRequest $request): bool {
                return $request->justification() === WaiverRule::TYPE_ONLY_PENDING_COURSE
                    && $request->termsAcceptedAt() !== null
                    && $request->engineResult() === StudentRequest::RESULT_MANUAL_REVIEW;
            }))
            ->andReturnUsing(fn (StudentRequest $request) => $request);

        // Registro lo dijo así.
        $this->notifier->shouldReceive('notifySubmitted')->once();
        $this->notifier->shouldNotReceive('notifyResolved');

        $result = $this->useCase->handle($this->dto());

        self::assertFalse($result->isDuplicate);
        self::assertSame(StudentRequest::RESULT_MANUAL_REVIEW, $result->studentRequest?->engineResult());
    }

    public function test_a_justification_the_course_does_not_admit_is_denied_and_notified(): void
    {
        $this->studentRequestRepository
            ->shouldReceive('findApprovedWaiverDuplicate')
            ->once()
            ->andReturn(null);

        $this->waiverRuleRepository
            ->shouldReceive('activeForCourseOrdered')
            ->once()
            ->andReturn([
                WaiverRule::reconstitute(
                    id: 9,
                    courseId: 10,
                    evaluationOrder: 1,
                    type: WaiverRule::TYPE_PRIOR_KNOWLEDGE,
                    isActive: true,
                ),
            ]);

        $this->studentRequestRepository
            ->shouldReceive('save')
            ->once()
            ->andReturnUsing(fn (StudentRequest $request) => $request);

        // El motor resolvió en el acto.
        $this->notifier->shouldReceive('notifySubmitted')->once();
        $this->notifier->shouldReceive('notifyResolved')->once();

        $result = $this->useCase->handle($this->dto());

        self::assertSame(StudentRequest::RESULT_DENIED, $result->studentRequest?->engineResult());
    }

    public function test_it_rejects_a_justification_that_is_not_on_the_form(): void
    {
        // El sexto tipo existe solo del lado de Docencia.
        $this->expectException(InvalidArgumentException::class);

        $this->useCase->handle(new SubmitWaiverRequestDTO(
            studentId: 1,
            courseId: 10,
            prerequisiteCourseId: 5,
            justification: WaiverRule::TYPE_SPECIAL_CASE,
            acceptsTerms: true,
        ));
    }

    public function test_it_rejects_a_request_without_accepting_the_important_notes(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->useCase->handle(new SubmitWaiverRequestDTO(
            studentId: 1,
            courseId: 10,
            prerequisiteCourseId: 5,
            justification: WaiverRule::TYPE_ONLY_PENDING_COURSE,
            acceptsTerms: false,
        ));
    }

    private function dto(): SubmitWaiverRequestDTO
    {
        return new SubmitWaiverRequestDTO(
            studentId: 1,
            courseId: 10,
            prerequisiteCourseId: 5,
            justification: WaiverRule::TYPE_ONLY_PENDING_COURSE,
            acceptsTerms: true,
        );
    }

    private function waiver(): StudentRequest
    {
        return StudentRequest::forWaiver(
            studentId: 1,
            courseId: 10,
            prerequisiteCourseId: 5,
            justification: WaiverRule::TYPE_ONLY_PENDING_COURSE,
            termsAcceptedAt: new DateTimeImmutable('2026-08-10 09:00:00'),
        );
    }
}
