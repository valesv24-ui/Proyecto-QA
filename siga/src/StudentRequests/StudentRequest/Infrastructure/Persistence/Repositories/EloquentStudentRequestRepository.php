<?php

declare(strict_types=1);

namespace Src\StudentRequests\StudentRequest\Infrastructure\Persistence\Repositories;

use App\Models\StudentRequest as StudentRequestModel;
use DateTimeImmutable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Src\StudentRequests\StudentRequest\Domain\Contracts\StudentRequestRepositoryInterface;
use Src\StudentRequests\StudentRequest\Domain\Entities\StudentRequest;

final class EloquentStudentRequestRepository implements StudentRequestRepositoryInterface
{
    /**
     * Columnas reales de student_requests que se pueden pedir como orden.
     *
     * @var array<int, string>
     */
    private const SORTABLE_COLUMNS = ['created_at', 'status', 'type', 'estimated_resolution_date'];

    public function find(int $id): ?StudentRequest
    {
        $model = StudentRequestModel::query()->find($id);

        return $model ? $this->toDomain($model) : null;
    }

    public function findApprovedWaiverDuplicate(int $studentId, int $courseId, int $prerequisiteCourseId): ?StudentRequest
    {
        $model = StudentRequestModel::query()
            ->where('student_id', $studentId)
            ->where('course_id', $courseId)
            ->where('prerequisite_course_id', $prerequisiteCourseId)
            ->where('type', StudentRequest::TYPE_WAIVER)
            ->where('status', StudentRequest::STATUS_APPROVED)
            ->first();

        return $model ? $this->toDomain($model) : null;
    }

    /**
     * Solicitudes de un estudiante, más recientes primero.
     *
     * @return array<int, StudentRequest>
     */
    public function forStudent(int $studentId): array
    {
        // Sin eager loading: toDomain() solo lee ids.
        return StudentRequestModel::query()
            ->where('student_id', $studentId)
            ->orderByDesc('created_at')
            ->get()
            ->map($this->toDomain(...))
            ->all();
    }

    public function paginateForInbox(array $filters, string $sortBy, string $sortDir, int $perPage, int $page): array
    {
        // Sin eager loading: los nombres se resuelven en lote.
        $query = StudentRequestModel::query();

        // Filtros exactos que InboxSearchQueryParser separó del texto libre.
        if (filled($filters['type'] ?? null)) {
            $query->where('type', 'like', '%'.$filters['type'].'%');
        }

        if (filled($filters['status'] ?? null)) {
            $query->where('status', 'like', '%'.$filters['status'].'%');
        }

        // Lo que falta resolver: todos los estados abiertos.
        if ($filters['openOnly'] ?? false) {
            $query->whereIn('status', StudentRequest::OPEN_STATUSES);
        }

        // El complemento exacto de openOnly: lo ya cerrado.
        if ($filters['resolvedOnly'] ?? false) {
            $query->whereIn('status', StudentRequest::RESOLVED_STATUSES);
        }

        // Solo lo que le toca a este escritorio.
        if (filled($filters['deskStatuses'] ?? null)) {
            $query->whereIn('status', $filters['deskStatuses']);
        }

        if (filled($filters['career'] ?? null)) {
            // La carrera sale del curso, vía su carrera.
            $career = $filters['career'];
            $query->whereHas('course.degreeProgram', function ($q) use ($career): void {
                $q->where('name', 'like', '%'.$career.'%');
            });
        }

        // Rango de recepción como DATETIME, para usar el índice.
        if (filled($filters['dateFrom'] ?? null)) {
            $query->where('created_at', '>=', $filters['dateFrom'].' 00:00:00');
        }

        if (filled($filters['dateTo'] ?? null)) {
            // Semiabierto: incluye el día completo de la fecha.
            $dateTo = (new DateTimeImmutable($filters['dateTo']))->modify('+1 day');
            $query->where('created_at', '<', $dateTo->format('Y-m-d').' 00:00:00');
        }

        // Busca en todas las columnas visibles de la bandeja.
        if (filled($filters['search'] ?? null)) {
            $raw = trim((string) $filters['search']);
            $term = '%'.$raw.'%';

            // Fecha completa se compara exacta; parcial, con LIKE.
            $isExactDate = (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw);

            $query->where(function ($outer) use ($term, $raw, $isExactDate): void {
                $outer
                    ->where('type', 'like', $term)
                    ->orWhere('status', 'like', $term)
                    ->orWhere('engine_result', 'like', $term)
                    ->orWhere('source_institution', 'like', $term)
                    ->orWhere('external_course', 'like', $term)
                    ->orWhereHas('student', function ($q) use ($term): void {
                        $q->where('first_name', 'like', $term)
                            ->orWhere('last_name', 'like', $term)
                            ->orWhere('second_last_name', 'like', $term)
                            ->orWhere('national_id', 'like', $term)
                            // Nombre completo concatenado, como se ve en pantalla.
                            ->orWhereRaw("CONCAT_WS(' ', first_name, last_name, second_last_name) LIKE ?", [$term]);
                    })
                    ->orWhereHas('course', function ($q) use ($term): void {
                        $q->where('code', 'like', $term)
                            ->orWhere('name', 'like', $term);
                    });

                // La fecha se compara ya formateada como en pantalla.
                if ($isExactDate) {
                    $outer->orWhereRaw("DATE_FORMAT(student_requests.created_at, '%Y-%m-%d') = ?", [$raw]);
                } else {
                    $outer->orWhereRaw("DATE_FORMAT(student_requests.created_at, '%Y-%m-%d') LIKE ?", [$term]);
                }
            });
        }

        $direction = $sortDir === 'asc' ? 'asc' : 'desc';

        // Estudiante y Curso se ordenan con subconsulta, no JOIN.
        if ($sortBy === 'studentName') {
            // El mismo nombre completo que muestra la tabla.
            $query->orderByRaw(
                "(SELECT CONCAT_WS(' ', first_name, last_name, second_last_name) FROM students WHERE students.id = student_requests.student_id) {$direction}"
            );
        } elseif ($sortBy === 'courseLabel') {
            // Se ordena por el código, que encabeza la etiqueta.
            $query->orderByRaw(
                "(SELECT code FROM courses WHERE courses.id = student_requests.course_id) {$direction}"
            );
        } else {
            $column = in_array($sortBy, self::SORTABLE_COLUMNS, true) ? $sortBy : 'created_at';
            $query->orderBy($column, $direction);
        }

        $paginator = $query->paginate(perPage: $perPage, page: $page);

        return [
            'items' => array_map($this->toDomain(...), $paginator->items()),
            'total' => $paginator->total(),
        ];
    }

