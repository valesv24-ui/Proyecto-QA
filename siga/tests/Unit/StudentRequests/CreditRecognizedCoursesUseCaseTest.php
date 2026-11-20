<?php

declare(strict_types=1);

namespace Tests\Unit\StudentRequests;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Src\AcademicCatalog\AcademicRecord\Domain\Contracts\AcademicRecordRepositoryInterface;
use Src\AcademicCatalog\AcademicRecord\Domain\Entities\AcademicRecord;
use Src\AcademicCatalog\AcademicTerm\Domain\Contracts\AcademicTermRepositoryInterface;
use Src\AcademicCatalog\AcademicTerm\Domain\Entities\AcademicTerm;
use Src\StudentRequests\StudentRequest\Application\UseCases\CreditRecognizedCoursesUseCase;
use Src\StudentRequests\StudentRequest\Domain\Contracts\RequestedCourseRepositoryInterface;
use Src\StudentRequests\StudentRequest\Domain\Entities\RequestedCourse;

/** Una convalidación aprobada tiene que cambiar el expediente. */
final class CreditRecognizedCoursesUseCaseTest extends TestCase
{
    private const STUDENT_ID = 7;

    private const TERM_ID = 3;

    public function test_it_credits_only_the_courses_the_committee_recognized(): void
    {
        $records = $this->recordsSpy();

        $useCase = $this->useCase(
            courses: [
                $this->course(id: 1, courseId: 100, outcome: RequestedCourse::OUTCOME_RECOGNIZED),
                $this->course(id: 2, courseId: 200, outcome: RequestedCourse::OUTCOME_REJECTED),
                $this->course(id: 3, courseId: 300, outcome: RequestedCourse::OUTCOME_RECOGNIZED),
            ],
            records: $records,
        );

        $credited = $useCase->handle(studentRequestId: 55, studentId: self::STUDENT_ID);

        self::assertSame(2, $credited);
        self::assertSame([100, 300], array_map(
            fn (AcademicRecord $r): int => $r->courseId(),
            $records->saved,
        ));
    }

    /** El caso que motiva toda la prueba. */
    public function test_a_partial_resolution_does_not_credit_the_rejected_courses(): void
    {
        $records = $this->recordsSpy();

        $useCase = $this->useCase(
            courses: [
                $this->course(id: 1, courseId: 100, outcome: RequestedCourse::OUTCOME_RECOGNIZED),
                $this->course(id: 2, courseId: 200, outcome: RequestedCourse::OUTCOME_REJECTED),
                $this->course(id: 3, courseId: 300, outcome: RequestedCourse::OUTCOME_REJECTED),
            ],
            records: $records,
        );

        $useCase->handle(studentRequestId: 55, studentId: self::STUDENT_ID);

        self::assertCount(1, $records->saved);
        self::assertSame(100, $records->saved[0]->courseId());
    }

    public function test_a_pending_course_is_not_credited(): void
    {
        // No debería llegar acá.
        $records = $this->recordsSpy();

        $useCase = $this->useCase(
            courses: [$this->course(id: 1, courseId: 100, outcome: RequestedCourse::OUTCOME_PENDING)],
            records: $records,
        );

        self::assertSame(0, $useCase->handle(studentRequestId: 55, studentId: self::STUDENT_ID));
        self::assertSame([], $records->saved);
    }

    public function test_it_records_the_transfer_status_and_the_origin_grade(): void
    {
        $records = $this->recordsSpy();

        $useCase = $this->useCase(
            courses: [$this->course(id: 1, courseId: 100, outcome: RequestedCourse::OUTCOME_RECOGNIZED, grade: 8.5)],
            records: $records,
        );

        $useCase->handle(studentRequestId: 55, studentId: self::STUDENT_ID);

        $saved = $records->saved[0];

        // "Acreditado por convalidación" y NO "Aprobado".
        self::assertSame('Acreditado por convalidación', $saved->status());
        self::assertSame(8.5, $saved->grade());
        self::assertSame(self::STUDENT_ID, $saved->studentId());
        self::assertSame(self::TERM_ID, $saved->academicTermId());
    }

    public function test_a_course_already_earned_is_not_credited_twice(): void
    {
        $records = $this->recordsSpy(existing: [
            AcademicRecord::create(self::STUDENT_ID, 100, self::TERM_ID, 'Aprobado', 92.0),
        ]);

        $useCase = $this->useCase(
            courses: [
                $this->course(id: 1, courseId: 100, outcome: RequestedCourse::OUTCOME_RECOGNIZED),
                $this->course(id: 2, courseId: 200, outcome: RequestedCourse::OUTCOME_RECOGNIZED),
            ],
            records: $records,
        );

        self::assertSame(1, $useCase->handle(studentRequestId: 55, studentId: self::STUDENT_ID));
        self::assertSame([200], array_map(
            fn (AcademicRecord $r): int => $r->courseId(),
            $records->saved,
        ));
    }

