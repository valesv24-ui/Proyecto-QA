<?php

declare(strict_types=1);

namespace Src\Shared\Attachment\Domain\Exceptions;

use DomainException;

final class AttachmentNotFoundException extends DomainException
{
    public static function withId(int $id): self
    {
        return new self("Attachment with id [{$id}] was not found.");
    }
}
