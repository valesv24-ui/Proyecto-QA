<?php

declare(strict_types=1);

namespace Src\StudentRequests\StudentRequest\Application\UseCases;

use DateTimeImmutable;
use Src\AcademicCatalog\AcademicRecord\Domain\Contracts\AcademicRecordRepositoryInterface;
use Src\AcademicCatalog\AcademicRecord\Domain\Entities\AcademicRecord;
use Src\AcademicCatalog\AcademicTerm\Domain\Contracts\AcademicTermRepositoryInterface;
use Src\StudentRequests\StudentRequest\Domain\Contracts\RequestedCourseRepositoryInterface;

/** Los cursos reconocidos de una convalidación pasan al expediente. */
final class CreditRecognizedCoursesUseCase
{
    /** El estado del ENUM de academic_records que corresponde a esta. */
    private const STATUS_TRANSFERRED = 'Acreditado por convalidación';

    public function __construct(
        private readonly RequestedCourseRepositoryInterface $requestedCourses,
        private readonly AcademicRecordRepositoryInterface $academicRecords,
        private readonly AcademicTermRepositoryInterface $academicTerms,
    ) {}

    /**
     * @return int Cuántos cursos quedaron acreditados en esta publicación.
     */
    public function handle(int $studentRequestId, int $studentId, ?DateTimeImmutable $publishedOn = null): int
    {
        $courses = $this->requestedCourses->forRequest($studentRequestId);

        if ($courses === []) {
            return 0;
        }

        // Una sola consulta del expediente para toda la resolución.
        $earnedCourseIds = $this->earnedCourseIds($studentId);

        // El cuatrimestre en que se PUBLICA.
        $term = $this->academicTerms->findForDate($publishedOn ?? new DateTimeImmutable);
        $termId = $term?->id();

        $credited = 0;

        foreach ($courses as $course) {
            if (! $course->isRecognized()) {
                continue;
            }

            if (in_array($course->courseId(), $earnedCourseIds, true)) {
                continue;
            }

            $this->academicRecords->save(AcademicRecord::create(
                studentId: $studentId,
                courseId: $course->courseId(),
                academicTermId: $termId,
                status: self::STATUS_TRANSFERRED,
                // La nota de la institución de ORIGEN.
                grade: $course->externalGrade(),
            ));

            // La lista local también se actualiza.
            $earnedCourseIds[] = $course->courseId();
            $credited++;
        }

        return $credited;
    }

    /**
     * Cursos que el estudiante ya tiene ganados o está cursando.
     *
     * @return array<int, int>
     */
    private function earnedCourseIds(int $studentId): array
    {
        $ids = [];

        foreach ($this->academicRecords->forStudent($studentId) as $record) {
            // blocksNewRequest y no isPassed: una materia que el estudiante
            // está cursando tampoco debe recibir una segunda fila.
            if ($record->blocksNewRequest()) {
                $ids[] = $record->courseId();
            }
        }

        return $ids;
    }
}
