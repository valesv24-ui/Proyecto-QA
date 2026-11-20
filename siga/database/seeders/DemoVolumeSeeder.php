<?php

declare(strict_types=1);

namespace Database\Seeders;

use Carbon\CarbonInterface;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Src\StudentRequests\WaiverRule\Domain\Entities\WaiverRule as WaiverRuleEntity;

/** Datos de VOLUMEN para probar el módulo con la interfaz. */
class DemoVolumeSeeder extends Seeder
{
    use WithoutModelEvents;

    private const STUDENTS = 120;

    private const REQUESTS = 500;

    private const WAIVER_RULES = 60;

    private const PRECEDENTS = 80;

    private const TYPE_WAIVER = 'Levantamiento de requisito';

    private const TYPE_TRANSFER = 'Convalidación';

    private const STATUS_PENDING = 'Pendiente de revisión';

    private const STATUS_VERIFIED = 'Verificada por Registro';

    private const STATUS_IN_REVIEW = 'En revisión';

    private const STATUS_DECIDED = 'Resuelta por Docencia';

    private const STATUS_APPROVED = 'Aprobada';

    private const STATUS_DENIED = 'Denegada';

    /** @var array<int, string> */
    private const FIRST_NAMES = [
        'María', 'José', 'Ana', 'Carlos', 'Laura', 'Diego', 'Sofía', 'Andrés',
        'Valeria', 'Mauricio', 'Gabriela', 'Esteban', 'Daniela', 'Rodrigo',
        'Natalia', 'Fabián', 'Carolina', 'Alejandro', 'Marcela', 'Kevin',
        'Jimena', 'Bryan', 'Priscilla', 'Randall', 'Yendry', 'Josué',
    ];

    /** @var array<int, string> */
    private const LAST_NAMES = [
        'Rodríguez', 'Jiménez', 'Vargas', 'Solís', 'Mora', 'Chaves', 'Araya',
        'Quesada', 'Montero', 'Ramírez', 'Alfaro', 'Céspedes', 'Villalobos',
        'Brenes', 'Sánchez', 'Cordero', 'Fernández', 'Rojas', 'Ureña',
        'Calderón', 'Zamora', 'Picado', 'Barrantes', 'Segura', 'Elizondo',
    ];

    /** @var array<int, string> */
    private const INSTITUTIONS = [
        'Universidad de Costa Rica',
        'Instituto Tecnológico de Costa Rica',
        'Universidad Nacional',
        'Universidad Estatal a Distancia',
        'Universidad Latina de Costa Rica',
        'Universidad Fidélitas',
        'Universidad Hispanoamericana',
        'Colegio Universitario de Cartago',
    ];

    /** @var array<int, string> */
    private const EXTERNAL_COURSES = [
        'Programación I', 'Programación II', 'Estructuras de Datos',
        'Bases de Datos I', 'Bases de Datos II', 'Matemática Discreta',
        'Cálculo Diferencial', 'Cálculo Integral', 'Estadística General',
        'Redes de Computadoras', 'Sistemas Operativos', 'Ingeniería de Software',
        'Arquitectura de Computadoras', 'Inglés Técnico I', 'Inglés Técnico II',
        'Álgebra Lineal', 'Física General', 'Investigación de Operaciones',
    ];

    public function run(): void
    {
        // Semilla fija: dos corridas producen los mismos datos.
        mt_srand(20260811);

        $courses = DB::table('courses')->orderBy('id')->pluck('id')->all();
        $studyPlans = DB::table('study_plans')->orderBy('id')->pluck('id')->all();
        $terms = DB::table('academic_terms')->orderBy('id')->pluck('id')->all();

        if (count($courses) < 10 || $studyPlans === []) {
            $this->command->error(
                'DemoVolumeSeeder necesita el catálogo académico cargado. '
                .'Corra primero: php artisan migrate:fresh --seed'
            );

            return;
        }

        DB::transaction(function () use ($courses, $studyPlans, $terms): void {
            $studentIds = $this->seedStudents($studyPlans);
            $this->seedAcademicRecords($studentIds, $courses, $terms);
            $this->seedWaiverRules($courses);
            $precedents = $this->seedPrecedents($courses);
            $this->seedRequests($studentIds, $courses, $precedents);
        });

        $this->command->info('DemoVolumeSeeder: datos de volumen cargados.');

        // Las solicitudes se insertan con DB::table.
        $this->call(TransferRequestCourseBackfillSeeder::class);
    }

