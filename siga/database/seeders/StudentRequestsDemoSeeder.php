<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/** Datos mínimos de referencia para probar el módulo de Solicitudes. */
class StudentRequestsDemoSeeder extends Seeder
{
    use WithoutModelEvents;

    /** Nombre oficial de la carrera. */
    private const DEGREE_PROGRAM_NAME = 'Ingeniería en Tecnologías de Información';

    public function run(): void
    {
        $degreeProgramId = $this->degreeProgramId(self::DEGREE_PROGRAM_NAME);

        $studyPlanId = DB::table('study_plans')->insertGetId([
            'degree_program_id' => $degreeProgramId,
            'name' => 'Plan 2023',
            'implementation_year' => 2023,
            'classification' => 'Vigente',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $levelOneId = DB::table('levels')->insertGetId([
            'study_plan_id' => $studyPlanId,
            'number' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $levelTwoId = DB::table('levels')->insertGetId([
            'study_plan_id' => $studyPlanId,
            'number' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // courses.code tiene índice UNIQUE y AcademicCurriculaSeeder ya carga ITI-224.
        $calculusId = $this->courseId($degreeProgramId, 'MAT-101', 'Cálculo I');
        $programmingId = $this->courseId($degreeProgramId, 'ITI-224', 'Programación en Ambiente Web I');

        // Cursos adicionales solo para tener ejemplos variados del motor.
        $mathBasicId = $this->courseId($degreeProgramId, 'MAT-050', 'Matemática Básica');
        $dataStructuresId = $this->courseId($degreeProgramId, 'ITI-301', 'Estructuras de Datos');

        DB::table('course_level')->insert([
            ['level_id' => $levelOneId, 'course_id' => $calculusId, 'credits' => 4, 'created_at' => now()],
            ['level_id' => $levelOneId, 'course_id' => $mathBasicId, 'credits' => 3, 'created_at' => now()],
            ['level_id' => $levelTwoId, 'course_id' => $programmingId, 'credits' => 4, 'created_at' => now()],
            ['level_id' => $levelTwoId, 'course_id' => $dataStructuresId, 'credits' => 4, 'created_at' => now()],
        ]);

        // Boleta SLR-002: una regla declara qué justificación admite un curso.

        // Programación admite la justificación (a).
        DB::table('waiver_rules')->insert([
            'course_id' => $programmingId,
            'evaluation_order' => 1,
            'type' => 'El curso es el único pendiente para avanzar hacia el siguiente nivel del plan de estudios.',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Cálculo I lleva el caso especial.
        DB::table('waiver_rules')->insert([
            'course_id' => $calculusId,
            'evaluation_order' => 1,
            'type' => 'Solicitud de revisión manual por caso especial',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Estructuras de Datos admite SOLO la justificación (e).
        $dataStructuresRuleId = DB::table('waiver_rules')->insertGetId([
            'course_id' => $dataStructuresId,
            'evaluation_order' => 1,
            'type' => 'El estudiante manifiesta formalmente que tiene conocimiento previo del requisito y va a optar por la suficiencia del mismo en este cuatrimestre.',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $studentUser = User::factory()->create([
            'name' => 'Estudiante de Prueba',
            'email' => 'estudiante@utn.ac.cr',
            'password' => bcrypt('12345678'),
        ]);
        // Rol nominal sin permisos.
        $studentUser->roles()->sync(
            Role::query()->where('name', 'Estudiante')->pluck('id')
        );

        $studentId = DB::table('students')->insertGetId([
            'user_id' => $studentUser->id,
            'national_id' => '117890123',
            'first_name' => 'María',
            'last_name' => 'Rodríguez',
            'second_last_name' => 'Solís',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('student_study_plan')->insert([
            'student_id' => $studentId,
            'study_plan_id' => $studyPlanId,
            'current_level' => 2,
            'created_at' => now(),
        ]);

        // Expediente simulado.
        DB::table('academic_records')->insert([
            'student_id' => $studentId,
            'course_id' => $calculusId,
            // El cuatrimestre anterior al vigente: está en nivel 2.
            'academic_term_id' => $this->previousTermId(),
            'status' => 'Aprobado',
            'grade' => 85,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Precedente de convalidación (ES-02) para probar el indicador.
        $precedentId = DB::table('transfer_credit_precedents')->insertGetId([
            'institution' => 'Universidad de Costa Rica',
            'external_course' => 'Programación I',
            'course_id' => $programmingId,
            'outcome' => 'Aprobada',
            'resolution_number' => 'CTC-2024-018',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Solicitudes de ejemplo (ES-01/ES-02) para poblar el portal del estudiante.

        // Usuario de Docencia para revisar la bandeja centralizada (ES-04).
        $docencia = User::factory()->create([
            'name' => 'Docencia UTN',
            'email' => 'docencia@gmail.com',
            'password' => bcrypt('12345678'),
        ]);
        $docencia->roles()->sync(
            Role::query()->where('name', 'Docencia')->pluck('id')
        );

        // Usuario de Registro Universitario: primer paso del trámite.
        $registro = User::factory()->create([
            'name' => 'Registro Universitario UTN',
            'email' => 'registro@gmail.com',
            'password' => bcrypt('12345678'),
        ]);
        $registro->roles()->sync(
            Role::query()->where('name', 'Registro')->pluck('id')
        );

        // Usuario de Coordinadora para configurar reglas de levantamiento.
        $coordinadora = User::factory()->create([
            'name' => 'Coordinadora UTN',
            'email' => 'coordinadora@utn.ac.cr',
            'password' => bcrypt('12345678'),
        ]);
        $coordinadora->roles()->sync(
            Role::query()->where('name', 'Coordinadora')->pluck('id')
        );

        // Usuario de la Comisión Técnica para el catálogo de precedentes.
        $comision = User::factory()->create([
            'name' => 'Comisión Técnica UTN',
            'email' => 'comision@utn.ac.cr',
            'password' => bcrypt('12345678'),
        ]);
        $comision->roles()->sync(
            Role::query()->where('name', 'Comisión')->pluck('id')
        );

        DB::table('student_requests')->insert([
            [
                // ES-01 tipo (d): Cálculo I siempre exige revisión manual.
                'student_id' => $studentId,
                'type' => 'Levantamiento de requisito',
                'course_id' => $calculusId,
                'prerequisite_course_id' => $mathBasicId,
                // Cálculo I lleva el caso especial: admite cualquier justificación.
                'justification' => 'El curso es el único pendiente para avanzar hacia el siguiente nivel del plan de estudios.',
                'terms_accepted_at' => now()->subDays(2),
                'source_institution' => null,
                'external_course' => null,
                'transfer_credit_precedent_id' => null,
                'engine_result' => 'Requiere revisión manual',
                'failed_rule_id' => null,
                'status' => 'Pendiente de revisión',
                'teaching_decision' => null,
                'created_at' => now()->subDays(2),
                'updated_at' => now()->subDays(2),
            ],
            [
                // Programación admite la justificación (a).
                'student_id' => $studentId,
                'type' => 'Levantamiento de requisito',
                'course_id' => $programmingId,
                'prerequisite_course_id' => $calculusId,
                'justification' => 'El curso es el único pendiente para avanzar hacia el siguiente nivel del plan de estudios.',
                'terms_accepted_at' => now()->subDays(3),
                'source_institution' => null,
                'external_course' => null,
                'transfer_credit_precedent_id' => null,
                'engine_result' => 'Requiere revisión manual',
                'failed_rule_id' => null,
                'status' => 'Aprobada',
                'teaching_decision' => 'Aprobada',
                'created_at' => now()->subDays(3),
                'updated_at' => now()->subDays(3),
            ],
            [
                // ES-01 tipo (a), "No procede".
                'student_id' => $studentId,
                'type' => 'Levantamiento de requisito',
                'course_id' => $dataStructuresId,
                'prerequisite_course_id' => $programmingId,
                // Ese curso solo admite la justificación (e).
                'justification' => 'El curso es el único pendiente para avanzar hacia el siguiente nivel del plan de estudios.',
                'terms_accepted_at' => now()->subDay(),
                'source_institution' => null,
                'external_course' => null,
                'transfer_credit_precedent_id' => null,
                'engine_result' => 'No procede',
                'failed_rule_id' => $dataStructuresRuleId,
                'status' => 'Denegada',
                // Sin decisión de Docencia.
                'teaching_decision' => null,
                'created_at' => now()->subDay(),
                'updated_at' => now()->subDay(),
            ],
            [
                // ES-02 con precedente encontrado (misma institución/curso externo que $precedentId).
                'student_id' => $studentId,
                'type' => 'Convalidación',
                'course_id' => $programmingId,
                'prerequisite_course_id' => null,
                // Las convalidaciones usan otra boleta: sin justificación.
                'justification' => null,
                'terms_accepted_at' => null,
                'source_institution' => 'Universidad de Costa Rica',
                'external_course' => 'Programación I',
                'transfer_credit_precedent_id' => $precedentId,
                'engine_result' => null,
                'failed_rule_id' => null,
                // Docencia ya resolvió y espera que Registro emita el RSREC-001.
                'status' => 'Resuelta por Docencia',
                'teaching_decision' => 'Aprobada',
                // Los datos del RSREC-001.
                'created_at' => now()->subDays(4),
                'updated_at' => now()->subDay(),
            ],
            [
                // ES-02 sin precedente.
                'student_id' => $studentId,
                'type' => 'Convalidación',
                'course_id' => $calculusId,
                'prerequisite_course_id' => null,
                'justification' => null,
                'terms_accepted_at' => null,
                'source_institution' => 'Universidad Nacional',
                'external_course' => 'Cálculo Diferencial e Integral',
                'transfer_credit_precedent_id' => null,
                'engine_result' => null,
                'failed_rule_id' => null,
                'status' => 'Pendiente de revisión',
                'teaching_decision' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                // Escritorio de DOCENCIA.
                'student_id' => $studentId,
                'type' => 'Levantamiento de requisito',
                'course_id' => $mathBasicId,
                'prerequisite_course_id' => $calculusId,
                'justification' => 'El curso es el único pendiente para avanzar hacia el siguiente nivel del plan de estudios.',
                'terms_accepted_at' => now()->subDays(6),
                'source_institution' => null,
                'external_course' => null,
                'transfer_credit_precedent_id' => null,
                'engine_result' => 'Requiere revisión manual',
                'failed_rule_id' => null,
                'status' => 'Verificada por Registro',
                'teaching_decision' => null,
                'created_at' => now()->subDays(6),
                'updated_at' => now()->subDays(5),
            ],
            [
                // También de Docencia, un paso más adelante.
                'student_id' => $studentId,
                'type' => 'Convalidación',
                'course_id' => $dataStructuresId,
                'prerequisite_course_id' => null,
                'justification' => null,
                'terms_accepted_at' => null,
                'source_institution' => 'Universidad Estatal a Distancia',
                'external_course' => 'Estructuras de Datos y Algoritmos',
                'transfer_credit_precedent_id' => null,
                'engine_result' => null,
                'failed_rule_id' => null,
                'status' => 'En revisión',
                'teaching_decision' => null,
                'created_at' => now()->subDays(5),
                'updated_at' => now()->subDays(2),
            ],
        ]);

        $this->seedRequestedCourses($studentId, $calculusId, $programmingId, $mathBasicId);

    }

    /** Devuelve la carrera con ese nombre exacto. */
    private function degreeProgramId(string $name): int
    {
        $existing = DB::table('degree_programs')->where('name', $name)->value('id');
        if ($existing !== null) {
            return (int) $existing;
        }

        return DB::table('degree_programs')->insertGetId([
            'name' => $name,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /** Los cursos de las dos convalidaciones de demo. */
    private function seedRequestedCourses(int $studentId, int $calculusId, int $programmingId, int $mathBasicId): void
    {
        $awaiting = DB::table('student_requests')
            ->where('student_id', $studentId)
            ->where('type', 'Convalidación')
            ->where('status', 'Resuelta por Docencia')
            ->value('id');

        if ($awaiting === null) {
            return;
        }

        DB::table('student_request_courses')->insert([
            [
                'student_request_id' => $awaiting,
                'course_id' => $programmingId,
                'external_institution' => 'Universidad de Costa Rica',
                'external_course_name' => 'Programación I',
                // Código y créditos los transcribió Registro de la certificación.
                'external_course_code' => 'CI-0111',
                'external_credits' => 4,
                'outcome' => 'Reconocido',
                'denial_reason' => null,
                'created_at' => now()->subDays(4),
                'updated_at' => now()->subDay(),
            ],
            [
                'student_request_id' => $awaiting,
                'course_id' => $calculusId,
                'external_institution' => 'Universidad de Costa Rica',
                'external_course_name' => 'Cálculo I',
                'external_course_code' => 'MA-1001',
                'external_credits' => 4,
                'outcome' => 'Reconocido',
                'denial_reason' => null,
                'created_at' => now()->subDays(4),
                'updated_at' => now()->subDay(),
            ],
            [
                // El tercero NO se reconoce, y lleva su razón.
                'student_request_id' => $awaiting,
                'course_id' => $mathBasicId,
                'external_institution' => 'Universidad Nacional',
                'external_course_name' => 'Matemática General',
                'external_course_code' => 'MAT-100',
                'external_credits' => 3,
                'outcome' => 'No reconocido',
                'denial_reason' => 'Los contenidos no cubren los objetivos del curso de la UTN.',
                'created_at' => now()->subDays(4),
                'updated_at' => now()->subDay(),
            ],
        ]);
    }

    /** Devuelve el curso con ese código. */
    private function courseId(int $degreeProgramId, string $code, string $name): int
    {
        $existing = DB::table('courses')->where('code', $code)->value('id');
        if ($existing !== null) {
            return (int) $existing;
        }

        return DB::table('courses')->insertGetId([
            'degree_program_id' => $degreeProgramId,
            'code' => $code,
            'name' => $name,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /** El último cuatrimestre que empezó antes de hoy. */
    private function previousTermId(): ?int
    {
        // Se traen los dos últimos y se toma el segundo.
        $recent = DB::table('academic_terms')
            ->where('start_date', '<', now()->toDateString())
            ->orderByDesc('start_date')
            ->limit(2)
            ->pluck('id')
            ->all();

        return isset($recent[1]) ? (int) $recent[1] : null;
    }
}
