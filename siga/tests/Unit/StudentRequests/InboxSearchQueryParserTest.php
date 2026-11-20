<?php

declare(strict_types=1);

namespace Tests\Unit\StudentRequests;

use PHPUnit\Framework\TestCase;
use Src\StudentRequests\StudentRequest\Application\Support\InboxSearchQueryParser;

/** Tarea 6.2 del Plan de Trabajo. */
final class InboxSearchQueryParserTest extends TestCase
{
    public function test_plain_text_with_no_keywords_is_returned_as_search(): void
    {
        self::assertSame(['search' => 'Jose Perez'], InboxSearchQueryParser::parse('Jose Perez'));
    }

    public function test_a_single_keyword_produces_only_that_filter(): void
    {
        self::assertSame(['status' => 'aprobada'], InboxSearchQueryParser::parse('estado:aprobada'));
    }

    public function test_the_five_keywords_are_combinable_in_one_term(): void
    {
        $result = InboxSearchQueryParser::parse(
            'tipo:beca estado:aprobada carrera:ISW desde:2026-08-01 hasta:2026-08-31'
        );

        self::assertSame([
            'type' => 'beca',
            'status' => 'aprobada',
            'career' => 'ISW',
            'dateFrom' => '2026-08-01',
            'dateTo' => '2026-08-31',
        ], $result);
    }

    public function test_a_quoted_value_keeps_its_internal_spaces(): void
    {
        self::assertSame(
            ['career' => 'Ingeniería en Sistemas', 'search' => 'jose'],
            InboxSearchQueryParser::parse('carrera:"Ingeniería en Sistemas" jose'),
        );
    }

    public function test_free_text_around_a_filter_is_preserved_as_search(): void
    {
        self::assertSame(
            ['status' => 'aprobada', 'search' => 'jose perez'],
            InboxSearchQueryParser::parse('jose estado:aprobada perez'),
        );
    }

    public function test_a_malformed_date_is_left_as_free_text_instead_of_silently_dropped(): void
    {
        self::assertSame(['search' => 'desde:no-es-fecha'], InboxSearchQueryParser::parse('desde:no-es-fecha'));
    }

    public function test_a_calendar_impossible_date_is_rejected(): void
    {
        // 30 de febrero no existe.
        self::assertSame(['search' => 'desde:2026-02-30'], InboxSearchQueryParser::parse('desde:2026-02-30'));
    }

    public function test_a_keyword_with_no_value_is_ignored(): void
    {
        self::assertSame(['search' => 'tipo: jose'], InboxSearchQueryParser::parse('tipo: jose'));
    }

    public function test_a_repeated_keyword_keeps_the_last_occurrence(): void
    {
        self::assertSame(['type' => 'convalidacion'], InboxSearchQueryParser::parse('tipo:beca tipo:convalidacion'));
    }

    public function test_keywords_are_case_insensitive(): void
    {
        self::assertSame(['status' => 'Aprobada'], InboxSearchQueryParser::parse('ESTADO:Aprobada'));
    }

    /** "Abiertas" no es un estado de la enumeración. */
    public function test_open_aliases_become_a_flag_instead_of_a_status(): void
    {
        self::assertSame(['openOnly' => true], InboxSearchQueryParser::parse('estado:abiertas'));
        self::assertSame(['openOnly' => true], InboxSearchQueryParser::parse('estado:sin-resolver'));
        self::assertSame(['openOnly' => true], InboxSearchQueryParser::parse('ESTADO:Abierta'));
    }

    public function test_the_open_flag_combines_with_a_date_like_the_backlog_card_does(): void
    {
        // Exactamente lo que escribe el botón "Filtrar por esta fecha".
        self::assertSame(
            ['openOnly' => true, 'search' => '2026-08-14'],
            InboxSearchQueryParser::parse('estado:abiertas 2026-08-14'),
        );
    }

    public function test_a_real_status_is_still_a_status(): void
    {
        self::assertSame(['status' => 'Aprobada'], InboxSearchQueryParser::parse('estado:Aprobada'));
    }

    public function test_an_empty_string_produces_no_filters_at_all(): void
    {
        self::assertSame([], InboxSearchQueryParser::parse(''));
    }

    public function test_surrounding_whitespace_never_leaks_into_search(): void
    {
        self::assertSame(['status' => 'aprobada'], InboxSearchQueryParser::parse('  estado:aprobada   '));
    }
}
