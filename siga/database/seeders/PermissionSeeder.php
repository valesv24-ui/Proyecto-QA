<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Actions available for every manageable module.
     *
     * @var array<int, string>
     */
    private const ACTIONS = [
        'create',
        'view',
        'edit',
        'delete',
        'search',
        'export_pdf',
        'export_excel',
    ];

    /**
     * Modules that currently expose the actions above.
     *
     * @var array<int, string>
     */
    private const MODULES = ['roles', 'permissions', 'waiver_rules', 'transfer_credit_precedents', 'student_requests'];

    /**
     * Acciones que existen en UN módulo.
     *
     * @var array<string, array<int, string>>
     */
    private const MODULE_ACTIONS = [
        'student_requests' => ['verify'],
    ];

    public function run(): void
    {
        foreach (self::MODULES as $module) {
            $actions = [...self::ACTIONS, ...(self::MODULE_ACTIONS[$module] ?? [])];

            foreach ($actions as $action) {
                Permission::query()->firstOrCreate(
                    ['name' => "{$module}.{$action}"],
                    ['module' => $module, 'action' => $action],
                );
            }
        }
    }
}
