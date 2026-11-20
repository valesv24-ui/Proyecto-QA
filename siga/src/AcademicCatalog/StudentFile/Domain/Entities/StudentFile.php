<?php

declare(strict_types=1);

namespace Src\AcademicCatalog\StudentFile\Domain\Entities;

/** Expediente del estudiante tal como lo necesita quien revisa. */
final class StudentFile
{
    /**
     * @param  array<int, StudentFileCourse>  $courses
     */
    public function __construct(
        private readonly int $studentId,
        private readonly string $fullName,
        private readonly string $nationalId,
        private readonly ?string $degreeProgram,
        private readonly ?string $studyPlan,
        private readonly ?int $currentLevel,
        private readonly ?string $currentTermLabel,
        private readonly array $courses,
        private readonly bool $isActive = true,
        private readonly ?string $email = null,
        private readonly ?int $planCredits = null,
    ) {}

    public function studentId(): int
    {
        return $this->studentId;
    }

    public function fullName(): string
    {
        return $this->fullName;
    }

    /** Cédula. */
    public function nationalId(): string
    {
        return $this->nationalId;
    }

    public function degreeProgram(): ?string
    {
        return $this->degreeProgram;
    }

    public function studyPlan(): ?string
    {
        return $this->studyPlan;
    }

    /** Cuatrimestre de la CARRERA en que va. */
    public function currentLevel(): ?int
    {
        return $this->currentLevel;
    }

    /** Cuatrimestre del CALENDARIO en curso hoy (2026-II). */
    public function currentTermLabel(): ?string
    {
        return $this->currentTermLabel;
    }

    /**
     * @return array<int, StudentFileCourse>
     */
    public function courses(): array
    {
        return $this->courses;
    }

    /** ¿La matrícula sigue activa? Un expediente de alguien inactivo. */
    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function email(): ?string
    {
        return $this->email;
    }

    /** Créditos ganados: ni reprobados ni en curso. */
    public function earnedCredits(): int
    {
        $earned = 0;

        foreach ($this->courses as $course) {
            // Matriculado no suma: el crédito se gana al aprobar, y contarlo
            // antes inflaría el avance con materias que aún se pueden perder.
            if ($course->isEarned()) {
                $earned += $course->credits() ?? 0;
            }
        }

        return $earned;
    }

    /** Cuántas materias se están cursando ahora mismo. */
    public function inProgressCount(): int
    {
        return count(array_filter(
            $this->courses,
            static fn (StudentFileCourse $course): bool => $course->isInProgress(),
        ));
    }

    /** Créditos totales del plan. */
    public function planCredits(): ?int
    {
        return $this->planCredits;
    }

    public function isEmpty(): bool
    {
        return $this->courses === [];
    }

    public function countWithStatus(string $status): int
    {
        return count(array_filter(
            $this->courses,
            fn (StudentFileCourse $course): bool => $course->status() === $status,
        ));
    }

    /** Promedio de las materias CON nota. */
    public function gradeAverage(): ?float
    {
        $grades = array_map(
            fn (StudentFileCourse $course): float => (float) $course->grade(),
            array_filter($this->courses, fn (StudentFileCourse $course): bool => $course->hasGrade()),
        );

        if ($grades === []) {
            return null;
        }

        return round(array_sum($grades) / count($grades), 2);
    }
}
