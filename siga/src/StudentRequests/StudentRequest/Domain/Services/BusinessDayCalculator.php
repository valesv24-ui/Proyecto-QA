<?php

declare(strict_types=1);

namespace Src\StudentRequests\StudentRequest\Domain\Services;

use DateTimeImmutable;
use Src\Shared\Holiday\Domain\Contracts\HolidayProviderInterface;

/** Aritmética de días hábiles. */
final class BusinessDayCalculator
{
    /**
     * ISO-8601: 6 = sábado, 7 = domingo.
     *
     * @var array<int, int>
     */
    private const WEEKEND = [6, 7];

    public function __construct(
        private readonly HolidayProviderInterface $holidays,
    ) {}

    public function addBusinessDays(DateTimeImmutable $from, int $days): DateTimeImmutable
    {
        $date = $from;
        $added = 0;

        while ($added < $days) {
            $date = $date->modify('+1 day');
            if (! $this->isNonBusinessDay($date)) {
                $added++;
            }
        }

        return $date;
    }

    /** Días hábiles entre dos fechas, con signo. */
    public function businessDaysBetween(DateTimeImmutable $from, DateTimeImmutable $to): int
    {
        $start = $from->setTime(0, 0);
        $end = $to->setTime(0, 0);

        if ($start == $end) {
            return 0;
        }

        $forward = $start < $end;
        $cursor = $forward ? $start : $end;
        $limit = $forward ? $end : $start;
        $count = 0;

        while ($cursor < $limit) {
            $cursor = $cursor->modify('+1 day');
            if (! $this->isNonBusinessDay($cursor)) {
                $count++;
            }
        }

        return $forward ? $count : -$count;
    }

    private function isNonBusinessDay(DateTimeImmutable $date): bool
    {
        return $this->isWeekend($date) || $this->holidays->isHoliday($date);
    }

    private function isWeekend(DateTimeImmutable $date): bool
    {
        return in_array((int) $date->format('N'), self::WEEKEND, true);
    }
}
