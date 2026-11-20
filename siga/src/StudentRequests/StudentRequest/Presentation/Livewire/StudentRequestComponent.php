<?php

declare(strict_types=1);

namespace Src\StudentRequests\StudentRequest\Presentation\Livewire;

use App\Livewire\Concerns\InteractsWithDataTable;
use App\Livewire\Concerns\InteractsWithExports;
use App\Livewire\Concerns\ManagesAttachments;
use App\Models\User;
use DateTimeImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Livewire\Component;
use Livewire\WithFileUploads;
use Src\AcademicCatalog\Course\Application\UseCases\ListCoursesUseCase;
use Src\AcademicCatalog\Course\Domain\Entities\Course;
use Src\AcademicCatalog\Prerequisite\Application\UseCases\ListCoursePrerequisitesUseCase;
use Src\AcademicCatalog\Student\Domain\Contracts\StudentRepositoryInterface;
use Src\AcademicCatalog\Student\Domain\Entities\Student;
use Src\AcademicCatalog\StudentFile\Application\UseCases\FindStudentFileUseCase;
use Src\AcademicCatalog\StudentFile\Domain\Entities\StudentFileCourse;
use Src\Shared\Attachment\Application\UseCases\CreateAttachmentUseCase;
use Src\Shared\Attachment\Application\UseCases\FindAttachmentUseCase;
use Src\Shared\Attachment\Application\UseCases\ListAttachmentsForUseCase;
use Src\Shared\Attachment\Presentation\Support\UploadLimit;
use Src\Shared\Export\Contracts\ExcelExporterInterface;
use Src\Shared\Export\Contracts\PdfExporterInterface;
use Src\StudentRequests\StudentRequest\Application\DTOs\SubmitTransferRequestDTO;
use Src\StudentRequests\StudentRequest\Application\DTOs\SubmitWaiverRequestDTO;
use Src\StudentRequests\StudentRequest\Application\Support\InboxSearchQueryParser;
use Src\StudentRequests\StudentRequest\Application\UseCases\AssignEstimatedResolutionDateUseCase;
use Src\StudentRequests\StudentRequest\Application\UseCases\ChangeStudentRequestStatusUseCase;
use Src\StudentRequests\StudentRequest\Application\UseCases\DecideStudentRequestUseCase;
use Src\StudentRequests\StudentRequest\Application\UseCases\EnsureEstimatedResolutionDateUseCase;
use Src\StudentRequests\StudentRequest\Application\UseCases\FindStudentRequestUseCase;
use Src\StudentRequests\StudentRequest\Application\UseCases\ListStudentRequestsForInboxUseCase;
use Src\StudentRequests\StudentRequest\Application\UseCases\ListStudentRequestsForStudentUseCase;
use Src\StudentRequests\StudentRequest\Application\UseCases\PublishStudentRequestUseCase;
use Src\StudentRequests\StudentRequest\Application\UseCases\ResolveRequestedCourseUseCase;
use Src\StudentRequests\StudentRequest\Application\UseCases\SearchSourceInstitutionsUseCase;
use Src\StudentRequests\StudentRequest\Application\UseCases\SubmitTransferRequestUseCase;
use Src\StudentRequests\StudentRequest\Application\UseCases\SubmitWaiverRequestUseCase;
use Src\StudentRequests\StudentRequest\Domain\Contracts\RequestedCourseRepositoryInterface;
use Src\StudentRequests\StudentRequest\Domain\Entities\StudentRequest;
use Src\StudentRequests\StudentRequest\Presentation\Livewire\Forms\TransferRequestForm;
use Src\StudentRequests\StudentRequest\Presentation\Livewire\Forms\WaiverRequestForm;
use Src\StudentRequests\StudentRequest\Presentation\Support\StudentRequestStatusBadge;
use Src\StudentRequests\StudentRequestStatusHistory\Application\UseCases\ListStudentRequestStatusHistoryUseCase;
use Src\StudentRequests\StudentRequestStatusHistory\Domain\Entities\StudentRequestStatusHistory as StatusHistoryEntry;
use Src\StudentRequests\TransferCreditPrecedent\Application\UseCases\FindTransferCreditPrecedentUseCase;
use Src\StudentRequests\TransferCreditPrecedent\Domain\Entities\TransferCreditPrecedent;
use Src\StudentRequests\TransferCreditPrecedent\Domain\Exceptions\TransferCreditPrecedentNotFoundException;
use Src\StudentRequests\WaiverRule\Domain\Entities\WaiverRule;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Una sola ruta, un solo componente. */
class StudentRequestComponent extends Component
{
    use AuthorizesRequests;

    // Alias del hook del trait para poder extenderlo.
    use InteractsWithDataTable {
        updatingSearch as private resetPageOnSearch;
    }
    use InteractsWithExports;
    use ManagesAttachments;
    use WithFileUploads;

    /** La bandeja pagina en servidor. */
    protected string $tableMode = 'server';

    /** Tope de filas por exportación. */
    private const EXPORT_ROW_LIMIT = 10000;

    /**
     * Las pestañas del personal.
     *
     * @var array<int, string>
     */
    private const STAFF_TABS = ['inbox', 'inbox-transfer', 'archive'];

    public string $activeTab = 'waiver';

    public WaiverRequestForm $waiverForm;

    public TransferRequestForm $transferForm;

    /**
     * Resultado inmediato de ES-01 a mostrar al estudiante tras enviar.
     *
     * @var array{variant: string, text: string}|null
     */
    public ?array $waiverOutcomeBanner = null;

    /**
     * @var array{variant: string, text: string}|null
     */
    public ?array $transferOutcomeBanner = null;

    /**
     * ES-02: sugerencias en vivo para "institución de origen".
     *
     * @var array<int, string>
     */
    public array $sourceInstitutionSuggestions = [];

    public bool $showDetailModal = false;

    public ?int $viewingRequestId = null;

    /**
     * @var array<string, mixed>|null
     */
    public ?array $requestDetail = null;

    public string $statusChangeComment = '';

    public string $manualResolutionDate = '';

    /**
     * Documento que el personal adjunta desde el detalle.
     *
     * @var mixed
     */
    public $newAttachment = null;

    public string $newAttachmentType = '';

    /** Estado que el menú de Acciones eligió al abrir. */
    public string $pendingStatus = '';

    /** Acotar la bandeja a las solicitudes sin resolver. */
    public bool $onlyOpen = false;

    /** ¿Está desplegado el expediente dentro del detalle?. */
    public bool $showStudentFile = true;

    /**
     * Lo que Registro está escribiendo sobre cada curso.
     *
     * @var array<int, array{code: string, credits: string, grade: string, reason: string}>
     */
    public array $courseEdits = [];

    /**
     * Datos que el RSREC-001 imprime en su encabezado.
     *
     * @var array{resolutionNumber: string, sessionNumber: string, minutesNumber: string, sessionDate: string}
     */
    public array $resolutionForm = [
        'resolutionNumber' => '',
        'sessionNumber' => '',
        'minutesNumber' => '',
        'sessionDate' => '',
    ];

