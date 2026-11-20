<?php

declare(strict_types=1);

namespace Src\Shared\Clock\Domain\Contracts;

use DateTimeImmutable;

/** Puerto para "qué hora es ahora mismo". */
interface ClockInterface
{
    public function now(): DateTimeImmutable;
}
