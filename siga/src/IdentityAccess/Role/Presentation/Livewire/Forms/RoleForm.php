<?php

declare(strict_types=1);

namespace Src\IdentityAccess\Role\Presentation\Livewire\Forms;

use Illuminate\Validation\Rule;
use Livewire\Form;
use Src\IdentityAccess\Role\Application\DTOs\RoleDTO;
use Src\IdentityAccess\Role\Domain\Entities\Role;
use Src\IdentityAccess\Role\Presentation\Livewire\RoleComponent;

/** Livewire Form Object. */
class RoleForm extends Form
{
    public string $name = '';

    /** @var array<int, string> */
    public array $permissions = [];

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var RoleComponent $component */
        $component = $this->component;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('roles', 'name')->ignore($component->editingId),
            ],
            'permissions' => ['array'],
            'permissions.*' => ['string'],
        ];
    }

    /** Hydrates the form from an existing Role for the edit. */
    public function fromEntity(Role $role): void
    {
        $this->name = $role->name();
        $this->permissions = $role->permissions();
    }

    public function toDto(): RoleDTO
    {
        return new RoleDTO(name: $this->name, permissions: $this->permissions);
    }
}