    /**
     * @var array<int, Course>|null
     */
    private ?array $courseCatalog = null;

    private ?StudentRequestStatusBadge $statusBadge = null;

    public function mount(): void
    {
        $this->activeTab = $this->isStudent() ? 'waiver' : ($this->canReviewInbox() ? 'inbox' : 'waiver');

        // ?tab= entra directo a una sección, validando permiso.
        $requested = (string) request()->query('tab', '');
        if (in_array($requested, self::STAFF_TABS, true) && $this->canReviewInbox()) {
            $this->activeTab = $requested;
        }

        $this->sortKey = 'created_at';
        $this->sortDir = 'desc';
    }

    public function isStudent(): bool
    {
        return Auth::user()?->student !== null;
    }

    public function canReviewInbox(): bool
    {
        return Auth::user() !== null && Auth::user()->can('viewAny', StudentRequest::class);
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;

        // Son listas distintas: la página vuelve a uno.
        $this->page = 1;
    }

    /** Las dos secciones: levantamientos y convalidaciones. */
    public function isInboxTab(): bool
    {
        return in_array($this->activeTab, self::STAFF_TABS, true);
    }

    /** El Historial: el archivo de lo ya resuelto. */
    public function isArchiveTab(): bool
    {
        return $this->activeTab === 'archive';
    }

    /** De qué tipo es la bandeja actual. */
    public function inboxType(): ?string
    {
        if ($this->isArchiveTab()) {
            return null;
        }

        return $this->activeTab === 'inbox-transfer'
            ? StudentRequest::TYPE_TRANSFER
            : StudentRequest::TYPE_WAIVER;
    }

    /** Cambiar de curso invalida el requisito ya elegido. */
    public function updatedWaiverFormCourseId(): void
    {
        $this->waiverForm->prerequisiteCourseId = null;
    }

    /** Quitar el documento adjunto ANTES de enviar la boleta. */
    public function removeWaiverAttachment(): void
    {
        $this->waiverForm->attachment = null;

        // El error anterior deja de tener sujeto.
        $this->resetValidation('waiverForm.attachment');
    }

    /** ES-01: verifica duplicados, evalúa el motor de reglas. */
    public function submitWaiver(SubmitWaiverRequestUseCase $useCase, CreateAttachmentUseCase $attachmentUseCase): void
    {
        $this->authorize('create', StudentRequest::class);

        $this->waiverForm->validate();

        $student = Auth::user()->student;

        $result = $useCase->handle(new SubmitWaiverRequestDTO(
            studentId: $student->id,
            courseId: (int) $this->waiverForm->courseId,
            prerequisiteCourseId: (int) $this->waiverForm->prerequisiteCourseId,
            justification: $this->waiverForm->justification,
            acceptsTerms: $this->waiverForm->acceptsTerms,
        ));

        if ($result->isDuplicate) {
            $this->waiverOutcomeBanner = [
                'variant' => 'warning',
                'text' => __('This waiver has already been processed previously.'),
            ];
            $this->waiverForm->reset();

            return;
        }

        $studentRequest = $result->studentRequest;

        $this->storeAttachment(
            $attachmentUseCase,
            $this->waiverForm->attachment,
            $studentRequest->id(),
            __('Supporting document'),
        );

        $this->waiverOutcomeBanner = $this->outcomeBannerFor($studentRequest);
        $this->waiverForm->reset();
        $this->dispatch('toast', variant: 'success', text: __('Request submitted.'));
    }

    /** ES-02: siempre se asigna a la Comisión Técnica. */
    public function submitTransfer(SubmitTransferRequestUseCase $useCase, CreateAttachmentUseCase $attachmentUseCase): void
    {
        $this->authorize('create', StudentRequest::class);

        $this->transferForm->validate();

        $student = Auth::user()->student;

        // Se comprueba acá y no con una regla `distinct`.
        if ($this->transferForm->hasDuplicateCourses()) {
            $this->addError('transferForm.courses', __('The same UTN course is requested more than once.'));

            return;
        }

        $studentRequest = $useCase->handle(new SubmitTransferRequestDTO(
            studentId: $student->id,
            courses: array_map(fn (array $row): array => [
                'courseId' => (int) $row['courseId'],
                'externalCourse' => trim($row['externalCourse']),
                'sourceInstitution' => trim($row['sourceInstitution']),
            ], $this->transferForm->courses),
        ));

        // Lista abierta: ya no hay tres documentos con nombre propio.
        foreach ($this->transferForm->attachments as $attachment) {
            $this->storeAttachment($attachmentUseCase, $attachment, $studentRequest->id(), __('Supporting document'));
        }

        $this->transferOutcomeBanner = [
            'variant' => 'success',
            'text' => $studentRequest->transferCreditPrecedentId() !== null
                ? __('Request sent to the Technical Commission — approved precedent found, simplified evaluation possible.')
                : __('Request sent to the Technical Commission for evaluation.'),
        ];
        $this->transferForm->reset();
        $this->dispatch('toast', variant: 'success', text: __('Request submitted.'));
    }

    /** Sugerencias de institución mientras el estudiante escribe. */
    public function addTransferCourse(): void
    {
        $this->transferForm->addCourse();
    }

    public function removeTransferCourse(int $index): void
    {
        $this->transferForm->removeCourse($index);
    }

    /** Registro transcribe el código y los créditos que trae. */
    public function saveRequestedCourseData(
        int $requestedCourseId,
        ResolveRequestedCourseUseCase $useCase,
        FindStudentRequestUseCase $findRequest,
        ListAttachmentsForUseCase $listAttachments,
        ListStudentRequestStatusHistoryUseCase $listHistory,
        ListCoursesUseCase $coursesUseCase,
        StudentRepositoryInterface $studentRepository,
    ): void {
        $current = $findRequest->handle((int) $this->viewingRequestId);
        // Transcribir los datos del curso externo es de Registro.
        $this->authorizeAbility($current, 'verify');

        // Unión y no ??: completa las claves que falten en un estado viejo.
        $edit = ($this->courseEdits[$requestedCourseId] ?? [])
            + ['code' => '', 'credits' => '', 'grade' => '', 'reason' => ''];

        $useCase->completeExternalData(
            (int) $this->viewingRequestId,
            $requestedCourseId,
            $edit['code'] === '' ? null : $edit['code'],
            $edit['credits'] === '' ? null : (int) $edit['credits'],
            $edit['grade'] === '' ? null : (float) $edit['grade'],
        );

        $this->requestDetail = $this->buildRequestDetail($current, $listAttachments, $listHistory, $coursesUseCase, $studentRepository);
        $this->dispatch('toast', variant: 'success', text: __('Course data saved.'));
    }

