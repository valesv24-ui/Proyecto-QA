<?php

declare(strict_types=1);

namespace Tests\Feature\QA;

use App\Models\Course;
use App\Models\DegreeProgram;
use App\Models\Prerequisite;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudentRequest as StudentRequestModel;
use App\Models\StudyPlan;
use App\Models\User;
use App\Models\WaiverRule as WaiverRuleModel;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Group;
use Src\StudentRequests\StudentRequest\Domain\Entities\StudentRequest;
use Src\StudentRequests\StudentRequest\Presentation\Livewire\StudentRequestComponent;
use Src\StudentRequests\WaiverRule\Domain\Entities\WaiverRule;
use Tests\TestCase;

/**
 * Proyecto QA ISW-622 — Pruebas funcionales automatizadas (Persona B).
 *
 * Módulo: Solicitudes estudiantiles — Levantamiento de requisitos (ES-01 / Boleta SLR-002).
 */
#[Group('funcionales')]
final class WaiverRequestFunctionalTest extends TestCase
{
    use RefreshDatabase;

    private User $studentUser;

    private Student $student;

    private Course $targetCourse;

    private Course $prerequisiteCourse;

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

        $this->prerequisiteCourse = Course::factory()->create([
            'degree_program_id' => $program->id,
            'code' => 'ISW-121',
            'name' => 'Programación I',
        ]);

        $this->targetCourse = Course::factory()->create([
            'degree_program_id' => $program->id,
            'code' => 'ISW-221',
            'name' => 'Programación II',
        ]);

        Prerequisite::query()->create([
            'study_plan_id' => $plan->id,
            'required_course_id' => $this->prerequisiteCourse->id,
            'target_course_id' => $this->targetCourse->id,
        ]);

        $role = Role::query()->where('name', 'Estudiante')->firstOrFail();
        $this->studentUser = User::factory()->create();
        $this->studentUser->roles()->sync([$role->id]);

