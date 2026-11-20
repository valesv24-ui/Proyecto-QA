<?php

declare(strict_types=1);

namespace Src\StudentRequests\StudentRequest\Infrastructure\Notifications;

use App\Models\Attachment as AttachmentModel;
use App\Models\Course as CourseModel;
use App\Models\Student as StudentModel;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Src\StudentRequests\StudentRequest\Application\Contracts\StudentRequestNotifierInterface;
use Src\StudentRequests\StudentRequest\Domain\Entities\StudentRequest;
use Src\StudentRequests\StudentRequest\Infrastructure\Documents\PdfTransferResolutionDocument;
use Src\StudentRequests\StudentRequest\Infrastructure\Mail\StudentRequestStatusChangedMail;
use Throwable;

/** Adaptador de correo del puerto StudentRequestNotifierInterface (ES-03). */
final class MailStudentRequestNotifier implements StudentRequestNotifierInterface
{
    public function notifySubmitted(StudentRequest $studentRequest): void
    {
        $this->send($studentRequest, submitted: true, statement: null);
    }

    public function notifyResolved(StudentRequest $studentRequest, ?string $statement = null): void
    {
        $this->send($studentRequest, submitted: false, statement: $statement);
    }

    private function send(StudentRequest $studentRequest, bool $submitted, ?string $statement): void
    {
        $student = StudentModel::query()->with('user')->find($studentRequest->studentId());

        // Sin cuenta de usuario no hay a dónde escribir.
        if ($student === null || $student->user === null) {
            return;
        }

        $course = CourseModel::query()->find($studentRequest->courseId());
        $courseLabel = $course ? "{$course->code} — {$course->name}" : "#{$studentRequest->courseId()}";

        try {
            Mail::to($student->user->email)->send(new StudentRequestStatusChangedMail(
                requestId: (int) $studentRequest->id(),
                studentName: trim("{$student->first_name} {$student->last_name}"),
                type: $studentRequest->type(),
                courseLabel: $courseLabel,
                submitted: $submitted,
                // Al estudiante se le muestra el estado PÚBLICO.
                publicStatus: $studentRequest->publicStatus(),
                estimatedResolutionDate: $studentRequest->estimatedResolutionDate()?->format('Y-m-d'),
                statement: $statement,
                justification: $studentRequest->justification(),
                // La copia de la resolución RSREC-001, cuando existe.
                resolutionPath: $submitted ? null : $this->resolutionPathFor($studentRequest),
            ));
        } catch (Throwable $exception) {
            Log::warning('No se pudo enviar la notificación de la solicitud.', [
                'student_request_id' => $studentRequest->id(),
                'submitted' => $submitted,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    /** Ruta en disco del RSREC-001 archivado de esta solicitud. */
    private function resolutionPathFor(StudentRequest $studentRequest): ?string
    {
        return AttachmentModel::query()
            ->where('attachable_type', StudentRequest::class)
            ->where('attachable_id', $studentRequest->id())
            ->where('document_type', PdfTransferResolutionDocument::DOCUMENT_TYPE)
            ->orderByDesc('id')
            ->value('path');
    }
}