    /**
     * @param  array<int, int>  $studyPlans
     * @return array<int, int>
     */
    private function seedStudents(array $studyPlans): array
    {
        $now = now();
        $rows = [];

        for ($i = 0; $i < self::STUDENTS; $i++) {
            $rows[] = [
                // Sin user_id: son expedientes de prueba para llenar la bandeja.
                'user_id' => null,
                // Prefijo 9 para que ninguna cédula choque.
                'national_id' => '9'.str_pad((string) (100000 + $i), 8, '0', STR_PAD_LEFT),
                'first_name' => self::FIRST_NAMES[$i % count(self::FIRST_NAMES)],
                'last_name' => self::LAST_NAMES[$i % count(self::LAST_NAMES)],
                'second_last_name' => self::LAST_NAMES[($i + 7) % count(self::LAST_NAMES)],
                'is_active' => mt_rand(1, 100) > 8,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('students')->insert($rows);

        $ids = DB::table('students')
            ->where('national_id', 'like', '9%')
            ->orderBy('id')
            ->pluck('id')
            ->all();

        // Matrícula: cada estudiante en un plan.
        $enrollments = [];
        foreach ($ids as $i => $studentId) {
            $enrollments[] = [
                'student_id' => $studentId,
                'study_plan_id' => $studyPlans[$i % count($studyPlans)],
                'current_level' => mt_rand(1, 10),
                'created_at' => $now,
            ];
        }
        DB::table('student_study_plan')->insert($enrollments);

        return $ids;
    }

    /**
     * Expediente académico: da sustancia al motor de reglas.
     *
     * @param  array<int, int>  $studentIds
     * @param  array<int, int>  $courses
     * @param  array<int, int>  $terms
     */
    private function seedAcademicRecords(array $studentIds, array $courses, array $terms): void
    {
        $now = now();
        $statuses = ['Aprobado', 'Aprobado', 'Aprobado', 'Reprobado', 'Acreditado por convalidación'];
        $rows = [];

        foreach ($studentIds as $studentId) {
            // Entre 5 y 12 cursos por estudiante, sin repetir curso.
            $taken = (array) array_rand(array_flip($courses), min(mt_rand(5, 12), count($courses)));

            foreach ($taken as $courseId) {
                $status = $statuses[array_rand($statuses)];

                $rows[] = [
                    'student_id' => $studentId,
                    'course_id' => $courseId,
                    'academic_term_id' => $terms === [] ? null : $terms[array_rand($terms)],
                    'status' => $status,
                    // Reprobado lleva nota bajo 70; las acreditaciones no llevan nota.
                    'grade' => match ($status) {
                        'Aprobado' => mt_rand(70, 100),
                        'Reprobado' => mt_rand(30, 69),
                        default => null,
                    },
                    'course_equivalence_id' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('academic_records')->insert($chunk);
        }
    }

    /**
     * Reglas sobre cursos reales, cubriendo los cuatro tipos del SRS.
     *
     * @param  array<int, int>  $courses
     */
    private function seedWaiverRules(array $courses): void
    {
        $now = now();
        $rows = [];

        // Cursos que ya tienen regla.
        $used = [];
        foreach (DB::table('waiver_rules')->get(['course_id', 'evaluation_order']) as $existing) {
            $used[$existing->course_id.':'.$existing->evaluation_order] = true;
        }

        $targets = (array) array_rand(array_flip($courses), min(self::WAIVER_RULES, count($courses)));

        foreach ($targets as $courseId) {
            $order = 1;
            while (isset($used[$courseId.':'.$order])) {
                $order++;
            }
            $used[$courseId.':'.$order] = true;

            // Boleta SLR-002: una regla ya no lleva parámetros.
            $types = WaiverRuleEntity::TYPES;
            $type = $types[array_rand($types)];

            $rows[] = [
                'course_id' => $courseId,
                'evaluation_order' => $order,
                'type' => $type,
                'is_active' => mt_rand(1, 100) > 15,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('waiver_rules')->insert($rows);
    }

    /**
     * @param  array<int, int>  $courses
     * @return array<int, array{id: int, institution: string, external_course: string, outcome: string}>
     */
    private function seedPrecedents(array $courses): array
    {
        $now = now();
        $rows = [];
        $seen = [];

        for ($i = 0; $i < self::PRECEDENTS; $i++) {
            $institution = self::INSTITUTIONS[$i % count(self::INSTITUTIONS)];
            $external = self::EXTERNAL_COURSES[($i * 3) % count(self::EXTERNAL_COURSES)];

            // La combinación institución + curso externo se consulta.
            $key = $institution.'|'.$external;
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;

            $rows[] = [
                'institution' => $institution,
                'external_course' => $external,
                'course_id' => $courses[array_rand($courses)],
                'outcome' => mt_rand(1, 100) > 30 ? 'Aprobada' : 'Denegada',
                'resolution_number' => sprintf('CTC-%d-%03d', 2024 + ($i % 3), $i + 1),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('transfer_credit_precedents')->insert($rows);

        return DB::table('transfer_credit_precedents')
            ->where('resolution_number', 'like', 'CTC-%')
            ->get(['id', 'institution', 'external_course', 'outcome'])
            // Construido explícitamente, en vez de (array) $p.
            ->map(fn ($p) => [
                'id' => (int) $p->id,
                'institution' => (string) $p->institution,
                'external_course' => (string) $p->external_course,
                'outcome' => (string) $p->outcome,
            ])
            ->all();
    }

    /**
     * El grueso del volumen: solicitudes con estados.
     *
     * @param  array<int, int>  $studentIds
     * @param  array<int, int>  $courses
     * @param  array<int, array<string, mixed>>  $precedents
     */
    private function seedRequests(array $studentIds, array $courses, array $precedents): void
    {
        $rows = [];

        // Sin precedentes no se puede armar una Convalidación válida.
        $canCreateTransfers = $precedents !== [];

        for ($i = 0; $i < self::REQUESTS; $i++) {
            $isWaiver = ! $canCreateTransfers || mt_rand(1, 100) <= 60;
            $courseId = $courses[array_rand($courses)];

            // Repartidas en el último cuatrimestre para que la búsqueda.
            $createdAt = now()->subDays(mt_rand(0, 110))->setTime(mt_rand(7, 19), mt_rand(0, 59));

            [$status, $engineResult] = $this->outcomeFor($isWaiver);

            $prerequisite = null;
            if ($isWaiver) {
                // CHECK: un Levantamiento exige requisito.
                $prerequisite = $courseId;
                while ($prerequisite === $courseId) {
                    $prerequisite = $courses[array_rand($courses)];
                }
            }

            $precedent = $isWaiver ? null : $precedents[array_rand($precedents)];

            $rows[] = [
                'student_id' => $studentIds[array_rand($studentIds)],
                'type' => $isWaiver ? self::TYPE_WAIVER : self::TYPE_TRANSFER,
                'course_id' => $courseId,
                'prerequisite_course_id' => $prerequisite,
                // Boleta SLR-002.
                'justification' => $isWaiver
                    ? WaiverRuleEntity::STUDENT_JUSTIFICATIONS[array_rand(WaiverRuleEntity::STUDENT_JUSTIFICATIONS)]
                    : null,
                'terms_accepted_at' => $isWaiver ? $createdAt : null,
                // CHECK: una Convalidación exige institución Y curso externo.
                'source_institution' => $isWaiver ? null : $precedent['institution'],
                'external_course' => $isWaiver ? null : $precedent['external_course'],
                // Solo se enlaza el precedente cuando está aprobado.
                'transfer_credit_precedent_id' => $precedent !== null && $precedent['outcome'] === 'Aprobada'
                    ? $precedent['id']
                    : null,
                'engine_result' => $engineResult,
                'failed_rule_id' => null,
                'status' => $status,
                // Lo que resolvió Docencia, guardado aparte del estado.
                'teaching_decision' => $this->decisionFor($status),
                // 5, no 7: debe coincidir con EnsureEstimatedResolutionDateUseCase::BUSINESS_DAYS_TO_ASSIGN (ES-03).
                'estimated_resolution_date' => $this->addBusinessDays($createdAt->copy(), 5)->format('Y-m-d'),
                'reviewer_id' => null,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ];
        }

        foreach (array_chunk($rows, 250) as $chunk) {
            DB::table('student_requests')->insert($chunk);
        }

        $this->seedHistory();
    }

    /**
     * Estado y resultado del motor coherentes entre sí.
     *
     * @return array{0: string, 1: string|null}
     */
    private function outcomeFor(bool $isWaiver): array
    {
        // ES-02: una Convalidación NUNCA la resuelve el motor.
        if (! $isWaiver) {
            // Los cinco estados del circuito real.
            $statuses = [
                self::STATUS_PENDING,
                self::STATUS_VERIFIED,
                self::STATUS_IN_REVIEW,
                self::STATUS_DECIDED,
                self::STATUS_APPROVED,
                self::STATUS_DENIED,
            ];

            return [$statuses[array_rand($statuses)], null];
        }

        // ES-01 con la boleta SLR-002.
        $roll = mt_rand(1, 100);

        if ($roll <= 25) {
            return [self::STATUS_DENIED, 'No procede'];
        }
        if ($roll <= 35) {
            return [self::STATUS_PENDING, 'Requiere revisión manual'];
        }
        if ($roll <= 45) {
            // Registro ya le dio el visto bueno y espera.
            return [self::STATUS_VERIFIED, 'Requiere revisión manual'];
        }
        if ($roll <= 55) {
            return [self::STATUS_IN_REVIEW, 'Requiere revisión manual'];
        }
        if ($roll <= 70) {
            // Docencia ya resolvió y la solicitud espera que Registro emita.
            return [self::STATUS_DECIDED, 'Requiere revisión manual'];
        }

        return [self::STATUS_APPROVED, 'Requiere revisión manual'];
    }

    /** La decisión de Docencia que corresponde a cada estado. */
    private function decisionFor(string $status): ?string
    {
        if ($status === self::STATUS_DECIDED) {
            return mt_rand(1, 100) <= 70 ? self::STATUS_APPROVED : self::STATUS_DENIED;
        }

        return in_array($status, [self::STATUS_APPROVED, self::STATUS_DENIED], true) ? $status : null;
    }

    /** Historial de cada solicitud. */
    private function seedHistory(): void
    {
        $rows = [];

        // Solo las solicitudes que todavía NO tienen historial.
        $requests = DB::table('student_requests')
            ->whereNotExists(function ($query): void {
                $query->select(DB::raw(1))
                    ->from('student_request_status_history')
                    ->whereColumn('student_request_status_history.student_request_id', 'student_requests.id');
            })
            ->get(['id', 'status', 'created_at']);

        foreach ($requests as $request) {
            $rows[] = [
                'student_request_id' => $request->id,
                'previous_status' => null,
                'new_status' => self::STATUS_PENDING,
                'comment' => 'Solicitud recibida por el portal.',
                'user_id' => null,
                'notified_at' => null,
                'created_at' => $request->created_at,
            ];

            if ($request->status !== self::STATUS_PENDING) {
                $rows[] = [
                    'student_request_id' => $request->id,
                    'previous_status' => self::STATUS_PENDING,
                    'new_status' => $request->status,
                    'comment' => match ($request->status) {
                        self::STATUS_APPROVED => 'Cumple los criterios configurados.',
                        self::STATUS_DENIED => 'No cumple los criterios configurados.',
                        default => 'Enviada a revisión por Docencia.',
                    },
                    'user_id' => null,
                    'notified_at' => null,
                    'created_at' => $request->created_at,
                ];
            }
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('student_request_status_history')->insert($chunk);
        }
    }

    /** Misma regla de días hábiles que BusinessDayCalculator del Dominio. */
    private function addBusinessDays(CarbonInterface $from, int $days): CarbonInterface
    {
        $date = $from;
        $added = 0;

        while ($added < $days) {
            // Reasignar el resultado en vez de mutar en el sitio.
            $date = $date->addDay();
            if (! in_array((int) $date->format('N'), [6, 7], true)) {
                $added++;
            }
        }

        return $date;
    }
}