    public function test_a_failed_course_does_not_block_the_credit(): void
    {
        // Haber perdido un curso no impide convalidar después uno equivalente.
        $records = $this->recordsSpy(existing: [
            AcademicRecord::create(self::STUDENT_ID, 100, self::TERM_ID, 'Reprobado', 45.0),
        ]);

        $useCase = $this->useCase(
            courses: [$this->course(id: 1, courseId: 100, outcome: RequestedCourse::OUTCOME_RECOGNIZED)],
            records: $records,
        );

        self::assertSame(1, $useCase->handle(studentRequestId: 55, studentId: self::STUDENT_ID));
    }

    public function test_publishing_during_a_recess_leaves_the_term_empty(): void
    {
        // Null y no "el cuatrimestre más cercano".
        $records = $this->recordsSpy();

        $useCase = $this->useCase(
            courses: [$this->course(id: 1, courseId: 100, outcome: RequestedCourse::OUTCOME_RECOGNIZED)],
            records: $records,
            // withTerm y no `term: null`.
            withTerm: false,
        );

        $useCase->handle(studentRequestId: 55, studentId: self::STUDENT_ID);

        self::assertNull($records->saved[0]->academicTermId());
    }

    /**
     * @param  array<int, RequestedCourse>  $courses
     */
    private function useCase(
        array $courses,
        AcademicRecordRepositoryInterface $records,
        ?AcademicTerm $term = null,
        bool $withTerm = true,
    ): CreditRecognizedCoursesUseCase {
        $resolved = $withTerm && $term === null
            ? AcademicTerm::reconstitute(
                self::TERM_ID, 2026, 2,
                new DateTimeImmutable('2026-05-01'),
                new DateTimeImmutable('2026-08-31'),
            )
            : $term;

        return new CreditRecognizedCoursesUseCase(
            $this->requestedCoursesStub($courses),
            $records,
            $this->termsStub($resolved),
        );
    }

    private function course(int $id, int $courseId, string $outcome, ?float $grade = null): RequestedCourse
    {
        return RequestedCourse::reconstitute(
            id: $id,
            courseId: $courseId,
            externalInstitution: 'Universidad de Costa Rica',
            externalCourseName: 'Cálculo I',
            externalCourseCode: 'MA-1001',
            externalCredits: 4,
            externalGrade: $grade,
            outcome: $outcome,
            denialReason: $outcome === RequestedCourse::OUTCOME_REJECTED ? 'Contenidos no equivalentes.' : null,
        );
    }

    /**
     * @param  array<int, AcademicRecord>  $existing
     */
    private function recordsSpy(array $existing = []): AcademicRecordRepositoryInterface
    {
        return new class($existing) implements AcademicRecordRepositoryInterface
        {
            /** @var array<int, AcademicRecord> */
            public array $saved = [];

            /**
             * @param  array<int, AcademicRecord>  $existing
             */
            public function __construct(private array $existing) {}

            public function find(int $id): ?AcademicRecord
            {
                return null;
            }

            public function forStudent(int $studentId): array
            {
                return $this->existing;
            }

            public function findByStudentAndCourse(int $studentId, int $courseId): ?AcademicRecord
            {
                return null;
            }

            public function save(AcademicRecord $academicRecord): AcademicRecord
            {
                $this->saved[] = $academicRecord;

                return $academicRecord;
            }

            public function delete(int $id): void {}
        };
    }

    /**
     * @param  array<int, RequestedCourse>  $courses
     */
    private function requestedCoursesStub(array $courses): RequestedCourseRepositoryInterface
    {
        return new class($courses) implements RequestedCourseRepositoryInterface
        {
            /**
             * @param  array<int, RequestedCourse>  $courses
             */
            public function __construct(private array $courses) {}

            public function forRequest(int $studentRequestId): array
            {
                return $this->courses;
            }

            public function addAll(int $studentRequestId, array $courses): void {}

            public function save(int $studentRequestId, RequestedCourse $course): RequestedCourse
            {
                return $course;
            }

            public function find(int $id): ?RequestedCourse
            {
                return null;
            }

            public function recordResolutionDetails(
                int $studentRequestId,
                string $resolutionNumber,
                string $sessionNumber,
                string $minutesNumber,
                DateTimeImmutable $sessionDate,
            ): void {}

            public function resolutionDetails(int $studentRequestId): array
            {
                return [
                    'resolutionNumber' => null,
                    'sessionNumber' => null,
                    'minutesNumber' => null,
                    'sessionDate' => null,
                ];
            }
        };
    }

    private function termsStub(?AcademicTerm $term): AcademicTermRepositoryInterface
    {
        return new class($term) implements AcademicTermRepositoryInterface
        {
            public function __construct(private ?AcademicTerm $term) {}

            public function find(int $id): ?AcademicTerm
            {
                return $this->term;
            }

            public function findForDate(DateTimeImmutable $date): ?AcademicTerm
            {
                return $this->term;
            }

            public function all(): array
            {
                return $this->term === null ? [] : [$this->term];
            }

            public function save(AcademicTerm $academicTerm): AcademicTerm
            {
                return $academicTerm;
            }

            public function delete(int $id): void {}
        };
    }
}
