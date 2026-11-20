<?php

declare(strict_types=1);

namespace Src\IdentityAccess\Permission\Presentation\Livewire\Forms;

use Illuminate\Validation\Rule;
use Livewire\Form;
use Src\IdentityAccess\Permission\Application\DTOs\PermissionDTO;
use Src\IdentityAccess\Permission\Domain\Entities\Permission;
use Src\IdentityAccess\Permission\Presentation\Livewire\PermissionComponent;

class PermissionForm extends Form
{
    public string $module = '';

    public string $action = '';

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var PermissionComponent $component */
        $component = $this->component;

        return [
            'module' => ['required', 'string', 'max:255'],
            'action' => [
                'required',
                'string',
                'max:255',
                // Uniqueness is on the (module, action) pair.
                Rule::unique('permissions', 'action')
                    ->where(fn ($query) => $query->where('module', $this->module))
                    ->ignore($component->editingId),
            ],
        ];
    }

    /** Hydrates the form from an existing Permission for the edit. */
    public function fromEntity(Permission $permission): void
    {
        $this->module = $permission->module();
        $this->action = $permission->action();
    }

    public function toDto(): PermissionDTO
    {
        return new PermissionDTO(module: $this->module, action: $this->action);
    }
}
