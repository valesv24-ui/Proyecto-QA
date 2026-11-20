<?php

declare(strict_types=1);

namespace Tests\Unit\QA;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Src\StudentRequests\StudentRequest\Domain\Entities\RequestedCourse;

/**
 * Proyecto QA ISW-622 — Pruebas unitarias (Persona A).
 *
 * Clase bajo prueba: RequestedCourse (curso dentro de una solicitud de convalidación).
 * Técnicas: partición de equivalencia, valores límite y transición de estados.
 */
final class RequestedCourseTest extends TestCase
{
    private function course(): RequestedCourse
    {
        return RequestedCourse::requested(10, 'Universidad de Costa Rica', 'Cálculo I');
    }

    // PU-RC-01
    public function test_un_curso_nuevo_inicia_en_estado_pendiente(): void
    {
        $course = $this->course();

        $this->assertSame(RequestedCourse::OUTCOME_PENDING, $course->outcome());
        $this->assertFalse($course->isResolved());
        $this->assertFalse($course->isRecognized());
        $this->assertNull($course->id());
        $this->assertNull($course->denialReason());
    }

    // PU-RC-02
    public function test_elimina_espacios_en_institucion_y_nombre_del_curso(): void
    {
        $course = RequestedCourse::requested(10, '  UCR  ', '  Cálculo I  ');

        $this->assertSame('UCR', $course->externalInstitution());
        $this->assertSame('Cálculo I', $course->externalCourseName());
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function datosExternosIncompletos(): array
    {
        return [
            'institución vacía' => ['', 'Cálculo I'],
            'institución solo espacios' => ['   ', 'Cálculo I'],
            'nombre de curso vacío' => ['UCR', ''],
            'nombre de curso solo espacios' => ['UCR', '   '],
        ];
    }

    // PU-RC-03
    #[DataProvider('datosExternosIncompletos')]
    public function test_rechaza_cursos_sin_institucion_o_nombre(string $institution, string $courseName): void
    {
        $this->expectException(InvalidArgumentException::class);

        RequestedCourse::requested(10, $institution, $courseName);
    }

    /**
     * @return array<string, array{int}>
     */
    public static function creditosValidos(): array
    {
        return [
            'límite inferior (0)' => [0],
            'valor típico (4)' => [4],
            'límite superior (30)' => [30],
        ];
    }

    // PU-RC-04
    #[DataProvider('creditosValidos')]
    public function test_acepta_creditos_dentro_del_rango(int $credits): void
    {
        $course = $this->course();

        $course->completeExternalData('MA-1001', $credits);

        $this->assertSame($credits, $course->externalCredits());
    }

    /**
     * @return array<string, array{int}>
     */
    public static function creditosInvalidos(): array
    {
        return [
            'debajo del límite (-1)' => [-1],
            'encima del límite (31)' => [31],
        ];
    }

    // PU-RC-05
    #[DataProvider('creditosInvalidos')]
    public function test_rechaza_creditos_fuera_del_rango(int $credits): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Los créditos del curso externo deben estar entre 0 y 30.');

        $this->course()->completeExternalData('MA-1001', $credits);
    }

    /**
     * @return array<string, array{float}>
     */
    public static function notasValidas(): array
    {
        return [
            'límite inferior (0)' => [0.0],
            'nota con decimales (85.5)' => [85.5],
            'límite superior (100)' => [100.0],
        ];
    }

    // PU-RC-06
    #[DataProvider('notasValidas')]
    public function test_acepta_notas_dentro_del_rango(float $grade): void
    {
        $course = $this->course();

        $course->completeExternalData('MA-1001', 4, $grade);

        $this->assertSame($grade, $course->externalGrade());
    }

    /**
     * @return array<string, array{float}>
     */
    public static function notasInvalidas(): array
    {
        return [
            'debajo del límite (-0.1)' => [-0.1],
            'encima del límite (100.1)' => [100.1],
        ];
    }

    // PU-RC-07
    #[DataProvider('notasInvalidas')]
    public function test_rechaza_notas_fuera_del_rango(float $grade): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('La nota del curso externo debe estar entre 0 y 100.');

        $this->course()->completeExternalData('MA-1001', 4, $grade);
    }

    // PU-RC-08
    public function test_un_codigo_vacio_se_guarda_como_nulo(): void
    {
        $course = $this->course();

        $course->completeExternalData('   ', 4);

        $this->assertNull($course->externalCourseCode());
    }

    // PU-RC-09
    public function test_reconocer_un_curso_lo_marca_como_resuelto_y_reconocido(): void
    {
        $course = $this->course();

        $course->resolve(RequestedCourse::OUTCOME_RECOGNIZED);

        $this->assertTrue($course->isResolved());
        $this->assertTrue($course->isRecognized());
        $this->assertNull($course->denialReason());
    }

    // PU-RC-10
    public function test_reconocer_un_curso_descarta_la_razon_de_rechazo(): void
    {
        $course = $this->course();

        $course->resolve(RequestedCourse::OUTCOME_RECOGNIZED, 'Texto que no aplica');

        $this->assertNull($course->denialReason());
    }

    // PU-RC-11
    public function test_rechazar_un_curso_guarda_la_razon_sin_espacios(): void
    {
        $course = $this->course();

        $course->resolve(RequestedCourse::OUTCOME_REJECTED, '  Contenidos no equivalentes  ');

        $this->assertTrue($course->isResolved());
        $this->assertFalse($course->isRecognized());
        $this->assertSame('Contenidos no equivalentes', $course->denialReason());
    }

    /**
     * @return array<string, array{?string}>
     */
    public static function razonesVacias(): array
    {
        return [
            'sin razón (null)' => [null],
            'razón vacía' => [''],
            'razón solo espacios' => ['   '],
        ];
    }

    // PU-RC-12
    #[DataProvider('razonesVacias')]
    public function test_rechazar_un_curso_exige_una_razon(?string $reason): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Un curso no reconocido exige indicar la razón.');

        $this->course()->resolve(RequestedCourse::OUTCOME_REJECTED, $reason);
    }

    // PU-RC-13
    public function test_no_se_puede_devolver_un_curso_a_pendiente(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->course()->resolve(RequestedCourse::OUTCOME_PENDING);
    }

    // PU-RC-14
    public function test_reconstruir_con_un_resultado_desconocido_falla(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Resultado de curso inválido: Aprobado');

        RequestedCourse::reconstitute(1, 10, 'UCR', 'Cálculo I', null, null, null, 'Aprobado', null);
    }
}
