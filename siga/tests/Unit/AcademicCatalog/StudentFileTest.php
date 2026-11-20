<?php

declare(strict_types=1);

namespace Tests\Unit\AcademicCatalog;

use PHPUnit\Framework\TestCase;
use Src\AcademicCatalog\StudentFile\Domain\Entities\StudentFile;
use Src\AcademicCatalog\StudentFile\Domain\Entities\StudentFileCourse;

/** El expediente que Registro y Docencia ven al abrir. */
final class StudentFileTest extends TestCase
{
    public function test_the_average_ignores_courses_without_a_grade(): void
    {
        $file = $this->fileWith([
            $this->course('ISW-121', 'Aprobado', 90.0),
            $this->course('ISW-122', 'Aprobado', 80.0),
            // Convalidada: sin nota.
            $this->course('MAT-101', 'Acreditado por convalidación', null),
        ]);

        self::assertSame(85.0, $file->gradeAverage());
    }

    public function test_an_empty_record_has_no_average_instead_of_zero(): void
    {
        // Cero es una nota; "sin expediente".
        self::assertNull($this->fileWith([])->gradeAverage());
        self::assertTrue($this->fileWith([])->isEmpty());
    }

    public function test_a_record_made_only_of_accreditations_has_no_average(): void
    {
        $file = $this->fileWith([
            $this->course('MAT-101', 'Acreditado por convalidación', null),
            $this->course('ING-101', 'Acreditado por equiparación', null),
        ]);

        self::assertNull($file->gradeAverage());
        self::assertFalse($file->isEmpty());
    }

    public function test_it_counts_each_status_separately(): void
    {
        $file = $this->fileWith([
            $this->course('ISW-121', 'Aprobado', 90.0),
            $this->course('ISW-122', 'Aprobado', 71.0),
            $this->course('ISW-123', 'Reprobado', 55.0),
            $this->course('MAT-101', 'Requisito levantado', null),
        ]);

        // Que las reprobadas se cuenten aparte es media razón.
        self::assertSame(2, $file->countWithStatus('Aprobado'));
        self::assertSame(1, $file->countWithStatus('Reprobado'));
        self::assertSame(1, $file->countWithStatus('Requisito levantado'));
        self::assertSame(0, $file->countWithStatus('Acreditado por convalidación'));
    }

    public function test_a_failed_course_still_counts_toward_the_average(): void
    {
        // Tiene nota, así que promedia.
        $file = $this->fileWith([
            $this->course('ISW-121', 'Aprobado', 90.0),
            $this->course('ISW-123', 'Reprobado', 50.0),
        ]);

        self::assertSame(70.0, $file->gradeAverage());
    }

    public function test_the_two_terms_of_a_course_are_independent(): void
    {
        // El nivel del plan y el cuatrimestre cursado son datos.
        $course = new StudentFileCourse('ISW-421', 'Bases de Datos', 4, '2026-I', 'Aprobado', 85.0, 4);

        self::assertSame(4, $course->planLevel());
        self::assertSame('2026-I', $course->termLabel());

        // Un curso de servicio no pertenece al plan del estudiante.
        $withoutContext = new StudentFileCourse('ING-101', 'Inglés I', null, null, 'Aprobado', 78.0, null);

        self::assertNull($withoutContext->planLevel());
        self::assertNull($withoutContext->termLabel());
        self::assertTrue($withoutContext->hasGrade());
    }

    /**
     * @param  array<int, StudentFileCourse>  $courses
     */
    private function fileWith(array $courses): StudentFile
    {
        return new StudentFile(
            studentId: 1,
            fullName: 'Jose Ortiz',
            nationalId: '118650427',
            degreeProgram: 'Ingeniería del Software',
            studyPlan: 'Ingeniería del Software 2025',
            currentLevel: 5,
            currentTermLabel: '2026-II',
            courses: $courses,
        );
    }

    private function course(string $code, string $status, ?float $grade): StudentFileCourse
    {
        return new StudentFileCourse($code, "Curso {$code}", 1, '2025-I', $status, $grade, 4);
    }
}
