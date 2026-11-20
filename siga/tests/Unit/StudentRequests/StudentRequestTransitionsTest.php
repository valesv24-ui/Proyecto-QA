<?php

declare(strict_types=1);

namespace Tests\Unit\StudentRequests;

use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Src\StudentRequests\StudentRequest\Domain\Entities\StudentRequest;

/** El circuito real del levantamiento, según Registro Universitario. */
final class StudentRequestTransitionsTest extends TestCase
{
    public function test_docencia_cannot_approve_a_request_registro_has_not_verified(): void
    {
        $request = $this->waiverWith(StudentRequest::STATUS_PENDING);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No se puede pasar de «Pendiente de revisión» a «Aprobada».');

        $request->changeStatus(StudentRequest::STATUS_APPROVED, 7);
    }

    public function test_the_happy_path_of_the_paper_process_is_allowed_end_to_end(): void
    {
        $request = $this->waiverWith(StudentRequest::STATUS_PENDING);

        // Registro verifica.
        $request->changeStatus(StudentRequest::STATUS_VERIFIED, 3);
        self::assertSame(StudentRequest::STATUS_VERIFIED, $request->status());

        // Docencia abre y resuelve, pero NO publica.
        $request->changeStatus(StudentRequest::STATUS_IN_REVIEW, 7);
        $request->decideByTeaching(StudentRequest::STATUS_APPROVED, 7);

        self::assertSame(StudentRequest::STATUS_DECIDED, $request->status());
        self::assertSame(StudentRequest::STATUS_APPROVED, $request->teachingDecision());
        self::assertFalse($request->isResolved());
        self::assertTrue($request->awaitsPublication());

        // Registro publica.
        $request->publish(3);

        self::assertSame(StudentRequest::STATUS_APPROVED, $request->status());
        self::assertTrue($request->isResolved());
    }

    public function test_docencia_can_no_longer_publish_on_its_own(): void
    {
        // Antes esta transición existía y era el hueco.
        $request = $this->waiverWith(StudentRequest::STATUS_IN_REVIEW);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No se puede pasar de «En revisión» a «Aprobada».');

        $request->changeStatus(StudentRequest::STATUS_APPROVED, 7);
    }

    public function test_publishing_takes_the_decision_and_offers_no_way_to_change_it(): void
    {
        $request = $this->waiverWith(StudentRequest::STATUS_IN_REVIEW);
        $request->decideByTeaching(StudentRequest::STATUS_DENIED, 7);

        // publish() no recibe estado destino.
        $request->publish(3);

        self::assertSame(StudentRequest::STATUS_DENIED, $request->status());
    }

    public function test_there_is_nothing_to_publish_without_a_decision(): void
    {
        // Se llega a este estado solo por decideByTeaching().
        $request = $this->waiverWith(StudentRequest::STATUS_DECIDED);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No hay decisión de Docencia que publicar.');

        $request->publish(3);
    }

    public function test_a_decision_must_be_approved_or_denied(): void
    {
        $request = $this->waiverWith(StudentRequest::STATUS_IN_REVIEW);

        $this->expectException(InvalidArgumentException::class);

        $request->decideByTeaching(StudentRequest::STATUS_VERIFIED, 7);
    }

    public function test_a_request_can_be_denied_from_any_open_state(): void
    {
        foreach ([
            StudentRequest::STATUS_PENDING,
            StudentRequest::STATUS_VERIFIED,
            StudentRequest::STATUS_IN_REVIEW,
        ] as $status) {
            $request = $this->waiverWith($status);
            $request->changeStatus(StudentRequest::STATUS_DENIED, 7);

            self::assertSame(StudentRequest::STATUS_DENIED, $request->status(), "desde {$status}");
        }
    }

    public function test_a_published_resolution_cannot_be_reopened(): void
    {
        $request = $this->waiverWith(StudentRequest::STATUS_APPROVED);

        self::assertSame([], $request->allowedNextStatuses());

        $this->expectException(InvalidArgumentException::class);

        // Corregir un error exige una boleta nueva.
        $request->changeStatus(StudentRequest::STATUS_IN_REVIEW, 7);
    }

    public function test_the_administrative_override_skips_the_circuit_but_not_the_enum(): void
    {
        $request = $this->waiverWith(StudentRequest::STATUS_PENDING);

        // La cuenta de administración no ocupa ningún escritorio del trámite.
        $request->changeStatus(StudentRequest::STATUS_APPROVED, 1, bypassWorkflow: true);
        self::assertSame(StudentRequest::STATUS_APPROVED, $request->status());

        // Lo que el atajo NO concede es inventarse estados.
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Estado inválido');

        $request->changeStatus('Archivada', 1, bypassWorkflow: true);
    }

    public function test_the_student_sees_three_moments_not_the_five_internal_states(): void
    {
        $expected = [
            StudentRequest::STATUS_PENDING => StudentRequest::PUBLIC_RECEIVED,
            StudentRequest::STATUS_VERIFIED => StudentRequest::PUBLIC_IN_PROGRESS,
            StudentRequest::STATUS_IN_REVIEW => StudentRequest::PUBLIC_IN_PROGRESS,
            // Resuelta por Docencia también es "En trámite".
            StudentRequest::STATUS_DECIDED => StudentRequest::PUBLIC_IN_PROGRESS,
            StudentRequest::STATUS_APPROVED => StudentRequest::STATUS_APPROVED,
            StudentRequest::STATUS_DENIED => StudentRequest::STATUS_DENIED,
        ];

        foreach ($expected as $internal => $public) {
            self::assertSame($public, $this->waiverWith($internal)->publicStatus());
        }

        // El estudiante no debe poder deducir en qué escritorio está.
        self::assertSame(
            $this->waiverWith(StudentRequest::STATUS_VERIFIED)->publicStatus(),
            $this->waiverWith(StudentRequest::STATUS_IN_REVIEW)->publicStatus(),
        );
    }

    /** Bandeja y Historial tienen que ser complementarios y no solaparse. */
    public function test_every_status_belongs_to_the_inbox_or_to_the_history_but_not_both(): void
    {
        foreach (StudentRequest::STATUSES as $status) {
            $isOpen = in_array($status, StudentRequest::OPEN_STATUSES, true);
            $isResolved = in_array($status, StudentRequest::RESOLVED_STATUSES, true);

            self::assertTrue(
                $isOpen !== $isResolved,
                "«{$status}» tiene que estar en la bandeja o en el historial, y en uno solo.",
            );
        }
    }

    public function test_the_history_holds_exactly_what_the_entity_calls_resolved(): void
    {
        // isResolved() decide si se manda el correo de resolución.
        foreach (StudentRequest::STATUSES as $status) {
            self::assertSame(
                in_array($status, StudentRequest::RESOLVED_STATUSES, true),
                $this->waiverWith($status)->isResolved(),
                "«{$status}»: el historial y isResolved() no coinciden.",
            );
        }
    }

    private function waiverWith(string $status): StudentRequest
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
            estimatedResolutionDate: new DateTimeImmutable('2026-08-27'),
            reviewerId: null,
            createdAt: new DateTimeImmutable('2026-08-14 09:00:00'),
        );
    }
}