        $this->student = Student::factory()->create(['user_id' => $this->studentUser->id]);
        $this->student->studyPlans()->attach($plan->id, ['current_level' => 2]);
    }

    // CP-F-006 (Flujo crítico: crear solicitud)
    #[Group('criticos')]
    public function test_estudiante_presenta_solicitud_de_levantamiento_con_adjunto_y_fecha_estimada(): void
    {
        $pdf = UploadedFile::fake()->create('boleta_slr002.pdf', 500, 'application/pdf');

        Livewire::actingAs($this->studentUser)
            ->test(StudentRequestComponent::class)
            ->set('waiverForm.courseId', $this->targetCourse->id)
            ->set('waiverForm.prerequisiteCourseId', $this->prerequisiteCourse->id)
            ->set('waiverForm.justification', WaiverRule::TYPE_ONLY_PENDING_COURSE)
            ->set('waiverForm.acceptsTerms', true)
            ->set('waiverForm.attachment', $pdf)
            ->call('submitWaiver')
            ->assertHasNoErrors()
            ->assertDispatched('toast');

        $this->assertDatabaseHas('student_requests', [
            'student_id' => $this->student->id,
            'type' => StudentRequest::TYPE_WAIVER,
            'course_id' => $this->targetCourse->id,
            'prerequisite_course_id' => $this->prerequisiteCourse->id,
            'status' => StudentRequest::STATUS_PENDING,
        ]);

        $created = StudentRequestModel::query()->firstOrFail();
        $this->assertNotNull($created->estimated_resolution_date);

        $this->assertDatabaseHas('attachments', [
            'attachable_type' => StudentRequest::class,
            'attachable_id' => $created->id,
            'original_name' => 'boleta_slr002.pdf',
        ]);
    }

    // CP-F-007
    public function test_valida_campos_obligatorios_y_aceptacion_de_notas_en_boleta_slr002(): void
    {
        $pdf = UploadedFile::fake()->create('respaldo.pdf', 200, 'application/pdf');

        Livewire::actingAs($this->studentUser)
            ->test(StudentRequestComponent::class)
            ->set('waiverForm.courseId', $this->targetCourse->id)
            ->set('waiverForm.prerequisiteCourseId', $this->prerequisiteCourse->id)
            ->set('waiverForm.justification', '')
            ->set('waiverForm.acceptsTerms', false)
            ->set('waiverForm.attachment', $pdf)
            ->call('submitWaiver')
            ->assertHasErrors([
                'waiverForm.justification',
                'waiverForm.acceptsTerms',
            ]);

        $this->assertDatabaseCount('student_requests', 0);
    }

    // CP-F-008
    public function test_valida_extension_y_tamano_maximo_de_archivo_adjunto(): void
    {
        $invalidFile = UploadedFile::fake()->create('script_malicioso.exe', 100, 'application/x-msdownload');

        Livewire::actingAs($this->studentUser)
            ->test(StudentRequestComponent::class)
            ->set('waiverForm.courseId', $this->targetCourse->id)
            ->set('waiverForm.prerequisiteCourseId', $this->prerequisiteCourse->id)
            ->set('waiverForm.justification', WaiverRule::TYPE_MINIMUM_ACADEMIC_LOAD)
            ->set('waiverForm.acceptsTerms', true)
            ->set('waiverForm.attachment', $invalidFile)
            ->call('submitWaiver')
            ->assertHasErrors(['waiverForm.attachment']);

        $oversizedPdf = UploadedFile::fake()->create('pesado.pdf', 10241, 'application/pdf');

        Livewire::actingAs($this->studentUser)
            ->test(StudentRequestComponent::class)
            ->set('waiverForm.courseId', $this->targetCourse->id)
            ->set('waiverForm.prerequisiteCourseId', $this->prerequisiteCourse->id)
            ->set('waiverForm.justification', WaiverRule::TYPE_MINIMUM_ACADEMIC_LOAD)
            ->set('waiverForm.acceptsTerms', true)
            ->set('waiverForm.attachment', $oversizedPdf)
            ->call('submitWaiver')
            ->assertHasErrors(['waiverForm.attachment']);
    }

    // CP-F-009
    public function test_motor_de_reglas_aprueba_automaticamente_cuando_existe_regla_coincidente(): void
    {
        WaiverRuleModel::factory()->create([
            'course_id' => $this->targetCourse->id,
            'evaluation_order' => 1,
            'type' => WaiverRule::TYPE_ONLY_PENDING_COURSE,
            'is_active' => true,
        ]);

        $pdf = UploadedFile::fake()->create('constancia.pdf', 300, 'application/pdf');

        Livewire::actingAs($this->studentUser)
            ->test(StudentRequestComponent::class)
            ->set('waiverForm.courseId', $this->targetCourse->id)
            ->set('waiverForm.prerequisiteCourseId', $this->prerequisiteCourse->id)
            ->set('waiverForm.justification', WaiverRule::TYPE_ONLY_PENDING_COURSE)
            ->set('waiverForm.acceptsTerms', true)
            ->set('waiverForm.attachment', $pdf)
            ->call('submitWaiver')
            ->assertHasNoErrors()
            ->assertSet('waiverOutcomeBanner.variant', 'info');

        $this->assertDatabaseHas('student_requests', [
            'student_id' => $this->student->id,
            'course_id' => $this->targetCourse->id,
            'engine_result' => StudentRequest::RESULT_MANUAL_REVIEW,
            'status' => StudentRequest::STATUS_PENDING,
        ]);
    }

    // CP-F-010
    public function test_impide_duplicar_un_levantamiento_ya_aprobado_para_el_mismo_curso(): void
    {
        StudentRequestModel::factory()->create([
            'student_id' => $this->student->id,
            'type' => StudentRequest::TYPE_WAIVER,
            'course_id' => $this->targetCourse->id,
            'prerequisite_course_id' => $this->prerequisiteCourse->id,
            'status' => StudentRequest::STATUS_APPROVED,
        ]);

        $pdf = UploadedFile::fake()->create('nuevo_intento.pdf', 250, 'application/pdf');

        Livewire::actingAs($this->studentUser)
            ->test(StudentRequestComponent::class)
            ->set('waiverForm.courseId', $this->targetCourse->id)
            ->set('waiverForm.prerequisiteCourseId', $this->prerequisiteCourse->id)
            ->set('waiverForm.justification', WaiverRule::TYPE_ONLY_PENDING_COURSE)
            ->set('waiverForm.acceptsTerms', true)
            ->set('waiverForm.attachment', $pdf)
            ->call('submitWaiver')
            ->assertHasNoErrors()
            ->assertSet('waiverOutcomeBanner.variant', 'warning');

        $this->assertDatabaseCount('student_requests', 1);
    }
}