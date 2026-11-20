<?php

declare(strict_types=1);

namespace Src\StudentRequests\StudentRequest\Presentation\Livewire;

use DateTimeImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Src\AcademicCatalog\Course\Application\UseCases\ListCoursesUseCase;
use Src\StudentRequests\StudentRequest\Application\UseCases\ListStudentRequestsForStudentUseCase;
use Src\StudentRequests\StudentRequest\Application\UseCases\SummarizeStudentRequestsUseCase;
use Src\StudentRequests\StudentRequest\Domain\Entities\StudentRequest;
use Src\StudentRequests\StudentRequest\Domain\Services\InstitutionalRequestCalendar;
use Src\StudentRequests\StudentRequest\Presentation\Support\StudentRequestStatusBadge;

/** Cards de solicitudes en el panel principal. */
class StudentRequestSummaryComponent extends Component
{
    /** Cuántas solicitudes vencidas se listan bajo las cards. */
    private const PAST_DUE_LIMIT = 5;

    public function canReviewInbox(): bool
    {
        return Auth::user() !== null && Auth::user()->can('viewAny', StudentRequest::class);
    }

    public function isStudent(): bool
    {
        return Auth::user()?->student !== null;
    }

    public function render(
        SummarizeStudentRequestsUseCase $summarize,
        ListStudentRequestsForStudentUseCase $listForStudent,
        ListCoursesUseCase $coursesUseCase,
        InstitutionalRequestCalendar $calendar,
    ): View {
        $reviewsInbox = $this->canReviewInbox();
        // Solo cuando la cuenta pertenece a UN solo escritorio (Docencia o
        // Registro, no ambos ni ninguno): mismo criterio que usa la bandeja.
        $deskStatuses = $reviewsInbox ? $this->currentDeskStatuses() : null;

        return view('studentrequests.studentrequest.livewire.student-request-summary-component', [
            'reviewsInbox' => $reviewsInbox,
            'showsOwnRequests' => ! $reviewsInbox && $this->isStudent(),
            'summary' => $reviewsInbox ? $summarize->handle($deskStatuses) : null,
            'pastDue' => $reviewsInbox ? $this->pastDueRows($summarize, $coursesUseCase, $deskStatuses) : [],
            'mine' => $reviewsInbox ? [] : $this->ownSummary($listForStudent, $calendar),
            'waiverType' => StudentRequest::TYPE_WAIVER,
            'transferType' => StudentRequest::TYPE_TRANSFER,
        ]);
    }

    /**
     * A qué escritorio le toca ESTA cuenta. Mismo criterio que
     * StudentRequestComponent::currentDeskStatuses() para la bandeja: no se
     * comparte porque son componentes de Livewire independientes y la lógica
     * es de una sola línea por caso.
     *
     * @return array<int, string>|null
     */
    private function currentDeskStatuses(): ?array
    {
        $user = Auth::user();
        $canVerify = $user?->hasPermissionTo('student_requests.verify') ?? false;
        $canDecide = $user?->hasPermissionTo('student_requests.edit') ?? false;

        if ($canVerify && ! $canDecide) {
            return StudentRequest::REGISTRAR_DESK_STATUSES;
        }

        if ($canDecide && ! $canVerify) {
            return StudentRequest::TEACHING_DESK_STATUSES;
        }

        return null;
    }

    /**
     * Filas de la lista "vence hoy o ya venció".
     *
     * @param  array<int, string>|null  $deskStatuses
     * @return array<int, array<string, mixed>>
     */
    private function pastDueRows(SummarizeStudentRequestsUseCase $summarize, ListCoursesUseCase $coursesUseCase, ?array $deskStatuses): array
    {
        $requests = $summarize->pastDue(self::PAST_DUE_LIMIT, $deskStatuses);

        if ($requests === []) {
            return [];
        }

        $courseIds = [];
        foreach ($requests as $request) {
            $courseIds[] = $request->courseId();
        }
        $courses = $coursesUseCase->findManyByIds($courseIds);

        $badge = app(StudentRequestStatusBadge::class);
        $now = new DateTimeImmutable;

        return array_map(static function (StudentRequest $request) use ($courses, $badge, $now): array {
            $course = $courses[$request->courseId()] ?? null;

            return [
                'id' => $request->id(),
                'type' => $request->type(),
                'status' => $request->status(),
                'courseLabel' => $course ? "{$course->code()} — {$course->name()}" : "#{$request->courseId()}",
                'estimatedResolutionDate' => $request->estimatedResolutionDate()?->format('Y-m-d'),
                'deadlineLabel' => $badge->deadlineLabel($request->status(), $request->estimatedResolutionDate(), $now),
                'statusVariant' => $badge->variantFor(
                    $request->status(),
                    $request->engineResult(),
                    $request->estimatedResolutionDate(),
                    $now,
                ),
            ];
        }, $requests);
    }

    /**
     * Resumen del propio expediente del estudiante.
     *
     * @return array{open: int, pending: int, inReview: int, approved: int, denied: int, nextPublicationDate: ?string}
     */
    private function ownSummary(ListStudentRequestsForStudentUseCase $listForStudent, InstitutionalRequestCalendar $calendar): array
    {
        $student = Auth::user()?->student;

        if ($student === null) {
            return ['open' => 0, 'pending' => 0, 'inReview' => 0, 'approved' => 0, 'denied' => 0, 'nextPublicationDate' => null];
        }

        $counts = ['open' => 0, 'pending' => 0, 'inReview' => 0, 'approved' => 0, 'denied' => 0];
        // Solo importa si hay una convalidación abierta.
        $hasOpenTransfer = false;

        foreach ($listForStudent->handle($student->id) as $request) {
            // Se reparte por publicStatus() y no por status().
            $counts[match ($request->publicStatus()) {
                StudentRequest::PUBLIC_RECEIVED => 'pending',
                StudentRequest::PUBLIC_IN_PROGRESS => 'inReview',
                StudentRequest::STATUS_APPROVED => 'approved',
                default => 'denied',
            }]++;

            if ($request->isResolved()) {
                continue;
            }

            $counts['open']++;

            if ($request->type() === StudentRequest::TYPE_TRANSFER) {
                $hasOpenTransfer = true;
            }
        }

        // Fecha REAL del Calendario Institucional, no una estimación del motor.
        $nextPublicationDate = $hasOpenTransfer
            ? $calendar->nextTransferPublicationDate(new DateTimeImmutable)?->format('Y-m-d')
            : null;

        return $counts + ['nextPublicationDate' => $nextPublicationDate];
    }
}
