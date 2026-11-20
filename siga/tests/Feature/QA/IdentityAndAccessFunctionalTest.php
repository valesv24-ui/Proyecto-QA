<?php

declare(strict_types=1);

namespace Tests\Feature\QA;

use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

/**
 * Proyecto QA ISW-622 — Pruebas funcionales automatizadas (Persona B).
 *
 * Módulo: Identidad y acceso.
 * Cubre: inicio de sesión, cierre de sesión y control de acceso por rol.
 */
#[Group('funcionales')]
final class IdentityAndAccessFunctionalTest extends TestCase
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

    // CP-F-001 (Flujo crítico: inicio de sesión)
    #[Group('criticos')]
    public function test_usuario_con_credenciales_validas_inicia_sesion_y_accede_al_dashboard(): void
    {
        $user = User::factory()->create([
            'email' => 'estudiante.qa@est.utn.ac.cr',
            'password' => 'ClaveSegura2026!',
        ]);

        $response = $this->post(route('login.store'), [
            'email' => 'estudiante.qa@est.utn.ac.cr',
            'password' => 'ClaveSegura2026!',
        ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticatedAs($user);
    }

    // CP-F-002
    public function test_rechaza_inicio_de_sesion_con_contrasena_incorrecta(): void
    {
        $user = User::factory()->create([
            'email' => 'estudiante.qa@est.utn.ac.cr',
            'password' => 'ClaveSegura2026!',
        ]);

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'ContrasenaErronea999',
        ]);

        $response->assertSessionHasErrorsIn('email');
        $this->assertGuest();
    }

    // CP-F-003
    public function test_rol_estudiante_accede_a_sus_modulos_y_recibe_403_en_vistas_administrativas(): void
    {
        $role = Role::query()->where('name', 'Estudiante')->firstOrFail();
        $user = User::factory()->create();
        $user->roles()->sync([$role->id]);
        Student::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->get(route('studentrequests.studentrequest.index'))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('academiccatalog.studentfile.index'))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('studentrequests.waiverrule.index'))
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('studentrequests.transfercreditprecedent.index'))
            ->assertForbidden();
    }

    // CP-F-004
    public function test_roles_coordinadora_registro_y_docencia_acceden_segun_sus_permisos(): void
    {
        $coordinadoraRole = Role::query()->where('name', 'Coordinadora')->firstOrFail();
        $coordinadora = User::factory()->create();
        $coordinadora->roles()->sync([$coordinadoraRole->id]);

        $this->actingAs($coordinadora)
            ->get(route('studentrequests.waiverrule.index'))
            ->assertOk();

        $this->actingAs($coordinadora)
            ->get(route('studentrequests.transfercreditprecedent.index'))
            ->assertForbidden();

        $registroRole = Role::query()->where('name', 'Registro')->firstOrFail();
        $registro = User::factory()->create();
        $registro->roles()->sync([$registroRole->id]);

        $this->actingAs($registro)
            ->get(route('studentrequests.studentrequest.index'))
            ->assertOk();
    }

    // CP-F-005
    public function test_cierre_de_sesion_invalida_autenticacion_y_protege_rutas(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('logout'))
            ->assertRedirect(route('home'));

        $this->assertGuest();

        $this->get(route('studentrequests.studentrequest.index'))
            ->assertRedirect(route('login'));
    }
}