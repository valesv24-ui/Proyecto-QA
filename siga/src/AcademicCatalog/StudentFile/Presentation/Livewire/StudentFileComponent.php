<?php

declare(strict_types=1);

namespace Src\AcademicCatalog\StudentFile\Presentation\Livewire;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Src\AcademicCatalog\StudentFile\Application\UseCases\FindStudentFileUseCase;
use Src\AcademicCatalog\StudentFile\Domain\Entities\StudentFileCourse;

/**
 * Expediente académico del propio estudiante autenticado.
 *
 * Vive en AcademicCatalog\StudentFile y no en StudentRequests: es el mismo
 * expediente que Docencia y Registro ya ven al revisar una solicitud
 * (StudentRequestComponent::studentFileRows()), pero mostrarlo no tiene
 * relación con presentar o resolver una solicitud, así que no debía
 * quedar como una pestaña más de esa pantalla — vive como su propia
 * página, junto a las demás secciones académicas del sidebar (Aulas,
 * Docentes, Oferta académica).
 */
final class StudentFileComponent extends Component
{
    public function isStudent(): bool
    {
        return Auth::user()?->student !== null;
    }

    public function render(FindStudentFileUseCase $findStudentFile): View
    {
        /** @disregard P1013 Livewire registra ->layout() como macro en runtime */
        return view('academiccatalog.studentfile.livewire.student-file-component', [
            'file' => $this->isStudent() ? $this->fileRows($findStudentFile) : null,
        ])->layout('components.layouts.dashboard', [
            'title' => __('Academic record'),
            'subtitle' => __('Your courses, credits, and academic standing.'),
        ]);
    }

    /**
     * Expediente aplanado para la vista. Mismo aplanado que
     * StudentRequestComponent::studentFileRows(), duplicado a propósito: es
     * lógica de presentación de este contexto (AcademicCatalog), no algo
     * que StudentRequests debería exponer para que otro módulo lo reuse.
     *
     * @return array{fullName: string, nationalId: string, email: string|null, isActive: bool, degreeProgram: string|null, studyPlan: string|null, currentLevel: int|null, currentTerm: string|null, average: float|null, approved: int, failed: int, accredited: int, inProgress: int, earnedCredits: int, planCredits: int|null, courses: array<int, array{code: string, name: string, planLevel: int|null, term: string|null, status: string, grade: string, credits: int|null}>}|null
     */
    private function fileRows(FindStudentFileUseCase $findStudentFile): ?array
    {
        $file = $findStudentFile->handle(Auth::user()->student->id);

        if ($file === null) {
            return null;
        }

        $courses = array_map(fn (StudentFileCourse $course): array => [
            'code' => $course->code(),
            'name' => $course->name(),
            'planLevel' => $course->planLevel(),
            'term' => $course->termLabel(),
            'status' => $course->status(),
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
            'accredited' => count($courses)
                - $file->countWithStatus('Aprobado')
                - $file->countWithStatus('Reprobado')
                - $file->inProgressCount(),
            'earnedCredits' => $file->earnedCredits(),
            'planCredits' => $file->planCredits(),
            'courses' => $courses,
        ];
    }
}
