<?php

declare(strict_types=1);

namespace Tests\Feature\QA;

use App\Models\Course;
use App\Models\DegreeProgram;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudentRequest as StudentRequestModel;
use App\Models\StudyPlan;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Group;
use Src\StudentRequests\StudentRequest\Domain\Entities\RequestedCourse;
use Src\StudentRequests\StudentRequest\Domain\Entities\StudentRequest;
use Src\StudentRequests\StudentRequest\Presentation\Livewire\StudentRequestComponent;
use Src\StudentRequests\WaiverRule\Domain\Entities\WaiverRule;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

/**
 * Proyecto QA ISW-622 — Pruebas funcionales automatizadas (Persona B).
 *
 * Módulo: Bandeja centralizada, flujo de estados entre escritorios y exportaciones.
 */
#[Group('funcionales')]
final class InboxWorkflowAndStatusFunctionalTest extends TestCase
{
    use RefreshDatabase;

    private User $registroUser;

    private User $docenciaUser;

    private Student $student;

    private Course $course;

    private Course $prerequisite;

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

        $this->prerequisite = Course::factory()->create([
            'degree_program_id' => $program->id,
            'code' => 'ISW-321',
            'name' => 'Bases de Datos I',
        ]);

        $this->course = Course::factory()->create([
            'degree_program_id' => $program->id,
            'code' => 'ISW-421',
            'name' => 'Bases de Datos II',
        ]);

        $this->student = Student::factory()->withUserAccount()->create();
        $this->student->studyPlans()->attach($plan->id, ['current_level' => 4]);

        $registroRole = Role::query()->where('name', 'Registro')->firstOrFail();
        $this->registroUser = User::factory()->create(['name' => 'Analista Registro']);
        $this->registroUser->roles()->sync([$registroRole->id]);

