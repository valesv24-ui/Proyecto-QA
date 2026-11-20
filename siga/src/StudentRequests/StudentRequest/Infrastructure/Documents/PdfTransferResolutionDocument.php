<?php

declare(strict_types=1);

namespace Src\StudentRequests\StudentRequest\Infrastructure\Documents;

use App\Models\Course as CourseModel;
use App\Models\StudentRequest as StudentRequestModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Src\Shared\Attachment\Application\DTOs\AttachmentDTO;
use Src\Shared\Attachment\Application\UseCases\CreateAttachmentUseCase;
use Src\Shared\Export\Contracts\PdfExporterInterface;
use Src\StudentRequests\StudentRequest\Application\Contracts\TransferResolutionDocumentInterface;
use Src\StudentRequests\StudentRequest\Domain\Contracts\RequestedCourseRepositoryInterface;
use Src\StudentRequests\StudentRequest\Domain\Entities\RequestedCourse;
use Src\StudentRequests\StudentRequest\Domain\Entities\StudentRequest;

/** Emite el RSREC-001 en PDF y lo guarda como adjunto. */
final class PdfTransferResolutionDocument implements TransferResolutionDocumentInterface
{
    public const DOCUMENT_TYPE = 'Resolución RSREC-001';

    public function __construct(
        private readonly RequestedCourseRepositoryInterface $requestedCourses,
        private readonly PdfExporterInterface $pdf,
        private readonly CreateAttachmentUseCase $createAttachment,
    ) {}

    public function issueFor(int $studentRequestId, ?int $issuedByUserId): void
    {
        $request = StudentRequestModel::query()
            ->with(['student.studyPlans.degreeProgram'])
            ->findOrFail($studentRequestId);

        // La relación está declarada no-nullable en el modelo.
        $student = $request->student;

        $courses = $this->requestedCourses->forRequest($studentRequestId);
        $details = $this->requestedCourses->resolutionDetails($studentRequestId);

        $html = view('exports.rsrec-001', [
            'issuedOn' => now()->format('Y-m-d'),
            'resolutionNumber' => $details['resolutionNumber'],
            'sessionNumber' => $details['sessionNumber'],
            'minutesNumber' => $details['minutesNumber'],
            'sessionDate' => $details['sessionDate'],
            // El nombre se arma acá y no se pide.
            'studentName' => trim(implode(' ', array_filter([
                $student->first_name,
                $student->last_name,
                $student->second_last_name,
            ]))),
            'nationalId' => $student->national_id,
            'degreeProgram' => $student->studyPlans->first()?->degreeProgram?->name,
            'groups' => $this->groupByInstitution($courses),
            'denialReasons' => $this->denialReasons($courses),
        ])->render();

        $bytes = $this->pdf->bytesFromHtml($html, 'letter');

        $uuid = (string) Str::uuid();
        $path = "resolutions/{$uuid}.pdf";

        if (! Storage::disk('local')->put($path, $bytes)) {
            // Sin archivo no hay prueba.
            throw new RuntimeException('No se pudo guardar el documento de resolución.');
        }

        $this->createAttachment->handle(new AttachmentDTO(
            uuid: $uuid,
            userId: $issuedByUserId,
            attachableType: StudentRequest::class,
            attachableId: $studentRequestId,
            documentType: self::DOCUMENT_TYPE,
            originalName: $this->fileName($details['resolutionNumber']),
            disk: 'local',
            path: $path,
            mimeType: 'application/pdf',
            sizeBytes: strlen($bytes),
            sha256Hash: hash('sha256', $bytes),
        ));
    }

    /**
     * Filas de la tabla, agrupadas por universidad de procedencia.
     *
     * @param  array<int, RequestedCourse>  $courses
     * @return array<string, array<int, array{externalCode: string|null, externalName: string, externalCredits: int|null, utnCode: string, utnName: string, utnCredits: int|null, recognized: bool}>>
     */
    private function groupByInstitution(array $courses): array
    {
        $courseIds = array_map(fn (RequestedCourse $c): int => $c->courseId(), $courses);

        // Una sola consulta para todos los cursos.
        $catalog = CourseModel::query()
            ->whereIn('id', $courseIds)
            ->get(['id', 'code', 'name'])
            ->keyBy('id');

        $credits = $this->creditsByCourse($courseIds);

        $groups = [];

        foreach ($courses as $course) {
            $utn = $catalog->get($course->courseId());

            $groups[$course->externalInstitution()][] = [
                'externalCode' => $course->externalCourseCode(),
                'externalName' => $course->externalCourseName(),
                'externalCredits' => $course->externalCredits(),
                // keyBy() devuelve el modelo o null si el curso ya.
                'utnCode' => $utn === null ? '' : $utn->code,
                'utnName' => $utn === null ? '' : $utn->name,
                'utnCredits' => $credits[$course->courseId()] ?? null,
                'recognized' => $course->isRecognized(),
            ];
        }

        return $groups;
    }

    /**
     * Créditos de cada curso de la UTN. Un curso puede.
     *
     * @param  array<int, int>  $courseIds
     * @return array<int, int>
     */
    private function creditsByCourse(array $courseIds): array
    {
        return DB::table('course_level')
            ->whereIn('course_id', $courseIds)
            ->groupBy('course_id')
            ->selectRaw('course_id, MIN(credits) as credits')
            ->pluck('credits', 'course_id')
            ->map(fn ($value): int => (int) $value)
            ->all();
    }

    /**
     * @param  array<int, RequestedCourse>  $courses
     * @return array<int, string>
     */
    private function denialReasons(array $courses): array
    {
        $reasons = [];

        foreach ($courses as $course) {
            if ($course->denialReason() !== null) {
                $reasons[] = $course->externalCourseName().': '.$course->denialReason();
            }
        }

        return $reasons;
    }

    private function fileName(?string $resolutionNumber): string
    {
        $suffix = $resolutionNumber === null ? '' : '-'.Str::slug($resolutionNumber);

        return "RSREC-001{$suffix}.pdf";
    }
}
