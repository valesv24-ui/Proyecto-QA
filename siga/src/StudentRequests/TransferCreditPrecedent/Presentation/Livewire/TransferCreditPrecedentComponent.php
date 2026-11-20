<?php

declare(strict_types=1);

namespace Src\StudentRequests\TransferCreditPrecedent\Presentation\Livewire;

use App\Livewire\Concerns\FiltersCoursesByDegreeProgram;
use App\Livewire\Concerns\InteractsWithDataTable;
use App\Livewire\Concerns\InteractsWithExports;
use App\Livewire\Concerns\ManagesAttachments;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;
use Src\AcademicCatalog\Course\Application\UseCases\ListCoursesUseCase;
use Src\AcademicCatalog\Course\Domain\Entities\Course;
use Src\AcademicCatalog\DegreeProgram\Application\UseCases\ListDegreeProgramsUseCase;
use Src\Shared\Attachment\Application\UseCases\CreateAttachmentUseCase;
use Src\Shared\Attachment\Application\UseCases\FindAttachmentUseCase;
use Src\Shared\Attachment\Application\UseCases\ListAttachmentsForUseCase;
use Src\Shared\Attachment\Presentation\Support\UploadLimit;
use Src\Shared\Export\Contracts\ExcelExporterInterface;
use Src\Shared\Export\Contracts\PdfExporterInterface;
use Src\StudentRequests\TransferCreditPrecedent\Application\UseCases\CreateTransferCreditPrecedentUseCase;
use Src\StudentRequests\TransferCreditPrecedent\Application\UseCases\DeleteTransferCreditPrecedentUseCase;
use Src\StudentRequests\TransferCreditPrecedent\Application\UseCases\FindTransferCreditPrecedentUseCase;
use Src\StudentRequests\TransferCreditPrecedent\Application\UseCases\ListTransferCreditPrecedentsUseCase;
use Src\StudentRequests\TransferCreditPrecedent\Application\UseCases\UpdateTransferCreditPrecedentUseCase;
use Src\StudentRequests\TransferCreditPrecedent\Domain\Entities\TransferCreditPrecedent;
use Src\StudentRequests\TransferCreditPrecedent\Presentation\Livewire\Forms\TransferCreditPrecedentForm;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TransferCreditPrecedentComponent extends Component
{
    use AuthorizesRequests;
    use FiltersCoursesByDegreeProgram;
    use InteractsWithDataTable;
    use InteractsWithExports;
    use ManagesAttachments;
    use WithFileUploads;

    /** Catálogo pequeño: la tabla pagina en cliente. */
    protected string $tableMode = 'client';

    public bool $showModal = false;

    public bool $showDetailModal = false;

    /**
     * @var array<string, mixed>|null
     */
    public ?array $detail = null;

    public ?int $editingId = null;

    public TransferCreditPrecedentForm $form;

    /**
     * Archivo en curso de subida dentro del modal.
     *
     * @var mixed
     */
    public $newAttachment = null;

    public string $newAttachmentType = '';

    /**
     * Adjuntos ya guardados del precedente que se está editando.
     *
     * @var array<int, array{id: int|null, documentType: string, originalName: string, sizeLabel: string, previewUrl: string|null}>
     */
    public array $attachments = [];

    /**
     * Catálogo de cursos cacheado por render.
     *
     * @var array<int, Course>|null
     */
    private ?array $courseCatalog = null;

    public function mount(): void
    {
        $this->authorize('viewAny', TransferCreditPrecedent::class);
        $this->sortKey = 'institution';
    }

    public function openCreateModal(): void
    {
        $this->authorize('create', TransferCreditPrecedent::class);

        $this->editingId = null;
        $this->formDegreeProgramId = '';
        $this->attachments = [];
        $this->newAttachment = null;
        $this->newAttachmentType = '';
        $this->form->reset();
        $this->resetValidation();
        $this->showModal = true;
    }

    /** Cambiar de carrera invalida el curso equivalente ya elegido. */
    public function updatedFormDegreeProgramId(): void
    {
        $this->form->courseId = null;
    }

    public function openEditModal(int $id, FindTransferCreditPrecedentUseCase $useCase, ListAttachmentsForUseCase $listAttachments, ListCoursesUseCase $coursesUseCase): void
    {
        // La Policy exige la instancia: buscar antes de autorizar.
        $precedent = $useCase->handle($id);
        $this->authorize('update', $precedent);

        $this->editingId = $id;

        // render() corre después de la acción: poblar acá.
        $this->courseCatalog ??= $this->indexCoursesById($coursesUseCase);

        $this->formDegreeProgramId = $this->degreeProgramIdOfCourse(
            $this->courseCatalog,
            $precedent->courseId(),
        );
        $this->attachments = $this->attachmentRows($listAttachments, TransferCreditPrecedent::class, $id);
        $this->newAttachment = null;
        $this->newAttachmentType = '';
        $this->form->fromEntity($precedent);
        $this->resetValidation();
        $this->showModal = true;
    }

    /** Los adjuntos cuelgan del precedente por su id. */
    public function uploadAttachment(
        CreateAttachmentUseCase $createAttachment,
        ListAttachmentsForUseCase $listAttachments,
        FindTransferCreditPrecedentUseCase $findUseCase,
    ): void {
        $this->authorize('update', $findUseCase->handle((int) $this->editingId));

        $this->validate([
            'newAttachment' => $this->attachmentRules(),
            'newAttachmentType' => ['required', 'string', 'max:60'],
        ]);

        $this->persistAttachment(
            $createAttachment,
            $this->newAttachment,
            TransferCreditPrecedent::class,
            (int) $this->editingId,
            $this->newAttachmentType,
            directory: 'transfer-credit-precedents',
        );

        $this->attachments = $this->attachmentRows($listAttachments, TransferCreditPrecedent::class, (int) $this->editingId);
        $this->newAttachment = null;
        $this->newAttachmentType = '';
        $this->dispatch('toast', variant: 'success', text: __('Document attached.'));
    }

    public function downloadAttachment(int $attachmentId, FindAttachmentUseCase $findAttachment, FindTransferCreditPrecedentUseCase $findUseCase): StreamedResponse
    {
        $attachment = $findAttachment->handle($attachmentId);

        // Se autoriza contra el precedente dueño del archivo.
        $this->authorize('view', $findUseCase->handle($attachment->attachableId()));

        return $this->streamAttachmentDownload($attachment);
    }

    public function closeModal(): void
    {
        $this->showModal = false;
    }

    /** Detalle de solo lectura, incluidos los documentos adjuntos. */
    public function openDetailModal(
        int $id,
        FindTransferCreditPrecedentUseCase $useCase,
        ListCoursesUseCase $coursesUseCase,
        ListAttachmentsForUseCase $listAttachments,
    ): void {
        $precedent = $useCase->handle($id);
        $this->authorize('view', $precedent);

        $this->courseCatalog ??= $this->indexCoursesById($coursesUseCase);
        $course = $this->courseCatalog[$precedent->courseId()] ?? null;

        $this->detail = [
            'id' => $precedent->id(),
            'canUpdate' => Auth::user()?->can('update', $precedent) ?? false,
            'institution' => $precedent->institution(),
            'externalCourse' => $precedent->externalCourse(),
            'courseLabel' => $course ? "{$course->code()} — {$course->name()}" : "#{$precedent->courseId()}",
            'outcome' => $precedent->outcome(),
            'resolutionNumber' => $precedent->resolutionNumber(),
            'attachments' => $this->attachmentRows($listAttachments, TransferCreditPrecedent::class, $id),
        ];
        $this->showDetailModal = true;
    }

    public function closeDetailModal(): void
    {
        $this->showDetailModal = false;
        $this->detail = null;
    }

    public function editFromDetail(
        FindTransferCreditPrecedentUseCase $useCase,
        ListAttachmentsForUseCase $listAttachments,
        ListCoursesUseCase $coursesUseCase,
    ): void {
        $id = (int) ($this->detail['id'] ?? 0);
        $this->closeDetailModal();
        $this->openEditModal($id, $useCase, $listAttachments, $coursesUseCase);
    }

    public function save(CreateTransferCreditPrecedentUseCase $createUseCase, UpdateTransferCreditPrecedentUseCase $updateUseCase, ListTransferCreditPrecedentsUseCase $listUseCase, FindTransferCreditPrecedentUseCase $findUseCase, ListCoursesUseCase $coursesUseCase): void
    {
        $this->form->validate();

        if ($this->editingId === null) {
            $this->authorize('create', TransferCreditPrecedent::class);
            $createUseCase->handle($this->form->toDto());
        } else {
            $this->authorize('update', $findUseCase->handle($this->editingId));
            $updateUseCase->handle($this->editingId, $this->form->toDto());
        }

        // El catálogo se puebla ANTES de armar las filas.
        $this->courseCatalog ??= $this->indexCoursesById($coursesUseCase);

        $this->showModal = false;
        $this->refreshTable($this->freshRows($listUseCase));
        $this->dispatch('toast', variant: 'success', text: $this->editingId === null
            ? __('Transfer credit precedent created.')
            : __('Transfer credit precedent updated.'));
    }

    public function delete(int $id, DeleteTransferCreditPrecedentUseCase $useCase, ListTransferCreditPrecedentsUseCase $listUseCase, FindTransferCreditPrecedentUseCase $findUseCase, ListCoursesUseCase $coursesUseCase): void
    {
        $this->authorize('delete', $findUseCase->handle($id));

        $useCase->handle($id);

        // El catálogo se puebla ANTES de armar las filas.
        $this->courseCatalog ??= $this->indexCoursesById($coursesUseCase);

        $this->refreshTable($this->freshRows($listUseCase));
        $this->dispatch('toast', variant: 'success', text: __('Transfer credit precedent deleted.'));
    }

    public function exportPdf(PdfExporterInterface $exporter, ListTransferCreditPrecedentsUseCase $useCase, ListCoursesUseCase $coursesUseCase, ?string $search = null): StreamedResponse
    {
        $this->authorize('exportPdf', TransferCreditPrecedent::class);

        // Livewire no garantiza que render() ya haya corrido en este.
        $this->courseCatalog ??= $this->indexCoursesById($coursesUseCase);

        return $this->streamPdf(
            __('Transfer credit precedents'),
            $this->exportHeaders(),
            $this->exportableRows($useCase, $search),
            Str::slug(__('Transfer credit precedents')).'.pdf',
            $exporter,
            paperSize: 'letter',
        );
    }

    public function exportExcel(ExcelExporterInterface $exporter, ListTransferCreditPrecedentsUseCase $useCase, ListCoursesUseCase $coursesUseCase, ?string $search = null): StreamedResponse
    {
        $this->authorize('exportExcel', TransferCreditPrecedent::class);

        $this->courseCatalog ??= $this->indexCoursesById($coursesUseCase);

        return $this->streamExcel(
            $this->exportHeaders(),
            $this->exportableRows($useCase, $search),
            Str::slug(__('Transfer credit precedents')).'.xlsx',
            $exporter,
        );
    }

    public function render(
        ListTransferCreditPrecedentsUseCase $useCase,
        ListCoursesUseCase $coursesUseCase,
        ListDegreeProgramsUseCase $degreeProgramsUseCase,
    ): View {
        $this->courseCatalog = $this->indexCoursesById($coursesUseCase);

        $view = $this->isServerMode()
            ? $this->renderServerMode($useCase)
            : $this->renderClientMode($useCase);

        // Igual que en WaiverRuleComponent.
        $view = $view->with([
            'courseOptions' => $this->coursesForSelectedDegreeProgram($this->courseCatalog),
            'degreeProgramOptions' => $degreeProgramsUseCase->all(),
        ]);

        /** @disregard P1013 Livewire registra ->layout() como macro en runtime */
        return $view->layout('components.layouts.dashboard', [
            'title' => __('Transfer Credit Precedents'),
            'subtitle' => __('Historical record of external course transfer resolutions used by the Technical Commission'),
        ]);
    }

    private function renderClientMode(ListTransferCreditPrecedentsUseCase $useCase): View
    {
        return view('studentrequests.transfercreditprecedent.livewire.transfer-credit-precedent-component', [
            // Mismo criterio que la bandeja de solicitudes.
            'uploadLimit' => UploadLimit::label(),
            'tableMode' => 'client',
            'rows' => $this->freshRows($useCase),
        ]);
    }

    private function renderServerMode(ListTransferCreditPrecedentsUseCase $useCase): View
    {
        $result = $useCase->paginate(
            search: $this->authorizedSearch(),
            perPage: $this->perPage,
            page: $this->page,
            sortBy: $this->sortKey,
            sortDir: $this->sortDir,
        );

        $paginator = new LengthAwarePaginator(
            items: $result['items'],
            total: $result['total'],
            perPage: $this->perPage,
            currentPage: $this->page,
        );

        return view('studentrequests.transfercreditprecedent.livewire.transfer-credit-precedent-component', [
            // También acá, y no solo en modo cliente.
            'uploadLimit' => UploadLimit::label(),
            'tableMode' => 'server',
            'precedents' => $paginator,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function toRow(TransferCreditPrecedent $precedent): array
    {
        $course = $this->courseCatalog[$precedent->courseId()] ?? null;

        return [
            'id' => $precedent->id(),
            'institution' => $precedent->institution(),
            'externalCourse' => $precedent->externalCourse(),
            'courseLabel' => $course ? "{$course->code()} — {$course->name()}" : "#{$precedent->courseId()}",
            'outcome' => $precedent->outcome(),
            'resolutionNumber' => $precedent->resolutionNumber(),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function freshRows(ListTransferCreditPrecedentsUseCase $useCase): array
    {
        return array_map($this->toRow(...), $useCase->all(sortBy: $this->sortKey, sortDir: $this->sortDir));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function exportableRows(ListTransferCreditPrecedentsUseCase $useCase, ?string $search): array
    {
        return array_map(
            $this->toRow(...),
            $useCase->all(search: $this->authorizedSearch($search), sortBy: $this->sortKey, sortDir: $this->sortDir),
        );
    }

    /** Enforces TransferCreditPrecedentPolicy. */
    private function authorizedSearch(?string $explicit = null): ?string
    {
        if (! Auth::user()->can('search', TransferCreditPrecedent::class)) {
            return null;
        }

        $candidate = filled($explicit) ? $explicit : $this->search;

        return $candidate !== '' ? $candidate : null;
    }

    /**
     * @return array<int, array{key: string, label: string, format?: callable}>
     */
    private function exportHeaders(): array
    {
        return [
            ['key' => 'institution', 'label' => __('Institution')],
            ['key' => 'externalCourse', 'label' => __('External course')],
            ['key' => 'courseLabel', 'label' => __('Equivalent course')],
            ['key' => 'outcome', 'label' => __('Outcome')],
            ['key' => 'resolutionNumber', 'label' => __('Resolution number')],
        ];
    }
}
