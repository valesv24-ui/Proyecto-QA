<?php

declare(strict_types=1);

namespace Src\IdentityAccess\Jwt\Domain\Exceptions;

use RuntimeException;
use Throwable;

/** Envuelve cualquier motivo por el que un token. */
final class InvalidTokenException extends RuntimeException
{
    public static function becauseOf(Throwable $previous): self
    {
        return new self('Token JWT inválido o expirado.', previous: $previous);
    }
}
