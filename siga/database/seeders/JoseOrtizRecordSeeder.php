<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Expediente real de Jose Ortiz, del estudio de graduación 2-2026.
 *
 * Sirve para probar con datos verdaderos lo que los datos inventados no
 * pueden: que los selectores dejen de ofrecer materias ya ganadas o en curso,
 * y que la cascada de requisitos siga la malla real de ISW B06.
 *
 * Las notas son las del documento. AP y APR son materias ganadas —APR sin
 * nota numérica, como los cursos de idiomas y las actividades—; MAT son las
 * que está cursando en este cuatrimestre y todavía no tienen nota.
 */
class JoseOrtizRecordSeeder extends Seeder
{
    private const NATIONAL_ID = '207420728';

    private const EMAIL = 'jsortiz@est.utn.ac.cr';

    /**
     * Materias ganadas: código => nota, o null cuando el estudio dice APR.
     *
     * @var array<string, float|null>
     */
    private const EARNED = [
        'ISW-124' => 9.75,
        'ISW-123' => 8.75,
        'ISW-122' => 9.75,
        'ISW-121' => 10.0,
        'IDTEC01' => null,
        'ISW-221' => 8.75,
        'IDTEC02' => null,
        'ISW-222' => 9.0,
        'ISW-223' => 9.5,
        'ISW-323' => 8.25,
        'ISW-321' => 7.5,
        'ISW-322' => 8.5,
        'IDTEC03' => null,
        'IDTEC04' => null,
        'ISW-421' => 10.0,
        'ISW-422' => 9.5,
        'ISW-423' => 9.5,
        'AC-100' => null,
        'TCU' => null,
        'FH-100' => 9.25,
        'AD-100' => null,
        'FH-200' => 9.5,
        'FH-300' => 10.0,
        'FH-400' => 9.5,
    ];

    /**
     * Materias en curso este cuatrimestre (MAT en el estudio).
     *
     * @var array<int, string>
     */
    private const ENROLLED = [
        'ISW-523', 'ISW-521', 'ISW-522', 'ISW-524', 'IDTEC05',
    ];

    public function run(): void
    {
        $studentId = $this->student();

        $this->enrollInPlan($studentId);

        // El cuatrimestre en curso para las matriculadas; las ganadas quedan
        // sin período: el estudio de graduación no dice en cuál se llevó cada
        // una, y repartirlas por nivel sería inventar la historia.
        $currentTermId = DB::table('academic_terms')
            ->whereDate('start_date', '<=', now()->toDateString())
            ->whereDate('end_date', '>=', now()->toDateString())
            ->value('id');

        foreach (self::EARNED as $code => $grade) {
            $this->record($studentId, $code, 'Aprobado', $grade, null);
        }

        foreach (self::ENROLLED as $code) {
            $this->record($studentId, $code, 'Matriculado', null, $currentTermId);
        }
    }

    /** Crea el estudiante y su cuenta, o devuelve el que ya existe. */
    private function student(): int
    {
        $existing = DB::table('students')->where('national_id', self::NATIONAL_ID)->value('id');

        if ($existing !== null) {
            return (int) $existing;
        }

        $user = User::query()->firstOrCreate(
            ['email' => self::EMAIL],
            ['name' => 'Jose Andrés Ortiz Marín', 'password' => bcrypt('12345678')],
        );

        return (int) DB::table('students')->insertGetId([
            'user_id' => $user->id,
            'national_id' => self::NATIONAL_ID,
            'first_name' => 'Jose Andrés',
            'last_name' => 'Ortiz',
            'second_last_name' => 'Marín',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /** Lo matricula en ISW plan B06, con su nivel actual. */
    private function enrollInPlan(int $studentId): void
    {
        $planId = DB::table('study_plans')
            ->join('degree_programs', 'degree_programs.id', '=', 'study_plans.degree_program_id')
            ->where('degree_programs.name', 'Ingeniería del Software')
            ->where('study_plans.name', 'Ingeniería del Software 2025')
            ->value('study_plans.id');

        if ($planId === null) {
            return;
        }

        DB::table('student_study_plan')->updateOrInsert(
            ['student_id' => $studentId, 'study_plan_id' => $planId],
            // Nivel 5: es el de las materias que está cursando.
            ['current_level' => 5, 'created_at' => now(), 'updated_at' => now()],
        );
    }

    private function record(int $studentId, string $code, string $status, ?float $grade, ?int $termId): void
    {
        $courseId = DB::table('courses')->where('code', $code)->value('id');

        if ($courseId === null) {
            // Un código que no está en el catálogo se salta, no aborta: es
            // preferible un expediente incompleto a un sembrado a medias.
            // TCU es el caso conocido: el PDF lo menciona como requisito de
            // graduación, no como una fila de la malla.
            $this->command->warn("  Curso {$code} no está en el catálogo; se omite del expediente.");

            return;
        }

        DB::table('academic_records')->updateOrInsert(
            ['student_id' => $studentId, 'course_id' => $courseId],
            [
                'academic_term_id' => $termId,
                'status' => $status,
                'grade' => $grade,
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );
    }
}
