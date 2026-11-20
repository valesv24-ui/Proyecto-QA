<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

/** Tarea 2.2 del Plan de Trabajo. */
class RoleSeeder extends Seeder
{
    /** Run the database seeds. */
    public function run(): void
    {
        $superadmin = Role::query()->firstOrCreate(['name' => 'Superadmin']);
        $superadmin->permissions()->sync(Permission::query()->pluck('id'));
        Role::query()->firstOrCreate(['name' => 'Admin']);

        // Estudiante: existe para que "cada usuario tiene rol asignado" sea.
        Role::query()->firstOrCreate(['name' => 'Estudiante']);

        // Coordinadora: dueña de la configuración de reglas de levantamiento.
        $coordinadora = Role::query()->firstOrCreate(['name' => 'Coordinadora']);
        $coordinadora->permissions()->sync(
            Permission::query()->where('module', 'waiver_rules')->pluck('id')
        );

        // Registro Universitario: primer filtro del trámite.
        $registro = Role::query()->firstOrCreate(['name' => 'Registro']);
        $registro->permissions()->sync(
            Permission::query()
                ->where('module', 'student_requests')
                ->whereIn('action', ['view', 'search', 'verify', 'export_pdf', 'export_excel'])
                ->pluck('id')
        );

        // Docencia: toma la decisión final sobre las solicitudes que Registro.
        $docencia = Role::query()->firstOrCreate(['name' => 'Docencia']);
        $docencia->permissions()->sync(
            Permission::query()
                ->where('module', 'student_requests')
                ->whereIn('action', ['view', 'search', 'edit', 'export_pdf', 'export_excel'])
                ->pluck('id')
        );

        // Comisión Técnica.
        $comision = Role::query()->firstOrCreate(['name' => 'Comisión']);
        $comision->permissions()->sync(
            Permission::query()
                ->where('module', 'transfer_credit_precedents')
                ->orWhere(function ($query) {
                    $query->where('module', 'student_requests')
                        ->whereIn('action', ['view', 'search', 'edit', 'export_pdf', 'export_excel']);
                })
                ->pluck('id')
        );
    }
}
