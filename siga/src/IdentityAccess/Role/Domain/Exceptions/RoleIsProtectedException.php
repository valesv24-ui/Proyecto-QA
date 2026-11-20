<?php

declare(strict_types=1);

namespace Src\IdentityAccess\Role\Domain\Exceptions;

use DomainException;

/** Se lanza al intentar renombrar o eliminar uno. */
final class RoleIsProtectedException extends DomainException
{
    public static function forName(string $name): self
    {
        return new self("El rol «{$name}» es parte del sistema y no se puede renombrar ni eliminar.");
    }
}
