<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/** Cuenta de estudiante para verificaciones centrales del módulo. */
class CentralVerificationStudentSeeder extends Seeder
{
    private const EMAIL = 'ortiz3558jo@gmail.com';

    private const PASSWORD = 'Moon1995';

    /** Cédula fuera de los rangos que ya usan los otros. */
    private const NATIONAL_ID = '118650427';

    private const STUDY_PLAN = 'Ingeniería del Software 2025';

    /** Nivel en curso. */
    private const CURRENT_LEVEL = 5;

    /**
     * Expediente: los cuatro primeros niveles de la malla de ISW.
     *
     * @var array<string, int>
     */
    private const APPROVED_COURSES = [
        // Nivel 1.
        'ISW-121' => 92,
        'ISW-122' => 78,
        'ISW-123' => 85,
        'ISW-124' => 90,
        // Nivel 2.
        'ISW-221' => 95,
        'ISW-222' => 71,
        'ISW-223' => 83,
        // Nivel 3.
        'ISW-321' => 88,
        'ISW-322' => 75,
        'ISW-323' => 69,
        // Nivel 4.
        'ISW-421' => 85,
        'ISW-422' => 91,
        'ISW-423' => 68,
    ];

    public function run(): void
    {
        $user = User::query()->firstWhere('email', self::EMAIL);

        if ($user === null) {
            $user = User::factory()->create([
                'name' => 'Jose Ortiz',
                'email' => self::EMAIL,
                'password' => Hash::make(self::PASSWORD),
            ]);
        } else {
            // Al re-sembrar se restablece la contraseña conocida.
            $user->forceFill(['password' => Hash::make(self::PASSWORD)])->save();
        }

        // Rol nominal SIN permisos (ver el docblock de la clase).
        $user->roles()->sync(
            Role::query()->where('name', 'Estudiante')->pluck('id')
        );

        $studentId = $this->ensureStudentRecord($user->id);

        $studyPlanId = DB::table('study_plans')->where('name', self::STUDY_PLAN)->value('id');

        if ($studyPlanId === null) {
            // Sin el plan real no hay carrera que deducir.
            $this->command->warn(
                'No se encontró el plan "'.self::STUDY_PLAN.'". '
                .'Corré AcademicCurriculaSeeder antes que este seeder.'
            );

            return;
        }

        $this->ensureEnrollment($studentId, (int) $studyPlanId);
        $this->ensureAcademicRecords($studentId, (int) $studyPlanId);
    }

    private function ensureStudentRecord(int $userId): int
    {
        $existing = DB::table('students')->where('user_id', $userId)->value('id');

        if ($existing !== null) {
            return (int) $existing;
        }

        return (int) DB::table('students')->insertGetId([
            'user_id' => $userId,
            'national_id' => self::NATIONAL_ID,
            'first_name' => 'Jose',
            'last_name' => 'Ortiz',
            'second_last_name' => null,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function ensureEnrollment(int $studentId, int $studyPlanId): void
    {
        // updateOrInsert y no insert.
        DB::table('student_study_plan')->updateOrInsert(
            ['student_id' => $studentId, 'study_plan_id' => $studyPlanId],
            ['current_level' => self::CURRENT_LEVEL, 'created_at' => now()],
        );
    }

    private function ensureAcademicRecords(int $studentId, int $studyPlanId): void
    {
        $courseIds = DB::table('courses')
            ->whereIn('code', array_keys(self::APPROVED_COURSES))
            ->pluck('id', 'code');

        $termByLevel = $this->termIdByLevel();
        $levelByCourse = $this->levelNumberByCourse($studyPlanId);

        $rows = [];

        foreach (self::APPROVED_COURSES as $code => $grade) {
            $courseId = $courseIds[$code] ?? null;

            if ($courseId === null) {
                continue;
            }

            $alreadyRecorded = DB::table('academic_records')
                ->where('student_id', $studentId)
                ->where('course_id', $courseId)
                ->exists();

            if ($alreadyRecorded) {
                continue;
            }

            $levelNumber = $levelByCourse[(int) $courseId] ?? null;

            $rows[] = [
                'student_id' => $studentId,
                'course_id' => $courseId,
                // En qué cuatrimestre CALENDARIO se cursó.
                'academic_term_id' => $levelNumber === null ? null : ($termByLevel[$levelNumber] ?? null),
                'status' => 'Aprobado',
                'grade' => $grade,
                'course_equivalence_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if ($rows !== []) {
            DB::table('academic_records')->insert($rows);
        }
    }

    /**
     * Nivel del plan al que pertenece cada curso.
     *
     * @return array<int, int> course_id => número de nivel
     */
    private function levelNumberByCourse(int $studyPlanId): array
    {
        return DB::table('course_level')
            ->join('levels', 'levels.id', '=', 'course_level.level_id')
            ->where('levels.study_plan_id', $studyPlanId)
            ->pluck('levels.number', 'course_level.course_id')
            ->map(fn ($number): int => (int) $number)
            ->all();
    }

    /**
     * Cuatrimestre calendario en que este estudiante cursó cada nivel.
     *
     * @return array<int, int> número de nivel => academic_term_id
     */
    private function termIdByLevel(): array
    {
        $terms = DB::table('academic_terms')
            ->orderBy('year')
            ->orderBy('term_number')
            ->pluck('id')
            ->all();

        $today = now()->toDateString();

        $currentId = DB::table('academic_terms')
            ->where('start_date', '<=', $today)
            ->where('end_date', '>=', $today)
            ->value('id');

        // Fuera de clases (receso entre cuatrimestres) no hay uno vigente.
        $found = $currentId !== null
            ? array_search((int) $currentId, $terms, true)
            : count($terms) - 1;

        if ($found === false || $terms === []) {
            return [];
        }

        // array_search() devuelve int|string en el tipo declarado.
        $currentIndex = (int) $found;

        $byLevel = [];

        foreach (range(1, self::CURRENT_LEVEL - 1) as $level) {
            $offset = $currentIndex - (self::CURRENT_LEVEL - $level);

            if ($offset >= 0) {
                $byLevel[$level] = (int) $terms[$offset];
            }
        }

        return $byLevel;
    }
}
