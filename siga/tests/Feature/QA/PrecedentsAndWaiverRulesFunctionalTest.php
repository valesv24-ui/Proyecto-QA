<?php

declare(strict_types=1);

namespace Tests\Feature\QA;

use App\Models\Course;
use App\Models\DegreeProgram;
use App\Models\Role;
use App\Models\TransferCreditPrecedent as TransferCreditPrecedentModel;
use App\Models\User;
use App\Models\WaiverRule as WaiverRuleModel;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Group;
use Src\StudentRequests\TransferCreditPrecedent\Domain\Entities\TransferCreditPrecedent;
use Src\StudentRequests\TransferCreditPrecedent\Presentation\Livewire\TransferCreditPrecedentComponent;
use Src\StudentRequests\WaiverRule\Domain\Entities\WaiverRule;
use Src\StudentRequests\WaiverRule\Presentation\Livewire\WaiverRuleComponent;
use Tests\TestCase;

/**
 * Proyecto QA ISW-622 — Pruebas funcionales automatizadas (Persona B).
 *
 * Módulo: Precedentes de convalidación y reglas de levantamiento.
 */
#[Group('funcionales')]
final class PrecedentsAndWaiverRulesFunctionalTest extends TestCase
{
    use RefreshDatabase;

    private User $coordinadora;

    private User $comision;

    private Course $course;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->seed([
            PermissionSeeder::class,
            RoleSeeder::class,
        ]);

        $program = DegreeProgram::factory()->create(['name' => 'Ingeniería del Software']);
        $this->course = Course::factory()->create([
            'degree_program_id' => $program->id,
            'code' => 'ISW-521',
            'name' => 'Ingeniería de Software II',
        ]);

        $coordRole = Role::query()->where('name', 'Coordinadora')->firstOrFail();
        $this->coordinadora = User::factory()->create();
        $this->coordinadora->roles()->sync([$coordRole->id]);

        $comisionRole = Role::query()->where('name', 'Comisión')->firstOrFail();
        $this->comision = User::factory()->create();
        $this->comision->roles()->sync([$comisionRole->id]);
    }

    // CP-F-020
    public function test_coordinadora_crea_edita_y_elimina_reglas_de_levantamiento(): void
    {
        Livewire::actingAs($this->coordinadora)
            ->test(WaiverRuleComponent::class)
            ->call('openCreateModal')
            ->set('form.courseId', $this->course->id)
            ->set('form.evaluationOrder', 1)
            ->set('form.type', WaiverRule::TYPE_ONLY_PENDING_COURSE)
            ->set('form.isActive', true)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('waiver_rules', [
            'course_id' => $this->course->id,
            'evaluation_order' => 1,
            'type' => WaiverRule::TYPE_ONLY_PENDING_COURSE,
            'is_active' => true,
        ]);

        $rule = WaiverRuleModel::query()->firstOrFail();

        Livewire::actingAs($this->coordinadora)
            ->test(WaiverRuleComponent::class)
            ->call('openEditModal', $rule->id)
            ->set('form.isActive', false)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('waiver_rules', [
            'id' => $rule->id,
            'is_active' => false,
        ]);

        Livewire::actingAs($this->coordinadora)
            ->test(WaiverRuleComponent::class)
            ->call('delete', $rule->id)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('waiver_rules', ['id' => $rule->id]);
    }

    // CP-F-021
    public function test_comision_crea_precedente_y_valida_unicidad_institucion_curso_externo(): void
    {
        Livewire::actingAs($this->comision)
            ->test(TransferCreditPrecedentComponent::class)
            ->call('openCreateModal')
            ->set('form.institution', 'Universidad de Costa Rica')
            ->set('form.externalCourse', 'Ingeniería de Software I')
            ->set('form.courseId', $this->course->id)
            ->set('form.outcome', 'Aprobada')
            ->set('form.resolutionNumber', 'RES-CTA-2026-01')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('transfer_credit_precedents', [
            'institution' => 'Universidad de Costa Rica',
            'external_course' => 'Ingeniería de Software I',
            'resolution_number' => 'RES-CTA-2026-01',
        ]);

        // Intento duplicado de la misma combinación institución + curso externo
        Livewire::actingAs($this->comision)
            ->test(TransferCreditPrecedentComponent::class)
            ->call('openCreateModal')
            ->set('form.institution', 'Universidad de Costa Rica')
            ->set('form.externalCourse', 'Ingeniería de Software I')
            ->set('form.courseId', $this->course->id)
            ->set('form.outcome', 'Aprobada')
            ->set('form.resolutionNumber', 'RES-CTA-2026-02')
            ->call('save')
            ->assertHasErrors(['form.externalCourse']);
    }

    // CP-F-022
    public function test_comision_adjunta_documento_de_resolucion_a_un_precedente(): void
    {
        $precedent = TransferCreditPrecedentModel::factory()->create([
            'institution' => 'Universidad Nacional',
            'external_course' => 'Análisis de Sistemas',
            'course_id' => $this->course->id,
            'outcome' => 'Aprobada',
            'resolution_number' => 'RES-CTA-2026-05',
        ]);

        $pdf = UploadedFile::fake()->create('resolucion_firmada.pdf', 400, 'application/pdf');

        Livewire::actingAs($this->comision)
            ->test(TransferCreditPrecedentComponent::class)
            ->call('openEditModal', $precedent->id)
            ->set('newAttachment', $pdf)
            ->set('newAttachmentType', 'Resolución oficial')
            ->call('uploadAttachment')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('attachments', [
            'attachable_type' => TransferCreditPrecedent::class,
            'attachable_id' => $precedent->id,
            'document_type' => 'Resolución oficial',
            'original_name' => 'resolucion_firmada.pdf',
        ]);
    }
}