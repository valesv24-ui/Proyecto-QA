<?php

declare(strict_types=1);

namespace Tests\Unit\StudentRequests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Src\StudentRequests\StudentRequest\Domain\Entities\RequestedCourse;
use Src\StudentRequests\StudentRequest\Domain\Entities\StudentRequest;
use Src\StudentRequests\StudentRequest\Domain\Services\TransferResolutionTally;

/** La resolución de una convalidación, curso por curso. */
final class TransferResolutionTest extends TestCase
{
    public function test_a_rejected_course_requires_a_reason(): void
    {
        // El documento tiene un bloque dedicado a las razones.
        $course = $this->course();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Un curso no reconocido exige indicar la razón.');

        $course->resolve(RequestedCourse::OUTCOME_REJECTED);
    }

    public function test_recognizing_a_course_clears_any_reason_left_over(): void
    {
        $course = $this->course();

        $course->resolve(RequestedCourse::OUTCOME_REJECTED, 'Contenidos insuficientes.');
        self::assertSame('Contenidos insuficientes.', $course->denialReason());

        // Cambiar de opinión no puede dejar la razón vieja pegada.
        $course->resolve(RequestedCourse::OUTCOME_RECOGNIZED);

        self::assertTrue($course->isRecognized());
        self::assertNull($course->denialReason());
    }

    public function test_a_request_stays_open_while_any_course_is_undecided(): void
    {
        $tally = new TransferResolutionTally;

        $decided = $this->course();
        $decided->resolve(RequestedCourse::OUTCOME_RECOGNIZED);

        $courses = [$decided, $this->course()];

        self::assertFalse($tally->isFullyResolved($courses));
        // Null y no un estado.
        self::assertNull($tally->statusFor($courses));
    }

    public function test_all_recognized_approves_and_none_recognized_denies(): void
    {
        $tally = new TransferResolutionTally;

        $a = $this->course();
        $b = $this->course();
        $a->resolve(RequestedCourse::OUTCOME_RECOGNIZED);
        $b->resolve(RequestedCourse::OUTCOME_RECOGNIZED);
        self::assertSame(StudentRequest::STATUS_APPROVED, $tally->statusFor([$a, $b]));

        $c = $this->course();
        $d = $this->course();
        $c->resolve(RequestedCourse::OUTCOME_REJECTED, 'No corresponde.');
        $d->resolve(RequestedCourse::OUTCOME_REJECTED, 'No corresponde.');
        self::assertSame(StudentRequest::STATUS_DENIED, $tally->statusFor([$c, $d]));
    }

    public function test_a_partial_resolution_is_flagged_as_such(): void
    {
        $tally = new TransferResolutionTally;

        $yes = $this->course();
        $no = $this->course();
        $yes->resolve(RequestedCourse::OUTCOME_RECOGNIZED);
        $no->resolve(RequestedCourse::OUTCOME_REJECTED, 'Créditos insuficientes.');

        $courses = [$yes, $no];

        // Se etiqueta Aprobada por falta de un estado intermedio.
        self::assertSame(StudentRequest::STATUS_APPROVED, $tally->statusFor($courses));
        self::assertTrue($tally->isPartial($courses));
        self::assertSame(1, $tally->recognizedCount($courses));
    }

    public function test_a_request_with_no_courses_is_never_resolvable(): void
    {
        // Una convalidación sin cursos no pide nada.
        $tally = new TransferResolutionTally;

        self::assertFalse($tally->isFullyResolved([]));
        self::assertNull($tally->statusFor([]));
    }

    public function test_external_credits_outside_a_plausible_range_are_rejected(): void
    {
        // No es una regla académica sino un filtro de transcripción.
        $course = $this->course();

        $this->expectException(InvalidArgumentException::class);

        $course->completeExternalData('MA-101', 300);
    }

    public function test_a_course_needs_an_institution_and_a_name(): void
    {
        $this->expectException(InvalidArgumentException::class);

        RequestedCourse::requested(courseId: 1, externalInstitution: '   ', externalCourseName: 'Programación I');
    }

    private function course(): RequestedCourse
    {
        return RequestedCourse::requested(
            courseId: 10,
            externalInstitution: 'Universidad de Costa Rica',
            externalCourseName: 'Programación I',
        );
    }
}
