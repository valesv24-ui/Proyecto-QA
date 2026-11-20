<?php

declare(strict_types=1);

namespace Src\AcademicCatalog\StudentFile\Infrastructure\Persistence\Repositories;

use Illuminate\Support\Facades\DB;
use Src\AcademicCatalog\StudentFile\Domain\Contracts\StudentFileRepositoryInterface;
use Src\AcademicCatalog\StudentFile\Domain\Entities\StudentFile;
use Src\AcademicCatalog\StudentFile\Domain\Entities\StudentFileCourse;

final class EloquentStudentFileRepository implements StudentFileRepositoryInterface
{
    /**
     * Números romanos de los cuatrimestres.
     *
     * @var array<int, string>
     */
    private const TERM_NUMERALS = [1 => 'I', 2 => 'II', 3 => 'III'];

    public function findForStudent(int $studentId): ?StudentFile
    {
        // Query Builder y NO Eloquent.
        $identity = DB::table('students')
            ->leftJoin('student_study_plan', 'student_study_plan.student_id', '=', 'students.id')
            ->leftJoin('study_plans', 'study_plans.id', '=', 'student_study_plan.study_plan_id')
            ->leftJoin('degree_programs', 'degree_programs.id', '=', 'study_plans.degree_program_id')
            // El correo vive en `users` y no en `students`.
            ->leftJoin('users', 'users.id', '=', 'students.user_id')
            ->where('students.id', $studentId)
            ->select([
                'students.id',
                'students.national_id',
                'students.first_name',
                'students.last_name',
                'students.second_last_name',
                'students.is_active',
                'users.email',
                'degree_programs.name as degree_program',
                'study_plans.name as study_plan',
                'study_plans.id as study_plan_id',
                'student_study_plan.current_level',
            ])
            // Un estudiante puede tener más de una matrícula.
            ->orderByDesc('student_study_plan.created_at')
            ->first();

        if ($identity === null) {
            return null;
        }

        $studyPlanId = $identity->study_plan_id === null ? null : (int) $identity->study_plan_id;

        return new StudentFile(
            studentId: (int) $identity->id,
            fullName: trim(implode(' ', array_filter([
                $identity->first_name,
                $identity->last_name,
                $identity->second_last_name,
            ]))),
            nationalId: (string) $identity->national_id,
            degreeProgram: $identity->degree_program === null ? null : (string) $identity->degree_program,
            studyPlan: $identity->study_plan === null ? null : (string) $identity->study_plan,
            currentLevel: $identity->current_level === null ? null : (int) $identity->current_level,
            currentTermLabel: $this->currentTermLabel(),
            courses: $this->coursesFor($studentId, $studyPlanId),
            isActive: (bool) $identity->is_active,
            email: $identity->email === null ? null : (string) $identity->email,
            planCredits: $this->planCredits($studyPlanId),
        );
    }

    /**
     * Todas las materias del expediente en UNA consulta.
     *
     * @return array<int, StudentFileCourse>
     */
    private function coursesFor(int $studentId, ?int $studyPlanId): array
    {
        // Subconsultas y no JOIN para el nivel y los créditos.
        $planLevel = DB::table('course_level')
            ->join('levels', 'levels.id', '=', 'course_level.level_id')
            ->whereColumn('course_level.course_id', 'academic_records.course_id')
            ->where('levels.study_plan_id', $studyPlanId)
            ->select('levels.number')
            ->limit(1);

        $credits = DB::table('course_level')
            ->join('levels', 'levels.id', '=', 'course_level.level_id')
            ->whereColumn('course_level.course_id', 'academic_records.course_id')
            ->where('levels.study_plan_id', $studyPlanId)
            ->select('course_level.credits')
            ->limit(1);

        $rows = DB::table('academic_records')
            ->join('courses', 'courses.id', '=', 'academic_records.course_id')
            ->leftJoin('academic_terms', 'academic_terms.id', '=', 'academic_records.academic_term_id')
            ->where('academic_records.student_id', $studentId)
            ->select([
                'courses.code',
                'courses.name',
                'academic_terms.year as term_year',
                'academic_terms.term_number',
                'academic_records.status',
                'academic_records.grade',
            ])
            ->selectSub($planLevel, 'plan_level')
            ->selectSub($credits, 'credits')
            // Orden del expediente.
            ->orderByRaw('plan_level IS NULL, plan_level')
            ->orderBy('courses.code')
            ->get();

        $courses = [];

        foreach ($rows as $row) {
            $courses[] = new StudentFileCourse(
                code: (string) $row->code,
                name: (string) $row->name,
                planLevel: $row->plan_level === null ? null : (int) $row->plan_level,
                termLabel: $this->termLabel(
                    $row->term_year === null ? null : (int) $row->term_year,
                    $row->term_number === null ? null : (int) $row->term_number,
                ),
                status: (string) $row->status,
                grade: $row->grade === null ? null : (float) $row->grade,
                credits: $row->credits === null ? null : (int) $row->credits,
            );
        }

        return $courses;
    }

    /** Créditos totales del plan. */
    private function planCredits(?int $studyPlanId): ?int
    {
        if ($studyPlanId === null) {
            return null;
        }

        return (int) DB::table('course_level')
            ->join('levels', 'levels.id', '=', 'course_level.level_id')
            ->where('levels.study_plan_id', $studyPlanId)
            ->sum('course_level.credits');
    }

    /** Cuatrimestre vigente hoy, o null durante el receso entre dos. */
    private function currentTermLabel(): ?string
    {
        $today = now()->toDateString();

        $term = DB::table('academic_terms')
            ->where('start_date', '<=', $today)
            ->where('end_date', '>=', $today)
            ->first(['year', 'term_number']);

        return $term === null
            ? null
            : $this->termLabel((int) $term->year, (int) $term->term_number);
    }

    private function termLabel(?int $year, ?int $number): ?string
    {
        if ($year === null || $number === null) {
            return null;
        }

        return $year.'-'.(self::TERM_NUMERALS[$number] ?? (string) $number);
    }
}
