<?php

declare(strict_types=1);

namespace Tests\Unit\StudentRequests;

use PHPUnit\Framework\TestCase;
use Src\StudentRequests\TransferCreditPrecedent\Domain\Services\CourseNameNormalizer;

/** ES-02 / tarea 4.1 y 7.5. */
final class CourseNameNormalizerTest extends TestCase
{
    private CourseNameNormalizer $normalizer;

    protected function setUp(): void
    {
        $this->normalizer = new CourseNameNormalizer;
    }

    public function test_the_exact_example_from_the_acceptance_criterion_matches(): void
    {
        self::assertSame(
            $this->normalizer->normalize('Cálculo I'),
            $this->normalizer->normalize('calculo 1'),
        );
    }

    public function test_case_differences_are_ignored(): void
    {
        self::assertSame(
            $this->normalizer->normalize('Universidad de Costa Rica'),
            $this->normalizer->normalize('UNIVERSIDAD DE COSTA RICA'),
        );
    }

    public function test_accents_and_the_spanish_ene_are_folded(): void
    {
        self::assertSame(
            $this->normalizer->normalize('Diseño y Programación'),
            $this->normalizer->normalize('Diseno y Programacion'),
        );
    }

    public function test_leading_trailing_and_repeated_whitespace_is_collapsed(): void
    {
        self::assertSame(
            $this->normalizer->normalize('Física   III'),
            $this->normalizer->normalize('  Fisica III  '),
        );
    }

    public function test_roman_numerals_up_to_x_convert_to_arabic(): void
    {
        self::assertSame($this->normalizer->normalize('Curso II'), $this->normalizer->normalize('curso 2'));
        self::assertSame($this->normalizer->normalize('Curso IX'), $this->normalizer->normalize('curso 9'));
        self::assertSame($this->normalizer->normalize('Curso X'), $this->normalizer->normalize('curso 10'));
    }

    public function test_a_roman_numeral_inside_a_longer_word_is_not_touched(): void
    {
        // "Civil" no debe convertirse porque contenga una "i".
        self::assertSame('ingenieria civil', $this->normalizer->normalize('Ingeniería Civil'));
    }

    public function test_genuinely_different_names_do_not_match(): void
    {
        self::assertNotSame(
            $this->normalizer->normalize('Cálculo I'),
            $this->normalizer->normalize('Cálculo II'),
        );
    }
}