    /** La casilla Sí/No del RSREC-001, curso por curso. */
    public function resolveRequestedCourse(
        int $requestedCourseId,
        string $outcome,
        ResolveRequestedCourseUseCase $useCase,
        FindStudentRequestUseCase $findRequest,
        ListAttachmentsForUseCase $listAttachments,
        ListStudentRequestStatusHistoryUseCase $listHistory,
        ListCoursesUseCase $coursesUseCase,
        StudentRepositoryInterface $studentRepository,
    ): void {
        $current = $findRequest->handle((int) $this->viewingRequestId);
        // La casilla Sí/No es de Docencia.
        $this->authorizeAbility($current, 'update');

        $reason = $this->courseEdits[$requestedCourseId]['reason'] ?? '';

        try {
            $useCase->resolve((int) $this->viewingRequestId, $requestedCourseId, $outcome, $reason);
        } catch (InvalidArgumentException $exception) {
            // Un «No reconocido» exige indicar la razón.
            $this->dispatch('toast', variant: 'danger', text: $exception->getMessage());

            return;
        }

        $this->requestDetail = $this->buildRequestDetail($current, $listAttachments, $listHistory, $coursesUseCase, $studentRepository);
    }

    /** DOCENCIA resuelve y devuelve la solicitud a Registro. */
    public function decideRequest(
        string $decision,
        DecideStudentRequestUseCase $useCase,
        FindStudentRequestUseCase $findRequest,
        ListAttachmentsForUseCase $listAttachments,
        ListStudentRequestStatusHistoryUseCase $listHistory,
        ListCoursesUseCase $coursesUseCase,
        StudentRepositoryInterface $studentRepository,
    ): void {
        $current = $findRequest->handle((int) $this->viewingRequestId);
        $this->authorizeAbility($current, 'update');

        try {
            $useCase->handle(
                id: (int) $this->viewingRequestId,
                reviewerId: (int) Auth::id(),
                decision: $decision !== '' ? $decision : null,
                comment: $this->statusChangeComment !== '' ? $this->statusChangeComment : null,
            );
        } catch (InvalidArgumentException $exception) {
            $this->dispatch('toast', variant: 'danger', text: $exception->getMessage());

            return;
        }

        $updated = $findRequest->handle((int) $this->viewingRequestId);
        $this->requestDetail = $this->buildRequestDetail($updated, $listAttachments, $listHistory, $coursesUseCase, $studentRepository);
        $this->statusChangeComment = '';
        $this->dispatch('toast', variant: 'success', text: __('Sent to Registro for publication.'));
    }

    /** REGISTRO publica: emite el documento, lo archiva. */
    public function publishResolution(
        PublishStudentRequestUseCase $useCase,
        FindStudentRequestUseCase $findRequest,
        ListAttachmentsForUseCase $listAttachments,
        ListStudentRequestStatusHistoryUseCase $listHistory,
        ListCoursesUseCase $coursesUseCase,
        StudentRepositoryInterface $studentRepository,
    ): void {
        $current = $findRequest->handle((int) $this->viewingRequestId);
        $this->authorizeAbility($current, 'verify');

        try {
            $useCase->handle(
                id: (int) $this->viewingRequestId,
                registrarId: (int) Auth::id(),
                resolutionNumber: $this->resolutionForm['resolutionNumber'],
                sessionNumber: $this->resolutionForm['sessionNumber'],
                minutesNumber: $this->resolutionForm['minutesNumber'],
                sessionDate: $this->resolutionForm['sessionDate'] !== ''
                    ? new DateTimeImmutable($this->resolutionForm['sessionDate'])
                    : null,
            );
        } catch (InvalidArgumentException $exception) {
            $this->dispatch('toast', variant: 'danger', text: $exception->getMessage());

            return;
        }

        $updated = $findRequest->handle((int) $this->viewingRequestId);
        $this->requestDetail = $this->buildRequestDetail($updated, $listAttachments, $listHistory, $coursesUseCase, $studentRepository);
        $this->dispatch('toast', variant: 'success', text: __('Resolution published and sent to the student.'));
    }

    /** Cada acción sobre los cursos pertenece a UN escritorio. */
    private function authorizeAbility(StudentRequest $studentRequest, string $ability): void
    {
        if (! (Auth::user()?->can($ability, $studentRequest) ?? false)) {
            abort(403);
        }
    }

    public function searchSourceInstitutions(string $query): void
    {
        $this->sourceInstitutionSuggestions = app(SearchSourceInstitutionsUseCase::class)->handle($query);
    }

    /** Envoltura delgada sobre ManagesAttachments::persistAttachment(). */
    private function storeAttachment(CreateAttachmentUseCase $useCase, mixed $file, int $studentRequestId, string $documentType): void
    {
        $this->persistAttachment(
            $useCase,
            $file,
            StudentRequest::class,
            $studentRequestId,
            $documentType,
            directory: 'student-requests',
        );
    }

    /** Adjunto subido por el PERSONAL sobre una solicitud ya presentada. */
    public function uploadStaffAttachment(
        CreateAttachmentUseCase $createAttachment,
        FindStudentRequestUseCase $findRequest,
        ListAttachmentsForUseCase $listAttachments,
        ListStudentRequestStatusHistoryUseCase $listHistory,
        ListCoursesUseCase $coursesUseCase,
        StudentRepositoryInterface $studentRepository,
    ): void {
        $current = $findRequest->handle((int) $this->viewingRequestId);
        $this->authorize('update', $current);

        $this->validate([
            'newAttachment' => $this->attachmentRules(),
            'newAttachmentType' => ['required', 'string', 'max:60'],
        ]);

        $this->storeAttachment(
            $createAttachment,
            $this->newAttachment,
            (int) $this->viewingRequestId,
            $this->newAttachmentType,
        );

        $this->requestDetail = $this->buildRequestDetail($current, $listAttachments, $listHistory, $coursesUseCase, $studentRepository);
        $this->newAttachment = null;
        $this->newAttachmentType = '';
        $this->dispatch('toast', variant: 'success', text: __('Document attached.'));
    }

    /**
     * @return array{variant: string, text: string}
     */
    private function outcomeBannerFor(StudentRequest $studentRequest): array
    {
        return match ($studentRequest->engineResult()) {
            StudentRequest::RESULT_AUTOMATIC => ['variant' => 'success', 'text' => __('Proceeds automatically.')],
            StudentRequest::RESULT_DENIED => ['variant' => 'danger', 'text' => __('Does not proceed: the configured rule was not met.')],
            default => ['variant' => 'info', 'text' => __('Requires manual review — request sent to Docencia.')],
        };
    }