        $docenciaRole = Role::query()->where('name', 'Docencia')->firstOrFail();
        $this->docenciaUser = User::factory()->create(['name' => 'Coordinación Docencia']);
        $this->docenciaUser->roles()->sync([$docenciaRole->id]);
    }

    // CP-F-015
    public function test_bandeja_segrega_solicitudes_segun_el_escritorio_de_registro_o_docencia(): void
    {
        $pending = StudentRequestModel::factory()->create([
            'student_id' => $this->student->id,
            'type' => StudentRequest::TYPE_WAIVER,
            'course_id' => $this->course->id,
            'prerequisite_course_id' => $this->prerequisite->id,
            'status' => StudentRequest::STATUS_PENDING,
        ]);

        $verified = StudentRequestModel::factory()->create([
            'student_id' => $this->student->id,
            'type' => StudentRequest::TYPE_WAIVER,
            'course_id' => $this->course->id,
            'prerequisite_course_id' => $this->prerequisite->id,
            'status' => StudentRequest::STATUS_VERIFIED,
        ]);

        Livewire::actingAs($this->registroUser)
            ->test(StudentRequestComponent::class)
            ->assertViewHas('inboxPaginator', function ($paginator) use ($pending, $verified): bool {
                $ids = collect($paginator->items())->pluck('id')->all();

                return in_array($pending->id, $ids, true)
                    && ! in_array($verified->id, $ids, true);
            });

        Livewire::actingAs($this->docenciaUser)
            ->test(StudentRequestComponent::class)
            ->assertViewHas('inboxPaginator', function ($paginator) use ($pending, $verified): bool {
                $ids = collect($paginator->items())->pluck('id')->all();

                return in_array($verified->id, $ids, true)
                    && ! in_array($pending->id, $ids, true);
            });
    }

    // CP-F-016 (Flujo crítico: cambiar estado)
    #[Group('criticos')]
    public function test_flujo_completo_de_estados_registro_docencia_publicacion_con_historial(): void
    {
        $request = StudentRequestModel::factory()->create([
            'student_id' => $this->student->id,
            'type' => StudentRequest::TYPE_WAIVER,
            'course_id' => $this->course->id,
            'prerequisite_course_id' => $this->prerequisite->id,
            'justification' => WaiverRule::TYPE_PRIOR_KNOWLEDGE,
            'status' => StudentRequest::STATUS_PENDING,
        ]);

        // 1. Registro verifica la solicitud -> Verificada por Registro
        Livewire::actingAs($this->registroUser)
            ->test(StudentRequestComponent::class)
            ->call('openRequestDetail', $request->id, '')
            ->set('statusChangeComment', 'Expediente completo verificado por Registro.')
            ->call('changeRequestStatus', StudentRequest::STATUS_VERIFIED)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('student_requests', [
            'id' => $request->id,
            'status' => StudentRequest::STATUS_IN_REVIEW,
        ]);

        $this->assertDatabaseHas('student_request_status_history', [
            'student_request_id' => $request->id,
            'new_status' => StudentRequest::STATUS_VERIFIED,
        ]);

        // 2. Docencia resuelve Aprobada -> Resuelta por Docencia
        Livewire::actingAs($this->docenciaUser)
            ->test(StudentRequestComponent::class)
            ->call('openRequestDetail', $request->id, '')
            ->set('statusChangeComment', 'Procede el levantamiento por suficiencia.')
            ->call('decideRequest', StudentRequest::STATUS_APPROVED)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('student_requests', [
            'id' => $request->id,
            'status' => StudentRequest::STATUS_DECIDED,
            'teaching_decision' => StudentRequest::STATUS_APPROVED,
        ]);

        // 3. Registro publica la resolución -> Aprobada
        Livewire::actingAs($this->registroUser)
            ->test(StudentRequestComponent::class)
            ->call('openRequestDetail', $request->id, '')
            ->call('publishResolution')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('student_requests', [
            'id' => $request->id,
            'status' => StudentRequest::STATUS_APPROVED,
        ]);

        $this->assertDatabaseCount('student_request_status_history', 4);
    }

    // CP-F-017
    public function test_resolucion_curso_por_curso_y_publicacion_rsrec001_en_convalidacion(): void
    {
        $request = StudentRequestModel::factory()->transfer()->create([
            'student_id' => $this->student->id,
            'course_id' => $this->course->id,
            'source_institution' => 'Universidad de Costa Rica',
            'external_course' => 'Bases de Datos Avanzadas',
            'status' => StudentRequest::STATUS_VERIFIED,
        ]);

        $requestedCourseId = (int) DB::table('student_request_courses')->insertGetId([
            'student_request_id' => $request->id,
            'course_id' => $this->course->id,
            'external_institution' => 'Universidad de Costa Rica',
            'external_course_name' => 'Bases de Datos Avanzadas',
            'outcome' => RequestedCourse::OUTCOME_PENDING,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Registro transcribe código, créditos y nota externa
        Livewire::actingAs($this->registroUser)
            ->test(StudentRequestComponent::class)
            ->call('openRequestDetail', $request->id, '')
            ->set("courseEdits.{$requestedCourseId}.code", 'CI-0128')
            ->set("courseEdits.{$requestedCourseId}.credits", '4')
            ->set("courseEdits.{$requestedCourseId}.grade", '90')
            ->call('saveRequestedCourseData', $requestedCourseId)
            ->assertHasNoErrors();

        // Docencia pasa a En revisión, reconoce el curso y devuelve a Registro
        Livewire::actingAs($this->docenciaUser)
            ->test(StudentRequestComponent::class)
            ->call('openRequestDetail', $request->id, '')
            ->call('changeRequestStatus', StudentRequest::STATUS_IN_REVIEW)
            ->call('resolveRequestedCourse', $requestedCourseId, RequestedCourse::OUTCOME_RECOGNIZED)
            ->call('decideRequest', '')
            ->assertHasNoErrors();

        // Registro publica la resolución con acta y sesión (emite RSREC-001)
        Livewire::actingAs($this->registroUser)
            ->test(StudentRequestComponent::class)
            ->call('openRequestDetail', $request->id, '')
            ->set('resolutionForm', [
                'resolutionNumber' => 'RES-2026-088',
                'sessionNumber' => 'SES-15',
                'minutesNumber' => 'ACTA-019',
                'sessionDate' => '2026-11-24',
            ])
            ->call('publishResolution')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('student_requests', [
            'id' => $request->id,
            'status' => StudentRequest::STATUS_APPROVED,
        ]);

        $this->assertDatabaseHas('academic_records', [
            'student_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => 'Acreditado por convalidación',
        ]);
    }

    // CP-F-018
    public function test_asigna_fecha_manual_de_resolucion_futura_y_rechaza_fechas_pasadas(): void
    {
        $request = StudentRequestModel::factory()->create([
            'student_id' => $this->student->id,
            'course_id' => $this->course->id,
            'prerequisite_course_id' => $this->prerequisite->id,
            'status' => StudentRequest::STATUS_IN_REVIEW,
        ]);

        $futureDate = now()->addDays(10)->format('Y-m-d');
        $pastDate = now()->subDays(3)->format('Y-m-d');

        Livewire::actingAs($this->docenciaUser)
            ->test(StudentRequestComponent::class)
            ->call('openRequestDetail', $request->id, '')
            ->set('manualResolutionDate', $pastDate)
            ->call('assignManualResolutionDate')
            ->assertHasErrors(['manualResolutionDate'])
            ->set('manualResolutionDate', $futureDate)
            ->call('assignManualResolutionDate')
            ->assertHasNoErrors();

        $this->assertSame($futureDate, $request->fresh()->estimated_resolution_date?->format('Y-m-d'));
    }

    // CP-F-019
    public function test_exporta_bandeja_de_solicitudes_en_pdf_y_excel(): void
    {
        StudentRequestModel::factory()->create([
            'student_id' => $this->student->id,
            'course_id' => $this->course->id,
            'prerequisite_course_id' => $this->prerequisite->id,
            'status' => StudentRequest::STATUS_PENDING,
        ]);

        $component = Livewire::actingAs($this->registroUser)
            ->test(StudentRequestComponent::class);

        $pdfResponse = $component->instance()->exportPdf(
            app(\Src\Shared\Export\Contracts\PdfExporterInterface::class),
            app(\Src\StudentRequests\StudentRequest\Application\UseCases\ListStudentRequestsForInboxUseCase::class),
            app(\Src\AcademicCatalog\Course\Application\UseCases\ListCoursesUseCase::class),
            app(\Src\AcademicCatalog\Student\Domain\Contracts\StudentRepositoryInterface::class),
        );

        $this->assertInstanceOf(StreamedResponse::class, $pdfResponse);
        $this->assertSame(200, $pdfResponse->getStatusCode());

        $excelResponse = $component->instance()->exportExcel(
            app(\Src\Shared\Export\Contracts\ExcelExporterInterface::class),
            app(\Src\StudentRequests\StudentRequest\Application\UseCases\ListStudentRequestsForInboxUseCase::class),
            app(\Src\AcademicCatalog\Course\Application\UseCases\ListCoursesUseCase::class),
            app(\Src\AcademicCatalog\Student\Domain\Contracts\StudentRepositoryInterface::class),
        );

        $this->assertInstanceOf(StreamedResponse::class, $excelResponse);
        $this->assertSame(200, $excelResponse->getStatusCode());
    }
}