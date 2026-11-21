<?php

declare(strict_types=1);

namespace Tests\Feature\QA;

use App\Models\AcademicRecord;
use App\Models\AcademicTerm;
use App\Models\Course;
use App\Models\DegreeProgram;
use App\Models\Level;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudentRequest as StudentRequestModel;
use App\Models\StudyPlan;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Group;
use Src\AcademicCatalog\StudentFile\Presentation\Livewire\StudentFileComponent;
use Src\StudentRequests\StudentRequest\Domain\Entities\StudentRequest;
use Tests\TestCase;

/**
 * Proyecto QA ISW-622 — Pruebas funcionales automatizadas (Persona B).
 *
 * Módulo: Catálogo/Expediente académico y API REST autenticada con JWT.
 */
#[Group('funcionales')]
final class AcademicRecordAndApiFunctionalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            PermissionSeeder::class,
            RoleSeeder::class,
        ]);
    }

    // CP-F-023
    public function test_estudiante_consulta_su_expediente_academico_con_cursos_y_creditos(): void
    {
        $program = DegreeProgram::factory()->create(['name' => 'Ingeniería del Software']);
        $plan = StudyPlan::factory()->create(['degree_program_id' => $program->id]);
        $level = Level::factory()->create(['study_plan_id' => $plan->id, 'number' => 1]);
        $term = AcademicTerm::factory()->create();

        $course = Course::factory()->create([
            'degree_program_id' => $program->id,
            'code' => 'ISW-101',
            'name' => 'Introducción a la Informática',
        ]);

        DB::table('course_level')->insert([
            'course_id' => $course->id,
            'level_id' => $level->id,
            'credits' => 4,
        ]);

        $role = Role::query()->where('name', 'Estudiante')->firstOrFail();
        $user = User::factory()->create();
        $user->roles()->sync([$role->id]);

        $student = Student::factory()->create([
            'user_id' => $user->id,
            'first_name' => 'Maykel',
            'last_name' => 'Meoño',
        ]);
        $student->studyPlans()->attach($plan->id, ['current_level' => 2]);

        AcademicRecord::factory()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
            'academic_term_id' => $term->id,
            'status' => 'Aprobado',
            'grade' => 92,
        ]);

        Livewire::actingAs($user)
            ->test(StudentFileComponent::class)
            ->assertViewHas('file', function (?array $file): bool {
                return $file !== null
                    && $file['approved'] === 1
                    && $file['earnedCredits'] === 4
                    && count($file['courses']) === 1
                    && $file['courses'][0]['code'] === 'ISW-101';
            });
    }

    // CP-F-024
    public function test_api_jwt_autentica_estudiante_y_devuelve_sus_solicitudes_con_fecha_estimada(): void
    {
        $user = User::factory()->create([
            'email' => 'api.estudiante@est.utn.ac.cr',
            'password' => 'ApiPass2026!',
        ]);
        $student = Student::factory()->create(['user_id' => $user->id]);

        $request = StudentRequestModel::factory()->create([
            'student_id' => $student->id,
            'type' => StudentRequest::TYPE_WAIVER,
            'status' => StudentRequest::STATUS_PENDING,
            'estimated_resolution_date' => '2026-12-01',
        ]);

        // 1. Petición sin token debe retornar 401
        $this->getJson(route('api.studentrequests.index'))
            ->assertUnauthorized();

        // 2. Login JWT entrega token Bearer
        $loginResponse = $this->postJson(route('api.auth.login'), [
            'email' => 'api.estudiante@est.utn.ac.cr',
            'password' => 'ApiPass2026!',
        ]);

        $loginResponse
            ->assertOk()
            ->assertJsonStructure(['token', 'token_type']);

        $token = (string) $loginResponse->json('token');

        // 3. Consulta autenticada devuelve el estado y fecha estimada de la solicitud
        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson(route('api.studentrequests.index'))
            ->assertOk()
            ->assertJsonFragment([
                'id' => $request->id,
                'type' => StudentRequest::TYPE_WAIVER,
                'status' => StudentRequest::STATUS_PENDING,
                'estimated_resolution_date' => '2026-12-01',
            ]);
    }
}