    /** ES-04/ES-03: abrir el detalle dispara la asignación automática de fecha. */
    public function openRequestDetail(
        int $id,
        string $intendedStatus,
        FindStudentRequestUseCase $findRequest,
        EnsureEstimatedResolutionDateUseCase $ensureDate,
        ListAttachmentsForUseCase $listAttachments,
        ListStudentRequestStatusHistoryUseCase $listHistory,
        ListCoursesUseCase $coursesUseCase,
        StudentRepositoryInterface $studentRepository,
    ): void {
        $studentRequest = $findRequest->handle($id);
        $this->authorize('view', $studentRequest);

        $studentRequest = $ensureDate->handle($id);

        $this->viewingRequestId = $id;
        $this->requestDetail = $this->buildRequestDetail($studentRequest, $listAttachments, $listHistory, $coursesUseCase, $studentRepository);
        $this->statusChangeComment = '';
        $this->manualResolutionDate = '';
        $this->newAttachment = null;
        $this->newAttachmentType = '';
        // Solo se preselecciona un estado válido.
        $this->pendingStatus = in_array($intendedStatus, StudentRequest::STATUSES, true) ? $intendedStatus : '';
        $this->showDetailModal = true;
    }

    public function toggleStudentFile(): void
    {
        $this->showStudentFile = ! $this->showStudentFile;
    }

    public function closeModal(): void
    {
        $this->showDetailModal = false;
        $this->viewingRequestId = null;
        $this->requestDetail = null;
    }

    public function changeRequestStatus(
        string $newStatus,
        ChangeStudentRequestStatusUseCase $useCase,
        FindStudentRequestUseCase $findRequest,
        ListAttachmentsForUseCase $listAttachments,
        ListStudentRequestStatusHistoryUseCase $listHistory,
        ListCoursesUseCase $coursesUseCase,
        StudentRepositoryInterface $studentRepository,
    ): void {
        $current = $findRequest->handle((int) $this->viewingRequestId);

        // Se autoriza contra la transición concreta.
        if (! in_array($newStatus, $this->permittedTransitions($current), true)) {
            abort(403);
        }

        try {
            $useCase->handle(
                (int) $this->viewingRequestId,
                $newStatus,
                (int) Auth::id(),
                $this->statusChangeComment !== '' ? $this->statusChangeComment : null,
                // Se vuelve a preguntar en vez de deducirlo.
                Auth::user()?->can('overrideWorkflow', $current) ?? false,
            );
        } catch (InvalidArgumentException $exception) {
            // Sin catch, un error del Dominio rompe la pantalla.
            $this->dispatch('toast', variant: 'danger', text: $exception->getMessage());

            return;
        }

        $updated = $findRequest->handle((int) $this->viewingRequestId);
        $this->requestDetail = $this->buildRequestDetail($updated, $listAttachments, $listHistory, $coursesUseCase, $studentRepository);
        $this->statusChangeComment = '';
        $this->dispatch('toast', variant: 'success', text: __('Status updated.'));
    }

    public function assignManualResolutionDate(
        AssignEstimatedResolutionDateUseCase $useCase,
        FindStudentRequestUseCase $findRequest,
        ListAttachmentsForUseCase $listAttachments,
        ListStudentRequestStatusHistoryUseCase $listHistory,
        ListCoursesUseCase $coursesUseCase,
        StudentRepositoryInterface $studentRepository,
    ): void {
        $current = $findRequest->handle((int) $this->viewingRequestId);
        $this->authorize('update', $current);

        $this->validate(['manualResolutionDate' => ['required', 'date', 'after_or_equal:today']]);

        $useCase->handle((int) $this->viewingRequestId, new DateTimeImmutable($this->manualResolutionDate));

        $updated = $findRequest->handle((int) $this->viewingRequestId);
        $this->requestDetail = $this->buildRequestDetail($updated, $listAttachments, $listHistory, $coursesUseCase, $studentRepository);
        $this->manualResolutionDate = '';
        $this->dispatch('toast', variant: 'success', text: __('Estimated resolution date saved.'));
    }

    public function downloadAttachment(int $attachmentId, FindAttachmentUseCase $findAttachment, FindStudentRequestUseCase $findRequest): StreamedResponse
    {
        $attachment = $findAttachment->handle($attachmentId);
        $owningRequest = $findRequest->handle($attachment->attachableId());
        $this->authorize('view', $owningRequest);

        return $this->streamAttachmentDownload($attachment);
    }

