<?php

declare(strict_types=1);

namespace Tests\Feature\QA;

use App\Models\Course;
use App\Models\DegreeProgram;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudentRequest as StudentRequestModel;
use App\Models\StudyPlan;
use App\Models\TransferCreditPrecedent as TransferCreditPrecedentModel;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Group;
use Src\StudentRequests\StudentRequest\Domain\Entities\StudentRequest;
use Src\StudentRequests\StudentRequest\Presentation\Livewire\StudentRequestComponent;
use Tests\TestCase;

/**
 * Proyecto QA ISW-622 — Pruebas funcionales automatizadas (Persona B).
 *
 * Módulo: Solicitudes estudiantiles — Convalidación de créditos (ES-02).
 */
#[Group('funcionales')]
final class TransferRequestFunctionalTest extends TestCase
{
    use RefreshDatabase;

    private User $studentUser;

    private Student $student;

    private Course $courseOne;

    private Course $courseTwo;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Mail::fake();

        $this->seed([
            PermissionSeeder::class,
            RoleSeeder::class,
        ]);

        $program = DegreeProgram::factory()->create(['name' => 'Ingeniería del Software']);
        $plan = StudyPlan::factory()->create(['degree_program_id' => $program->id]);

        $this->courseOne = Course::factory()->create([
            'degree_program_id' => $program->id,
            'code' => 'MAT-101',
            'name' => 'Matemática General',
        ]);

        $this->courseTwo = Course::factory()->create([
            'degree_program_id' => $program->id,
            'code' => 'ING-101',
            'name' => 'Inglés I',
        ]);

        $role = Role::query()->where('name', 'Estudiante')->firstOrFail();
        $this->studentUser = User::factory()->create();
        $this->studentUser->roles()->sync([$role->id]);

        $this->student = Student::factory()->create(['user_id' => $this->studentUser->id]);
        $this->student->studyPlans()->attach($plan->id, ['current_level' => 1]);
    }

    // CP-F-011
    public function test_estudiante_presenta_solicitud_de_convalidacion_con_varios_cursos(): void
    {
        $certificado = UploadedFile::fake()->create('certificacion_notas.pdf', 800, 'application/pdf');
        $programa = UploadedFile::fake()->create('programa_curso.pdf', 600, 'application/pdf');

        Livewire::actingAs($this->studentUser)
            ->test(StudentRequestComponent::class)
            ->call('setTab', 'transfer')
            ->call('addTransferCourse')
            ->set('transferForm.courses', [
                [
                    'courseId' => (string) $this->courseOne->id,
                    'externalCourse' => 'Cálculo Diferencial',
                    'sourceInstitution' => 'Universidad de Costa Rica',
                ],
                [
                    'courseId' => (string) $this->courseTwo->id,
                    'externalCourse' => 'Inglés Básico I',
                    'sourceInstitution' => 'Universidad Nacional',
                ],
            ])
            ->set('transferForm.attachments', [$certificado, $programa])
            ->call('submitTransfer')
            ->assertHasNoErrors()
            ->assertSet('transferOutcomeBanner.variant', 'success');

        $this->assertDatabaseHas('student_requests', [
            'student_id' => $this->student->id,
            'type' => StudentRequest::TYPE_TRANSFER,
            'course_id' => $this->courseOne->id,
            'status' => StudentRequest::STATUS_PENDING,
        ]);

        $request = StudentRequestModel::query()->firstOrFail();
        $this->assertDatabaseCount('student_request_courses', 2);
        $this->assertDatabaseHas('student_request_courses', [
            'student_request_id' => $request->id,
            'course_id' => $this->courseTwo->id,
            'external_course_name' => 'Inglés Básico I',
        ]);
    }

    // CP-F-012
    public function test_vincula_automaticamente_precedente_aprobado_al_presentar_convalidacion(): void
    {
        $precedent = TransferCreditPrecedentModel::factory()->create([
            'institution' => 'Tecnológico de Costa Rica',
            'external_course' => 'Matemática Elemental',
            'course_id' => $this->courseOne->id,
            'outcome' => 'Aprobada',
            'resolution_number' => 'RES-CTA-2026-10',
        ]);

        $pdf = UploadedFile::fake()->create('historial_tec.pdf', 450, 'application/pdf');

        Livewire::actingAs($this->studentUser)
            ->test(StudentRequestComponent::class)
            ->call('setTab', 'transfer')
            ->set('transferForm.courses', [
                [
                    'courseId' => (string) $this->courseOne->id,
                    'externalCourse' => 'Matemática Elemental',
                    'sourceInstitution' => 'Tecnológico de Costa Rica',
                ],
            ])
            ->set('transferForm.attachments', [$pdf])
            ->call('submitTransfer')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('student_requests', [
            'student_id' => $this->student->id,
            'type' => StudentRequest::TYPE_TRANSFER,
            'transfer_credit_precedent_id' => $precedent->id,
        ]);
    }

    // CP-F-013
    public function test_rechaza_cursos_utn_duplicados_en_una_misma_solicitud_de_convalidacion(): void
    {
        $pdf = UploadedFile::fake()->create('notas.pdf', 300, 'application/pdf');

        Livewire::actingAs($this->studentUser)
            ->test(StudentRequestComponent::class)
            ->call('setTab', 'transfer')
            ->set('transferForm.courses', [
                [
                    'courseId' => (string) $this->courseOne->id,
                    'externalCourse' => 'Álgebra I',
                    'sourceInstitution' => 'UCR',
                ],
                [
                    'courseId' => (string) $this->courseOne->id,
                    'externalCourse' => 'Matemática I',
                    'sourceInstitution' => 'UNA',
                ],
            ])
            ->set('transferForm.attachments', [$pdf])
            ->call('submitTransfer')
            ->assertHasErrors(['transferForm.courses']);

        $this->assertDatabaseCount('student_requests', 0);
    }

    // CP-F-014
    public function test_sugiere_instituciones_de_origen_al_buscar_universidad(): void
    {
        Http::fake([
            'universities.hipolabs.com/*' => Http::response([
                ['name' => 'Universidad de Costa Rica', 'country' => 'Costa Rica'],
                ['name' => 'Universidad Nacional Costa Rica', 'country' => 'Costa Rica'],
            ], 200),
        ]);

        Livewire::actingAs($this->studentUser)
            ->test(StudentRequestComponent::class)
            ->call('searchSourceInstitutions', 'Universidad')
            ->assertSet('sourceInstitutionSuggestions', [
                'Universidad de Costa Rica',
                'Universidad Nacional Costa Rica',
            ]);
    }
}