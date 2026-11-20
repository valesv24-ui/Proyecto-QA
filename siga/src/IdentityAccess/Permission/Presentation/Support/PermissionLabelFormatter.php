<?php

declare(strict_types=1);

namespace Src\IdentityAccess\Permission\Presentation\Support;

/** Turns a raw. */
final class PermissionLabelFormatter
{
    /** @var array<string, string> */
    private const ACTION_LABELS = [
        'create' => 'Crear',
        'view' => 'Ver',
        'edit' => 'Editar',
        'delete' => 'Eliminar',
        'search' => 'Buscar',
        'export_pdf' => 'Exportar PDF de',
        'export_excel' => 'Exportar Excel de',
    ];

    /** @var array<string, string> */
    private const MODULE_LABELS = [
        'roles' => 'roles',
        'permissions' => 'permisos',
    ];

    public static function forHumans(string $module, string $action): string
    {
        $actionLabel = self::ACTION_LABELS[$action]
            ?? ucfirst(str_replace('_', ' ', $action));

        $moduleLabel = self::MODULE_LABELS[$module] ?? $module;

        return "{$actionLabel} {$moduleLabel}";
    }
}
