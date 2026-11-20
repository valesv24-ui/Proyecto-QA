<?php

declare(strict_types=1);

namespace Src\Shared\Persistence\Infrastructure\Concerns;

/** The same two-line "resolve $sortBy/$sortDir against an allow-list" block was. */
trait ResolvesSortOrder
{
    /**
     * @param  array<int, string>  $allowList
     *
     * $sortBy is client-controllable.
     * orderBy()), so falling back to $default for anything not on the.
     * allow-list is not optional hardening.
     */
    private function resolveSortColumn(?string $sortBy, array $allowList, string $default): string
    {
        return in_array($sortBy, $allowList, true) ? $sortBy : $default;
    }

    /**
     * El tipo declarado —string a secas.
     *
     * @return 'asc'|'desc'
     */
    private function resolveSortDirection(string $sortDir): string
    {
        return $sortDir === 'desc' ? 'desc' : 'asc';
    }
}
