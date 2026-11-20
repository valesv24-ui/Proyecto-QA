<?php

declare(strict_types=1);

namespace Src\Shared\Holiday\Domain\Contracts;

use DateTimeImmutable;

/** Puerto para "¿es este día un feriado?". */
interface HolidayProviderInterface
{
    public function isHoliday(DateTimeImmutable $date): bool;
}
