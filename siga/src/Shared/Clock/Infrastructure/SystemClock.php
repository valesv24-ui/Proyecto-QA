<?php

declare(strict_types=1);

namespace Src\Shared\Clock\Infrastructure;

use DateTimeImmutable;
use Src\Shared\Clock\Domain\Contracts\ClockInterface;

/** Adaptador real de ClockInterface. */
final class SystemClock implements ClockInterface
{
    public function now(): DateTimeImmutable
    {
        return new DateTimeImmutable;
    }
}
