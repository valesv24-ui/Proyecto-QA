<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use Src\AcademicCatalog\Course\Application\UseCases\ListCoursesUseCase;
use Src\AcademicCatalog\Course\Domain\Entities\Course;

/** Catálogo de cursos y filtro en cascada Carrera -> Curso. */
trait FiltersCoursesByDegreeProgram
{
    /** Carrera elegida; cadena vacía significa sin filtrar. */
    public string $formDegreeProgramId = '';

    /**
     * Cursos de la carrera elegida, más los transversales.
     *
     * @param  array<int, Course>  $courses
     * @return array<int, Course>
     */
    protected function coursesForSelectedDegreeProgram(array $courses): array
    {
        if ($this->formDegreeProgramId === '') {
            return $courses;
        }

        $programId = (int) $this->formDegreeProgramId;

        return array_filter(
            $courses,
            static fn (Course $course): bool => $course->degreeProgramId() === $programId
                || $course->degreeProgramId() === null,
        );
    }

    /**
     * Carrera de un curso guardado, para posicionar el selector.
     *
     * @param  array<int, Course>  $courses
     */
    protected function degreeProgramIdOfCourse(array $courses, ?int $courseId): string
    {
        if ($courseId === null) {
            return '';
        }

        $programId = ($courses[$courseId] ?? null)?->degreeProgramId();

        return $programId !== null ? (string) $programId : '';
    }

    /**
     * Catálogo de cursos indexado por id.
     *
     * @return array<int, Course>
     */
    protected function indexCoursesById(ListCoursesUseCase $useCase): array
    {
        $indexed = [];

        foreach ($useCase->all() as $course) {
            // id() es ?int hasta persistirse; acá siempre viene.
            $id = $course->id();

            if ($id !== null) {
                $indexed[$id] = $course;
            }
        }

        return $indexed;
    }
}
