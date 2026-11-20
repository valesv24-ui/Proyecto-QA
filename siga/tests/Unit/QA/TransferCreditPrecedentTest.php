<?php

declare(strict_types=1);

namespace Tests\Unit\QA;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Src\StudentRequests\TransferCreditPrecedent\Domain\Entities\TransferCreditPrecedent;

/**
 * Proyecto QA ISW-622 — Pruebas unitarias (Persona A).
 *
 * Clase bajo prueba: TransferCreditPrecedent (convalidaciones aprobadas o denegadas antes, ES-02).
 * Técnica: partición de equivalencia sobre el resultado.
 */
final class TransferCreditPrecedentTest extends TestCase
{
    // PU-TP-01
    public function test_un_precedente_aprobado_se_reconoce_como_aprobado(): void
    {
        $precedent = TransferCreditPrecedent::create('UCR', 'Cálculo I', 10, 'Aprobada', 'RES-2025-14');

        $this->assertTrue($precedent->isApproved());
        $this->assertSame('UCR', $precedent->institution());
        $this->assertSame('Cálculo I', $precedent->externalCourse());
        $this->assertSame(10, $precedent->courseId());
        $this->assertSame('RES-2025-14', $precedent->resolutionNumber());
    }

    // PU-TP-02
    public function test_un_precedente_denegado_no_se_reconoce_como_aprobado(): void
    {
        $precedent = TransferCreditPrecedent::create('UCR', 'Cálculo I', 10, 'Denegada', 'RES-2025-15');

        $this->assertFalse($precedent->isApproved());
    }

    // PU-TP-03
    public function test_rechaza_un_resultado_desconocido(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Resultado inválido: Pendiente');

        TransferCreditPrecedent::create('UCR', 'Cálculo I', 10, 'Pendiente', 'RES-2025-16');
    }
}
