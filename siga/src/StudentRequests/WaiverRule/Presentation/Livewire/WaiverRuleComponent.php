<?php

declare(strict_types=1);

namespace Src\StudentRequests\WaiverRule\Presentation\Livewire;

use App\Livewire\Concerns\FiltersCoursesByDegreeProgram;
use App\Livewire\Concerns\InteractsWithDataTable;
use App\Livewire\Concerns\InteractsWithExports;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Component;
use Src\AcademicCatalog\Course\Application\UseCases\ListCoursesUseCase;
use Src\AcademicCatalog\Course\Domain\Entities\Course;
use Src\AcademicCatalog\DegreeProgram\Application\UseCases\ListDegreeProgramsUseCase;
use Src\Shared\Export\Contracts\ExcelExporterInterface;
use Src\Shared\Export\Contracts\PdfExporterInterface;
use Src\StudentRequests\WaiverRule\Application\UseCases\CreateWaiverRuleUseCase;
use Src\StudentRequests\WaiverRule\Application\UseCases\DeleteWaiverRuleUseCase;
use Src\StudentRequests\WaiverRule\Application\UseCases\FindWaiverRuleUseCase;
use Src\StudentRequests\WaiverRule\Application\UseCases\ListWaiverRulesUseCase;
use Src\StudentRequests\WaiverRule\Application\UseCases\UpdateWaiverRuleUseCase;
use Src\StudentRequests\WaiverRule\Domain\Entities\WaiverRule;
use Src\StudentRequests\WaiverRule\Presentation\Livewire\Forms\WaiverRuleForm;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WaiverRuleComponent extends Component
{
    use AuthorizesRequests;
    use FiltersCoursesByDegreeProgram;
    use InteractsWithDataTable;
    use InteractsWithExports;

    /** Catálogo pequeño: la tabla pagina en cliente. */
    protected string $tableMode = 'client';

    public bool $showModal = false;

    public bool $showDetailModal = false;

    /**
     * Proyección plana de la regla que se está consultando.
     *
     * @var array<string, mixed>|null
     */
    public ?array $detail = null;

    public ?int $editingId = null;

    public WaiverRuleForm $form;

    /**
     * Catálogo de cursos cacheado por render.
     *
     * @var array<int, Course>|null
     */
    private ?array $courseCatalog = null;

    public function mount(): void
    {
        $this->authorize('viewAny', WaiverRule::class);
        $this->sortKey = 'evaluation_order';
    }

    public function openCreateModal(): void
    {
        $this->authorize('create', WaiverRule::class);

        $this->editingId = null;
        $this->formDegreeProgramId = '';
        $this->form->reset();
        $this->resetValidation();
        $this->showModal = true;
    }

    /** Cambiar de carrera invalida cualquier curso ya elegido. */
    public function updatedFormDegreeProgramId(): void
    {
        $this->form->courseId = null;
    }

    public function openEditModal(int $id, FindWaiverRuleUseCase $useCase, ListCoursesUseCase $coursesUseCase): void
    {
        // La Policy exige la instancia: buscar antes de autorizar.
        $waiverRule = $useCase->handle($id);
        $this->authorize('update', $waiverRule);

        $this->editingId = $id;

        // render() corre después de la acción: poblar acá.
        $this->courseCatalog ??= $this->indexCoursesById($coursesUseCase);

        $this->formDegreeProgramId = $this->degreeProgramIdOfCourse(
            $this->courseCatalog,
            $waiverRule->courseId(),
        );
        $this->form->fromEntity($waiverRule);
        $this->resetValidation();
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
    }

    /** Detalle de solo lectura. */
    public function openDetailModal(int $id, FindWaiverRuleUseCase $useCase, ListCoursesUseCase $coursesUseCase): void
    {
        $waiverRule = $useCase->handle($id);

        // La Policy exige la instancia, no la clase.
        $this->authorize('view', $waiverRule);

        // render() corre después de la acción: poblar acá.
        $this->courseCatalog ??= $this->indexCoursesById($coursesUseCase);

        $course = $this->courseCatalog[$waiverRule->courseId()] ?? null;

        $this->detail = [
            'id' => $waiverRule->id(),
            'canUpdate' => Auth::user()?->can('update', $waiverRule) ?? false,
            'courseLabel' => $course ? "{$course->code()} — {$course->name()}" : "#{$waiverRule->courseId()}",
            'evaluationOrder' => $waiverRule->evaluationOrder(),
            'type' => $waiverRule->type(),
            // Booleano crudo: el modal de detalle lo evalúa con @if.
            'isActive' => $waiverRule->isActive(),
        ];
        $this->showDetailModal = true;
    }

    public function closeDetailModal(): void
    {
        $this->showDetailModal = false;
        $this->detail = null;
    }

    /** Puente del detalle al formulario. */
    public function editFromDetail(FindWaiverRuleUseCase $useCase, ListCoursesUseCase $coursesUseCase): void
    {
        $id = (int) ($this->detail['id'] ?? 0);
        $this->closeDetailModal();
        $this->openEditModal($id, $useCase, $coursesUseCase);
    }

    public function save(CreateWaiverRuleUseCase $createUseCase, UpdateWaiverRuleUseCase $updateUseCase, ListWaiverRulesUseCase $listUseCase, FindWaiverRuleUseCase $findUseCase, ListCoursesUseCase $coursesUseCase): void
    {
        $this->form->validate();

        if ($this->editingId === null) {
            $this->authorize('create', WaiverRule::class);
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
            ? __('Waiver rule created.')
            : __('Waiver rule updated.'));
    }

    public function delete(int $id, DeleteWaiverRuleUseCase $useCase, ListWaiverRulesUseCase $listUseCase, FindWaiverRuleUseCase $findUseCase, ListCoursesUseCase $coursesUseCase): void
    {
        $this->authorize('delete', $findUseCase->handle($id));

        $useCase->handle($id);

        // El catálogo se puebla ANTES de armar las filas.
        $this->courseCatalog ??= $this->indexCoursesById($coursesUseCase);

        $this->refreshTable($this->freshRows($listUseCase));
        $this->dispatch('toast', variant: 'success', text: __('Waiver rule deleted.'));
    }

    public function exportPdf(PdfExporterInterface $exporter, ListWaiverRulesUseCase $useCase, ListCoursesUseCase $coursesUseCase, ?string $search = null): StreamedResponse
    {
        $this->authorize('exportPdf', WaiverRule::class);

        // Livewire no garantiza que render() ya haya corrido en este.
        $this->courseCatalog ??= $this->indexCoursesById($coursesUseCase);

        return $this->streamPdf(
            __('Waiver rules'),
            $this->exportHeaders(),
            $this->exportableRows($useCase, $search),
            Str::slug(__('Waiver rules')).'.pdf',
            $exporter,
            paperSize: 'letter',
        );
    }

    public function exportExcel(ExcelExporterInterface $exporter, ListWaiverRulesUseCase $useCase, ListCoursesUseCase $coursesUseCase, ?string $search = null): StreamedResponse
    {
        $this->authorize('exportExcel', WaiverRule::class);

        $this->courseCatalog ??= $this->indexCoursesById($coursesUseCase);

        return $this->streamExcel(
            $this->exportHeaders(),
            $this->exportableRows($useCase, $search),
            Str::slug(__('Waiver rules')).'.xlsx',
            $exporter,
        );
    }

    public function render(
        ListWaiverRulesUseCase $useCase,
        ListCoursesUseCase $coursesUseCase,
        ListDegreeProgramsUseCase $degreeProgramsUseCase,
    ): View {
        $this->courseCatalog = $this->indexCoursesById($coursesUseCase);

        $view = $this->isServerMode()
            ? $this->renderServerMode($useCase)
            : $this->renderClientMode($useCase);

        // El catálogo completo ($this->courseCatalog) se sigue usando para resolver.
        $view = $view->with([
            'courseOptions' => $this->coursesForSelectedDegreeProgram($this->courseCatalog),
            'degreeProgramOptions' => $degreeProgramsUseCase->all(),
        ]);

        /** @disregard P1013 Livewire registra ->layout() como macro en runtime */
        return $view->layout('components.layouts.dashboard', [
            'title' => __('Waiver Rules'),
            'subtitle' => __('Course-by-course requirement waiver criteria configured by Docencia'),
        ]);
    }

    private function renderClientMode(ListWaiverRulesUseCase $useCase): View
    {
        return view('studentrequests.waiverrule.livewire.waiver-rule-component', [
            'tableMode' => 'client',
            'rows' => $this->freshRows($useCase),
        ]);
    }

    private function renderServerMode(ListWaiverRulesUseCase $useCase): View
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

        return view('studentrequests.waiverrule.livewire.waiver-rule-component', [
            'tableMode' => 'server',
            'waiverRules' => $paginator,
        ]);
    }

    /**
     * Resumen legible del parámetro de la regla.
     *
     * @return array<string, mixed>
     */
    private function toRow(WaiverRule $waiverRule): array
    {
        $course = $this->courseCatalog[$waiverRule->courseId()] ?? null;

        return [
            'id' => $waiverRule->id(),
            'courseLabel' => $course ? "{$course->code()} — {$course->name()}" : "#{$waiverRule->courseId()}",
            'evaluationOrder' => $waiverRule->evaluationOrder(),
            'type' => $waiverRule->type(),
            // El booleano crudo lo consume Alpine (x-show) y lleva sufijo.
            'isActiveRaw' => $waiverRule->isActive(),
            'isActive' => $waiverRule->isActive() ? __('Active') : __('Inactive'),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function freshRows(ListWaiverRulesUseCase $useCase): array
    {
        return array_map($this->toRow(...), $useCase->all(sortBy: $this->sortKey, sortDir: $this->sortDir));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function exportableRows(ListWaiverRulesUseCase $useCase, ?string $search): array
    {
        return array_map(
            $this->toRow(...),
            $useCase->all(search: $this->authorizedSearch($search), sortBy: $this->sortKey, sortDir: $this->sortDir),
        );
    }

    /** Enforces WaiverRulePolicy. */
    private function authorizedSearch(?string $explicit = null): ?string
    {
        if (! Auth::user()->can('search', WaiverRule::class)) {
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
            ['key' => 'courseLabel', 'label' => __('Course')],
            ['key' => 'evaluationOrder', 'label' => __('Order')],
            ['key' => 'type', 'label' => __('Type')],
            ['key' => 'isActive', 'label' => __('Active'), 'format' => fn (bool $active): string => $active ? __('Yes') : __('No')],
        ];
    }
}