    /** Descarga de un documento del PRECEDENTE que respalda la convalidación. */
    public function downloadPrecedentAttachment(
        int $attachmentId,
        FindAttachmentUseCase $findAttachment,
        FindStudentRequestUseCase $findRequest,
    ): StreamedResponse {
        $current = $findRequest->handle((int) $this->viewingRequestId);
        $this->authorize('view', $current);

        $attachment = $findAttachment->handle($attachmentId);

        $belongsToThisPrecedent = $attachment->attachableType() === TransferCreditPrecedent::class
            && $current->transferCreditPrecedentId() !== null
            && $attachment->attachableId() === $current->transferCreditPrecedentId();

        if (! $belongsToThisPrecedent) {
            abort(404);
        }

        return $this->streamAttachmentDownload($attachment);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildRequestDetail(
        StudentRequest $studentRequest,
        ListAttachmentsForUseCase $listAttachments,
        ListStudentRequestStatusHistoryUseCase $listHistory,
        ListCoursesUseCase $coursesUseCase,
        StudentRepositoryInterface $studentRepository,
    ): array {
        // El detalle muestra el curso y su requisito.
        $courses = $this->coursesFor($coursesUseCase, $this->courseIdsIn([$studentRequest]));
        $course = $courses[$studentRequest->courseId()] ?? null;
        $prerequisite = $studentRequest->prerequisiteCourseId() !== null
            ? ($courses[$studentRequest->prerequisiteCourseId()] ?? null)
            : null;

        $student = $studentRepository->find($studentRequest->studentId());

        return [
            'id' => $studentRequest->id(),
            // canUpdate sigue gobernando lo que NO es un cambio.
            'canUpdate' => Auth::user()?->can('update', $studentRequest) ?? false,
            // Las acciones de estado que esta persona puede ejecutar.
            'nextStatuses' => $this->permittedTransitions($studentRequest),
            'studentName' => $student?->fullName() ?? "#{$studentRequest->studentId()}",
            // Expediente completo del estudiante.
            'studentFile' => $this->canReviewInbox()
                ? $this->studentFileRows($studentRequest->studentId())
                : null,
            'type' => $studentRequest->type(),
            'courseLabel' => $course ? "{$course->code()} — {$course->name()}" : "#{$studentRequest->courseId()}",
            'prerequisiteLabel' => $prerequisite ? "{$prerequisite->code()} — {$prerequisite->name()}" : null,
            'sourceInstitution' => $studentRequest->sourceInstitution(),
            'externalCourse' => $studentRequest->externalCourse(),
            'justification' => $studentRequest->justification(),
            'termsAcceptedAt' => $studentRequest->termsAcceptedAt()?->format('Y-m-d H:i'),
            // Tres capacidades: transcribir, decidir y publicar.
            'canCompleteExternalData' => (Auth::user()?->can('verify', $studentRequest) ?? false)
                && ! $studentRequest->isResolved()
                && ! $studentRequest->awaitsPublication(),
            'canDecideCourses' => (Auth::user()?->can('update', $studentRequest) ?? false)
                && $studentRequest->status() === StudentRequest::STATUS_IN_REVIEW,
            'canPublish' => (Auth::user()?->can('verify', $studentRequest) ?? false)
                && $studentRequest->awaitsPublication(),
            'awaitsPublication' => $studentRequest->awaitsPublication(),
            'teachingDecision' => $studentRequest->teachingDecision(),
            'isResolved' => $studentRequest->isResolved(),
            // Los botones genéricos de estado NO incluyen la decisión.
            'genericStatuses' => $studentRequest->awaitsPublication()
                && ! (Auth::user()?->can('overrideWorkflow', $studentRequest) ?? false)
                    ? []
                    : array_values(array_filter(
                        $this->permittedTransitions($studentRequest),
                        fn (string $status): bool => $status !== StudentRequest::STATUS_DECIDED,
                    )),
            // Los cursos de una convalidación, con su resolución individual.
            'requestedCourses' => $studentRequest->type() === StudentRequest::TYPE_TRANSFER
                ? $this->requestedCourseRows((int) $studentRequest->id())
                : [],
            'resolutionDetails' => $studentRequest->type() === StudentRequest::TYPE_TRANSFER
                ? app(RequestedCourseRepositoryInterface::class)->resolutionDetails((int) $studentRequest->id())
                : null,
            'hasPrecedent' => $studentRequest->transferCreditPrecedentId() !== null,
            'precedent' => $this->precedentDetail($studentRequest, $listAttachments),
            // El estudiante no debe enterarse de que existe un precedente.
            'canReviewInbox' => $this->canReviewInbox(),
            'engineResult' => $studentRequest->engineResult(),
            'status' => $studentRequest->status(),
            'statusVariant' => $this->badge()->variantFor(
                $studentRequest->status(),
                $studentRequest->engineResult(),
                $studentRequest->estimatedResolutionDate(),
                new DateTimeImmutable,
            ),
            'deadlineLabel' => $this->badge()->deadlineLabel(
                $studentRequest->status(),
                $studentRequest->estimatedResolutionDate(),
                new DateTimeImmutable,
            ),
            'estimatedResolutionDate' => $studentRequest->estimatedResolutionDate()?->format('Y-m-d'),
            'createdAt' => $studentRequest->createdAt()?->format('Y-m-d H:i'),
            'attachments' => $this->attachmentRows($listAttachments, StudentRequest::class, (int) $studentRequest->id()),
            // Avance del trámite tal como lo pidió el equipo.
            'progress' => $this->progressSteps($studentRequest),
            'history' => $this->historyRows($listHistory->handle((int) $studentRequest->id())),
        ];
    }

    /** Escribir en el buscador apaga el "solo abiertas" implícito. */
    public function updatingSearch(): void
    {
        $this->resetPageOnSearch();

        $this->onlyOpen = false;
    }

    public function render(
        ListCoursesUseCase $coursesUseCase,
        ListStudentRequestsForStudentUseCase $listForStudent,
        ListStudentRequestsForInboxUseCase $listForInbox,
        StudentRepositoryInterface $studentRepository,
        ListCoursePrerequisitesUseCase $prerequisites,
    ): View {
        // Cada pestaña carga SOLO lo suyo.
        $showsStudentForms = $this->isStudent()
            && in_array($this->activeTab, ['waiver', 'transfer'], true);

        // El catálogo completo solo hace falta cuando hay un <select>.
        $fullCatalog = $showsStudentForms ? $this->courses($coursesUseCase) : [];

        $myRequests = [];
        if ($this->isStudent() && $this->activeTab === 'mine') {
            $requests = $listForStudent->handle(Auth::user()->student->id);
            $courses = $this->coursesFor($coursesUseCase, $this->courseIdsIn($requests));
            $myRequests = array_map(fn ($r) => $this->toSummaryRow($r, $courses), $requests);
        }

        // El buscador de esta tabla vive en Alpine (mode="client").
        $myRequestsVersion = md5(serialize($myRequests));

        $inboxPaginator = null;
        if ($this->canReviewInbox() && $this->isInboxTab()) {
            $result = $listForInbox->handle($this->inboxFilters(), $this->sortKey, $this->sortDir, $this->perPage, $this->page);
            $inboxPaginator = new LengthAwarePaginator(
                items: $this->toInboxRows($result['items'], $coursesUseCase, $studentRepository),
                total: $result['total'],
                perPage: $this->perPage,
                currentPage: $this->page,
            );
        }

        /** @disregard P1013 Livewire registra ->layout() como macro en runtime */
        return view('studentrequests.studentrequest.livewire.student-request-component', [
            'studentCourseOptions' => $showsStudentForms
                ? $this->coursesAvailableToStudent($fullCatalog, $studentRepository)
                : [],
            'studentWaiverCourseOptions' => $showsStudentForms
                ? $this->coursesWithPendingRequirements($fullCatalog, $studentRepository, $prerequisites)
                : [],
            'studentRequirementOptions' => $showsStudentForms
                ? $this->requirementsForSelectedCourse($fullCatalog, $studentRepository, $prerequisites)
                : [],
            'myRequests' => $myRequests,
            'myRequestsVersion' => $myRequestsVersion,
            'inboxPaginator' => $inboxPaginator,
            // Las cinco justificaciones de la boleta.
            'studentJustifications' => WaiverRule::STUDENT_JUSTIFICATIONS,
            // El límite REAL de subida, no el del módulo.
            'uploadLimit' => UploadLimit::label(),
            'uploadLimitedByPhp' => UploadLimit::isConstrainedByPhp(),
        ])->layout('components.layouts.dashboard', [
            'title' => __('Student Requests'),
            'subtitle' => __('Requirement waivers and external course transfer requests'),
        ]);
    }

    /**
     * Un único cuadro de búsqueda.
     *
     * @return array{search?: string, type?: string, status?: string, career?: string, dateFrom?: string, dateTo?: string, openOnly?: bool, resolvedOnly?: bool, deskStatuses?: array<int, string>}
     */
    private function inboxFilters(?string $explicitSearch = null): array
    {
        $search = $this->authorizedSearch($explicitSearch);

        $filters = $search !== null ? InboxSearchQueryParser::parse($search) : [];

        // La sección manda sobre el token tipo escrito.
        $type = $this->inboxType();
        if ($type !== null) {
            $filters['type'] = $type;
        } else {
            unset($filters['type']);
        }

        // Bandeja es lo pendiente; Historial, lo cerrado.
        if ($this->isArchiveTab()) {
            $filters['resolvedOnly'] = true;
            unset($filters['openOnly']);
        } elseif ($this->isInboxTab()) {
            // La bandeja ya no muestra lo resuelto.
            $filters['openOnly'] = true;
        } elseif ($this->onlyOpen) {
            $filters['openOnly'] = true;
        }

        // El equipo reportó ver convalidaciones sin verificar en la bandeja.
        $deskStatuses = $this->isArchiveTab() ? null : $this->currentDeskStatuses();
        if ($deskStatuses !== null) {
            $filters['deskStatuses'] = $deskStatuses;
        }

        return $filters;
    }

    /**
     * A qué escritorio le toca la bandeja de ESTA cuenta.
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

    /** Respeta StudentRequestPolicy::search() antes de consultar. */
    private function authorizedSearch(?string $explicit = null): ?string
    {
        if (! Auth::user()?->can('search', StudentRequest::class)) {
            return null;
        }

        $candidate = filled($explicit) ? $explicit : $this->search;

        return $candidate !== '' ? $candidate : null;
    }

    /** Título y nombre del archivo, según la pestaña. */
    private function exportTitle(): string
    {
        return $this->isArchiveTab()
            ? __('Resolved requests history')
            : __('Student requests');
    }

    public function exportPdf(PdfExporterInterface $exporter, ListStudentRequestsForInboxUseCase $useCase, ListCoursesUseCase $coursesUseCase, StudentRepositoryInterface $studentRepository, ?string $search = null): StreamedResponse
    {
        $this->authorize('exportPdf', StudentRequest::class);

        return $this->streamPdf(
            $this->exportTitle(),
            $this->exportHeaders(),
            $this->exportableRows($useCase, $coursesUseCase, $studentRepository, $search),
            Str::slug($this->exportTitle()).'.pdf',
            $exporter,
            paperSize: 'letter',
        );
    }

    public function exportExcel(ExcelExporterInterface $exporter, ListStudentRequestsForInboxUseCase $useCase, ListCoursesUseCase $coursesUseCase, StudentRepositoryInterface $studentRepository, ?string $search = null): StreamedResponse
    {
        $this->authorize('exportExcel', StudentRequest::class);

        return $this->streamExcel(
            $this->exportHeaders(),
            $this->exportableRows($useCase, $coursesUseCase, $studentRepository, $search),
            Str::slug($this->exportTitle()).'.xlsx',
            $exporter,
        );
    }

    /**
     * La bandeja pagina en servidor.
     *
     * @return array<int, array<string, mixed>>
     */
    private function exportableRows(
        ListStudentRequestsForInboxUseCase $useCase,
        ListCoursesUseCase $coursesUseCase,
        StudentRepositoryInterface $studentRepository,
        ?string $search,
    ): array {
        $result = $useCase->handle(
            $this->inboxFilters($search),
            $this->sortKey,
            $this->sortDir,
            perPage: self::EXPORT_ROW_LIMIT,
            page: 1,
        );

        // Dos consultas para TODAS las filas exportadas.
        return $this->toInboxRows($result['items'], $coursesUseCase, $studentRepository);
    }

    /**
     * @return array<int, array{key: string, label: string}>
     */
    private function exportHeaders(): array
    {
        return [
            ['key' => 'createdAt', 'label' => __('Received')],
            ['key' => 'type', 'label' => __('Type')],
            ['key' => 'studentName', 'label' => __('Student')],
            ['key' => 'courseLabel', 'label' => __('Course')],
            ['key' => 'status', 'label' => __('Status')],
        ];
    }

    /**
     * @param  array<int, Course>  $courses
     * @return array<string, mixed>
     */
    private function toSummaryRow(StudentRequest $studentRequest, array $courses): array
    {
        $course = $courses[$studentRequest->courseId()] ?? null;

        return [
            'id' => $studentRequest->id(),
            'type' => $studentRequest->type(),
            'courseLabel' => $course ? "{$course->code()} — {$course->name()}" : "#{$studentRequest->courseId()}",
            // Esta tabla no usa publicStatus().
            'status' => $studentRequest->isResolved()
                ? $studentRequest->status()
                : StudentRequest::PUBLIC_RECEIVED,
            'statusVariant' => $this->badge()->variantFor(
                $studentRequest->status(),
                $studentRequest->engineResult(),
                $studentRequest->estimatedResolutionDate(),
                new DateTimeImmutable,
            ),
            // Fecha de ENVÍO, no la estimada de resolución.
            'sentAt' => $studentRequest->createdAt()?->format('Y-m-d'),
        ];
    }

    /**
     * @param  array<int, Course>  $courses
     * @return array<string, mixed>
     */
    /**
     * Recorrido del trámite en los DOS momentos que se le.
     *
     * @return array<int, array{label: string, reached: bool, current: bool}>
     */
    private function progressSteps(StudentRequest $studentRequest): array
    {
        $isResolved = $studentRequest->isResolved();

        return [
            [
                'label' => StudentRequest::PUBLIC_RECEIVED,
                // Toda solicitud nace recibida.
                'reached' => true,
                'current' => ! $isResolved,
            ],
            [
                // Se muestra el desenlace real si ya lo hay.
                'label' => $isResolved ? $studentRequest->status() : __('Approved or denied'),
                'reached' => $isResolved,
                'current' => $isResolved,
            ],
        ];
    }

    /**
     * Arma las filas de la bandeja resolviendo cursos y estudiantes.
     *
     * @param  array<int, StudentRequest>  $requests
     * @return array<int, array<string, mixed>>
     */
    private function toInboxRows(array $requests, ListCoursesUseCase $coursesUseCase, StudentRepositoryInterface $studentRepository): array
    {
        $courses = $this->coursesFor($coursesUseCase, $this->courseIdsIn($requests));

        $studentIds = [];
        foreach ($requests as $request) {
            $studentIds[] = $request->studentId();
        }
        $students = $studentRepository->findManyByIds($studentIds);

        return array_map(
            fn (StudentRequest $request): array => $this->toInboxRow($request, $courses, $students),
            $requests,
        );
    }

    /**
     * Ids de curso referenciados por un conjunto de solicitudes.
     *
     * @param  array<int, StudentRequest>  $requests
     * @return array<int, int>
     */
    private function courseIdsIn(array $requests): array
    {
        $ids = [];
        foreach ($requests as $request) {
            $ids[] = $request->courseId();
            if ($request->prerequisiteCourseId() !== null) {
                $ids[] = $request->prerequisiteCourseId();
            }
        }

        return $ids;
    }

    /**
     * @param  array<int, int>  $ids
     * @return array<int, Course>
     */
    private function coursesFor(ListCoursesUseCase $useCase, array $ids): array
    {
        return $ids === [] ? [] : $useCase->findManyByIds($ids);
    }

    /**
     * @param  array<int, Course>  $courses
     * @param  array<int, Student>  $students
     * @return array<string, mixed>
     */
    private function toInboxRow(StudentRequest $studentRequest, array $courses, array $students): array
    {
        $course = $courses[$studentRequest->courseId()] ?? null;
        $student = $students[$studentRequest->studentId()] ?? null;

        return [
            'id' => $studentRequest->id(),
            // Se resuelve aquí, con la Entity real.
            'studentName' => $student?->fullName() ?? "#{$studentRequest->studentId()}",
            'type' => $studentRequest->type(),
            'courseLabel' => $course ? "{$course->code()} — {$course->name()}" : "#{$studentRequest->courseId()}",
            'status' => $studentRequest->status(),
            'statusVariant' => $this->badge()->variantFor(
                $studentRequest->status(),
                $studentRequest->engineResult(),
                $studentRequest->estimatedResolutionDate(),
                new DateTimeImmutable,
            ),
            'createdAt' => $studentRequest->createdAt()?->format('Y-m-d'),
        ];
    }

    /**
     * Historial del expediente con las dos cosas que faltaban.
     *
     * @param  array<int, StatusHistoryEntry>  $entries
     * @return array<int, array<string, mixed>>
     */
    private function historyRows(array $entries): array
    {
        $userIds = [];
        foreach ($entries as $entry) {
            if ($entry->userId() !== null) {
                $userIds[] = $entry->userId();
            }
        }

        $names = $userIds === []
            ? []
            : User::query()->whereIn('id', array_unique($userIds))->pluck('name', 'id')->all();

        // El estudiante no debe enterarse del criterio interno.
        $canReviewInbox = $this->canReviewInbox();
        $fixedPrefixes = [
            __('Sent to the Technical Commission.'),
            __('Sent to Docencia for manual review.'),
        ];

        return array_map(static function (StatusHistoryEntry $entry) use ($names, $canReviewInbox, $fixedPrefixes): array {
            $userId = $entry->userId();
            $comment = $entry->comment();

            if ($comment !== null && ! $canReviewInbox) {
                foreach ($fixedPrefixes as $prefix) {
                    if (str_starts_with($comment, $prefix)) {
                        $comment = $prefix;
                        break;
                    }
                }
            }

            return [
                'previousStatus' => $entry->previousStatus(),
                'newStatus' => $entry->newStatus(),
                'comment' => $comment,
                'userId' => $userId,
                'occurredAt' => $entry->occurredAt()?->format('Y-m-d H:i'),
                // user_id NULL significa que lo resolvió el motor.
                'author' => $userId === null
                    ? __('System')
                    : ($names[$userId] ?? __('Reviewer #:id', ['id' => $userId])),
            ];
        }, $entries);
    }

    /**
     * Estados a los que ESTA persona puede llevar ESTA solicitud.
     *
     * @return array<int, string>
     */
    private function permittedTransitions(StudentRequest $studentRequest): array
    {
        $user = Auth::user();

        if ($user === null) {
            return [];
        }

        // La cuenta de administración no ocupa ningún escritorio del trámite.
        if ($user->can('overrideWorkflow', $studentRequest)) {
            return $studentRequest->anyOtherStatus();
        }

        if (! $user->can($this->abilityFor($studentRequest), $studentRequest)) {
            return [];
        }

        return $studentRequest->allowedNextStatuses();
    }

    /** Qué permiso hace falta según EN QUÉ ESCRITORIO está. */
    private function abilityFor(StudentRequest $studentRequest): string
    {
        return in_array($studentRequest->status(), [
            StudentRequest::STATUS_PENDING,
            StudentRequest::STATUS_DECIDED,
        ], true) ? 'verify' : 'update';
    }

    /** Se resuelve por contenedor y se cachea por request. */
    /**
     * Expediente del estudiante aplanado para la vista.
     *
     * @return array{fullName: string, nationalId: string, email: string|null, isActive: bool, degreeProgram: string|null, studyPlan: string|null, currentLevel: int|null, currentTerm: string|null, average: float|null, approved: int, failed: int, accredited: int, earnedCredits: int, planCredits: int|null, courses: array<int, array{code: string, name: string, planLevel: int|null, term: string|null, status: string, grade: string, credits: int|null}>}|null
     */
    private function studentFileRows(int $studentId): ?array
    {
        $file = app(FindStudentFileUseCase::class)->handle($studentId);

        if ($file === null) {
            return null;
        }

        $courses = array_map(fn (StudentFileCourse $course): array => [
            'code' => $course->code(),
            'name' => $course->name(),
            'planLevel' => $course->planLevel(),
            'term' => $course->termLabel(),
            'status' => $course->status(),
            // La nota se formatea acá y no en el Blade.
            'grade' => $course->hasGrade() ? number_format((float) $course->grade(), 2) : '—',
            'credits' => $course->credits(),
        ], $file->courses());

        return [
            'fullName' => $file->fullName(),
            'nationalId' => $file->nationalId(),
            'email' => $file->email(),
            'isActive' => $file->isActive(),
            'degreeProgram' => $file->degreeProgram(),
            'studyPlan' => $file->studyPlan(),
            'currentLevel' => $file->currentLevel(),
            'currentTerm' => $file->currentTermLabel(),
            'average' => $file->gradeAverage(),
            'approved' => $file->countWithStatus('Aprobado'),
            'failed' => $file->countWithStatus('Reprobado'),
            'inProgress' => $file->inProgressCount(),
            // Acreditadas = el resto: equiparación, convalidación y requisito
            // levantado. Se restan también las matriculadas, o una materia en
            // curso se leería como ganada por otra vía.
            'accredited' => count($courses)
                - $file->countWithStatus('Aprobado')
                - $file->countWithStatus('Reprobado')
                - $file->inProgressCount(),
            'earnedCredits' => $file->earnedCredits(),
            'planCredits' => $file->planCredits(),
            'courses' => $courses,
        ];
    }

    /**
     * Cursos pedidos.
     *
     * @return array<int, array{id: int, courseLabel: string, externalName: string, externalCode: string|null, externalCredits: int|null, institution: string, outcome: string, denialReason: string|null, resolved: bool, recognized: bool}>
     */
    private function requestedCourseRows(int $studentRequestId): array
    {
        $courses = app(RequestedCourseRepositoryInterface::class)->forRequest($studentRequestId);

        if ($courses === []) {
            return [];
        }

        // findManyByIds() y no all().
        $byId = [];
        foreach (app(ListCoursesUseCase::class)->findManyByIds(
            array_map(fn ($course): int => $course->courseId(), $courses)
        ) as $course) {
            $byId[$course->id()] = $course;
        }

        $rows = [];

        foreach ($courses as $course) {
            $id = (int) $course->id();
            $utn = $byId[$course->courseId()] ?? null;

            $this->courseEdits[$id] ??= [
                'code' => (string) $course->externalCourseCode(),
                'credits' => $course->externalCredits() === null ? '' : (string) $course->externalCredits(),
                'grade' => $course->externalGrade() === null ? '' : (string) $course->externalGrade(),
                'reason' => (string) $course->denialReason(),
            ];

            $rows[] = [
                'id' => $id,
                'courseLabel' => $utn ? "{$utn->code()} — {$utn->name()}" : "#{$course->courseId()}",
                'externalName' => $course->externalCourseName(),
                'externalCode' => $course->externalCourseCode(),
                'externalCredits' => $course->externalCredits(),
                'externalGrade' => $course->externalGrade(),
                'institution' => $course->externalInstitution(),
                'outcome' => $course->outcome(),
                'denialReason' => $course->denialReason(),
                'resolved' => $course->isResolved(),
                'recognized' => $course->isRecognized(),
            ];
        }

        return $rows;
    }

    private function badge(): StudentRequestStatusBadge
    {
        return $this->statusBadge ??= app(StudentRequestStatusBadge::class);
    }

    /**
     * El precedente que respalda una convalidación, con sus documentos.
     *
     * @return array{institution: string, externalCourse: string, outcome: string, resolutionNumber: string, documents: array<int, array{id: int|null, documentType: string, originalName: string, sizeLabel: string, previewUrl: string|null}>}|null
     */
    private function precedentDetail(StudentRequest $studentRequest, ListAttachmentsForUseCase $listAttachments): ?array
    {
        $precedentId = $studentRequest->transferCreditPrecedentId();

        if ($precedentId === null) {
            return null;
        }

        try {
            $precedent = app(FindTransferCreditPrecedentUseCase::class)->handle($precedentId);
        } catch (TransferCreditPrecedentNotFoundException) {
            return null;
        }

        return [
            'institution' => $precedent->institution(),
            'externalCourse' => $precedent->externalCourse(),
            'outcome' => $precedent->outcome(),
            'resolutionNumber' => $precedent->resolutionNumber(),
            'documents' => array_map(
                // Se reemplaza la URL genérica de previsualización.
                function (array $document) use ($studentRequest): array {
                    if ($document['previewUrl'] !== null && $document['id'] !== null) {
                        $document['previewUrl'] = route('studentrequests.studentrequest.precedent-document', [
                            'studentRequest' => $studentRequest->id(),
                            'attachment' => $document['id'],
                        ]);
                    }

                    return $document;
                },
                $this->attachmentRows($listAttachments, TransferCreditPrecedent::class, $precedentId),
            ),
        ];
    }

    /**
     * Cursos que se le ofrecen al ESTUDIANTE en sus formularios.
     *
     * Se quitan los que ya ganó y los que está cursando: en ambos casos el
     * trámite no tendría objeto, y ofrecerlos solo invita a una solicitud que
     * Registro va a rechazar.
     *
     * @param  array<int, Course>  $courses
     * @return array<int, Course>
     */
    private function coursesAvailableToStudent(
        array $courses,
        StudentRepositoryInterface $studentRepository,
        bool $excludeBlocked = true,
    ): array {
        $student = Auth::user()?->student;
        if ($student === null) {
            return [];
        }

        $degreeProgramIds = $studentRepository->enrolledDegreeProgramIds($student->id);

        // Si el expediente todavía no tiene ningún plan asociado.
        $filtered = [];
        foreach ($courses as $id => $course) {
            $belongsToProgram = $degreeProgramIds === []
                || $course->degreeProgramId() === null
                || in_array($course->degreeProgramId(), $degreeProgramIds, true);

            if ($belongsToProgram) {
                $filtered[$id] = $course;
            }
        }

        if ($excludeBlocked) {
            foreach ($studentRepository->blockedCourseIds($student->id) as $blockedId) {
                unset($filtered[$blockedId]);
            }
        }

        return $filtered;
    }

    /**
     * Solo los cursos que TIENEN algo que levantar.
     *
     * El selector general (convalidaciones) ofrece todo lo que el estudiante
     * puede llevar. Este es más estrecho: para un levantamiento no basta con
     * que el curso esté disponible, tiene que quedarle al menos un requisito
     * sin ganar. Sin este filtro el estudiante elegía un curso y se topaba con
     * el segundo selector vacío, sin forma de saber por qué.
     *
     * Un curso SIN requisitos registrados también queda fuera: no se puede
     * levantar lo que la malla no exige.
     *
     * @param  array<int, Course>  $courses
     * @return array<int, Course>
     */
    private function coursesWithPendingRequirements(
        array $courses,
        StudentRepositoryInterface $studentRepository,
        ListCoursePrerequisitesUseCase $prerequisites,
    ): array {
        $available = $this->coursesAvailableToStudent($courses, $studentRepository);

        $student = Auth::user()?->student;
        if ($student === null || $available === []) {
            return [];
        }

        $blocked = $studentRepository->blockedCourseIds($student->id);
        // Una consulta para todo el catálogo, no una por curso.
        $requiredBy = $prerequisites->requiredForMany(array_keys($available));

        $withPending = [];
        foreach ($available as $id => $course) {
            foreach ($requiredBy[$id] ?? [] as $requiredId) {
                // Mismo criterio que el segundo selector: si no lo va a
                // mostrar allá, el curso no debe ofrecerse acá.
                if (isset($courses[$requiredId]) && ! in_array($requiredId, $blocked, true)) {
                    $withPending[$id] = $course;

                    break;
                }
            }
        }

        return $withPending;
    }

    /**
     * Requisitos REALES del curso elegido, según la malla.
     *
     * Antes el segundo selector mostraba el catálogo entero, así que se podía
     * declarar como requisito un curso que no lo es. Ahora sale de la tabla de
     * requisitos, y se quitan los que el estudiante ya ganó: si lo aprobó, no
     * hay nada que levantar.
     *
     * Sin curso elegido devuelve vacío, no todo el catálogo: la lista es una
     * consecuencia de la elección anterior y mostrarla antes sería ofrecer
     * opciones que van a cambiar.
     *
     * @param  array<int, Course>  $courses
     * @return array<int, Course>
     */
    private function requirementsForSelectedCourse(
        array $courses,
        StudentRepositoryInterface $studentRepository,
        ListCoursePrerequisitesUseCase $prerequisites,
    ): array {
        $student = Auth::user()?->student;
        $courseId = $this->waiverForm->courseId;

        if ($student === null || $courseId === null) {
            return [];
        }

        $blocked = $studentRepository->blockedCourseIds($student->id);
        $options = [];

        foreach ($prerequisites->requiredFor((int) $courseId) as $requiredId) {
            if (isset($courses[$requiredId]) && ! in_array($requiredId, $blocked, true)) {
                $options[$requiredId] = $courses[$requiredId];
            }
        }

        return $options;
    }

    /**
     * @return array<int, Course>
     */
    private function courses(ListCoursesUseCase $useCase): array
    {
        if ($this->courseCatalog === null) {
            $this->courseCatalog = [];
            foreach ($useCase->all() as $course) {
                // Course::id() is ?int at the Domain level.
                $id = $course->id();
                if ($id !== null) {
                    $this->courseCatalog[$id] = $course;
                }
            }
        }

        return $this->courseCatalog;
    }
}