    public function idsWithoutEstimatedResolutionDate(): array
    {
        return StudentRequestModel::query()
            ->whereNull('estimated_resolution_date')
            ->orderBy('id')
            ->pluck('id')
            ->all();
    }

    public function inboxSummary(string $today, string $dueSoonLimit): array
    {
        // Un solo GROUP BY para todo el resumen.
        $rows = DB::table('student_requests')
            ->selectRaw('type, status, COUNT(*) as total')
            ->selectRaw(
                'SUM(CASE WHEN estimated_resolution_date IS NOT NULL AND estimated_resolution_date < ? THEN 1 ELSE 0 END) as overdue',
                [$today],
            )
            ->selectRaw(
                'SUM(CASE WHEN estimated_resolution_date IS NOT NULL AND estimated_resolution_date >= ? AND estimated_resolution_date <= ? THEN 1 ELSE 0 END) as due_soon',
                [$today, $dueSoonLimit],
            )
            ->groupBy('type', 'status')
            ->get();

        return $rows->map(static fn ($row): array => [
            'type' => (string) $row->type,
            'status' => (string) $row->status,
            'total' => (int) $row->total,
            'overdue' => (int) $row->overdue,
            'dueSoon' => (int) $row->due_soon,
        ])->all();
    }

    public function openPastDue(string $today, int $limit, ?array $deskStatuses = null): array
    {
        return StudentRequestModel::query()
            ->whereIn('status', StudentRequest::OPEN_STATUSES)
            // Sin esto, el escritorio de Docencia vería vencidas que en realidad
            // están del lado de Registro (y viceversa).
            ->when($deskStatuses !== null, fn ($query) => $query->whereIn('status', $deskStatuses))
            ->whereNotNull('estimated_resolution_date')
            // Comparación directa, NO whereDate(): estimated_resolution_date ya es de tipo DATE.
            ->where('estimated_resolution_date', '<=', $today)
            // Las más atrasadas primero.
            ->orderBy('estimated_resolution_date')
            ->limit($limit)
            ->get()
            ->map($this->toDomain(...))
            ->all();
    }

    public function save(StudentRequest $studentRequest): StudentRequest
    {
        $model = $studentRequest->id()
            ? StudentRequestModel::query()->findOrFail($studentRequest->id())
            : new StudentRequestModel;

        $model->student_id = $studentRequest->studentId();
        $model->type = $studentRequest->type();
        $model->course_id = $studentRequest->courseId();
        $model->prerequisite_course_id = $studentRequest->prerequisiteCourseId();
        $model->justification = $studentRequest->justification();
        $model->terms_accepted_at = $studentRequest->termsAcceptedAt() !== null
            ? Carbon::instance($studentRequest->termsAcceptedAt())
            : null;
        $model->source_institution = $studentRequest->sourceInstitution();
        $model->external_course = $studentRequest->externalCourse();
        $model->transfer_credit_precedent_id = $studentRequest->transferCreditPrecedentId();
        $model->engine_result = $studentRequest->engineResult();
        $model->failed_rule_id = $studentRequest->failedRuleId();
        $model->status = $studentRequest->status();
        // Same DateTimeImmutable (Domain) -> Carbon (Eloquent cast) bridge as EloquentAcademicTermRepository.
        $model->estimated_resolution_date = $studentRequest->estimatedResolutionDate() !== null
            ? Carbon::instance($studentRequest->estimatedResolutionDate())
            : null;
        $model->reviewer_id = $studentRequest->reviewerId();
        // Lo que decidió Docencia, guardado aparte del estado.
        $model->teaching_decision = $studentRequest->teachingDecision();
        $model->save();

        return $this->toDomain($model);
    }

    public function delete(int $id): void
    {
        StudentRequestModel::query()->whereKey($id)->delete();
    }

    private function toDomain(StudentRequestModel $model): StudentRequest
    {
        return StudentRequest::reconstitute(
            id: $model->id,
            studentId: $model->student_id,
            type: $model->type,
            courseId: $model->course_id,
            prerequisiteCourseId: $model->prerequisite_course_id,
            sourceInstitution: $model->source_institution,
            externalCourse: $model->external_course,
            transferCreditPrecedentId: $model->transfer_credit_precedent_id,
            engineResult: $model->engine_result,
            failedRuleId: $model->failed_rule_id,
            status: $model->status,
            estimatedResolutionDate: $model->estimated_resolution_date
                ? new DateTimeImmutable($model->estimated_resolution_date->toDateString())
                : null,
            reviewerId: $model->reviewer_id,
            createdAt: $model->created_at ? DateTimeImmutable::createFromInterface($model->created_at) : null,
            justification: $model->justification,
            termsAcceptedAt: $model->terms_accepted_at
                ? DateTimeImmutable::createFromInterface($model->terms_accepted_at)
                : null,
            teachingDecision: $model->teaching_decision,
        );
    }
}
