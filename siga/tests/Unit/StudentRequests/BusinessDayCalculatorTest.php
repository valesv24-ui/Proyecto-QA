<?php

declare(strict_types=1);

namespace Tests\Unit\StudentRequests;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Src\Shared\Holiday\Domain\Services\EasterCalculator;
use Src\Shared\Holiday\Infrastructure\CostaRicaHolidayProvider;
use Src\StudentRequests\StudentRequest\Domain\Services\BusinessDayCalculator;

/** Tarea 5.5, criterio de aceptación literal. */
final class BusinessDayCalculatorTest extends TestCase
{
    private BusinessDayCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new BusinessDayCalculator(new CostaRicaHolidayProvider(new EasterCalculator));
    }

    public function test_a_holiday_pair_shifts_the_estimated_date_further_than_a_weekend_alone(): void
    {
        // Lunes 30 de marzo de 2026, 09:00.
        $from = new DateTimeImmutable('2026-03-30 09:00:00');

        $result = $this->calculator->addBusinessDays($from, 5);

        self::assertSame('2026-04-08', $result->format('Y-m-d'));
    }

    public function test_a_fixed_holiday_on_a_weekday_is_also_skipped(): void
    {
        // Viernes 1 de mayo de 2026 es el Día Internacional.
        $from = new DateTimeImmutable('2026-04-29 09:00:00'); // miércoles

        // Sin feriados: jue30, vie1(feriado->se salta), lun4, mar5.
        $result = $this->calculator->addBusinessDays($from, 5);

        self::assertSame('2026-05-07', $result->format('Y-m-d'));
        self::assertNotSame('2026-05-01', $result->format('Y-m-d'));
    }

    public function test_business_days_between_excludes_holidays_from_the_count(): void
    {
        // Del miércoles 1 al miércoles 8 de abril de 2026.
        $from = new DateTimeImmutable('2026-04-01 00:00:00');
        $to = new DateTimeImmutable('2026-04-08 00:00:00');

        self::assertSame(3, $this->calculator->businessDaysBetween($from, $to));
    }

    public function test_a_stretch_with_no_holidays_behaves_like_weekend_only_counting(): void
    {
        // Lunes 10 al lunes 17 de agosto de 2026.
        $from = new DateTimeImmutable('2026-08-10 09:00:00');

        $result = $this->calculator->addBusinessDays($from, 5);

        self::assertSame('2026-08-17', $result->format('Y-m-d'));
    }
}
