<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use Livewire\Attributes\Url;

/** Generic pagination/sorting/search state shared by every CRUD data-table component. */
trait InteractsWithDataTable
{
    #[Url(as: 'q', history: true)]
    public string $search = '';

    public int $perPage = 10;

    public int $page = 1;

    public string $sortKey = '';

    public string $sortDir = 'asc';

    /** Exposed to the Blade view so it can pick which. */
    public function tableMode(): string
    {
        return $this->tableMode;
    }

    public function isServerMode(): bool
    {
        return $this->tableMode() === 'server';
    }

    public function isClientMode(): bool
    {
        return ! $this->isServerMode();
    }

    /** Server mode only. */
    public function updatingSearch(): void
    {
        $this->page = 1;
    }

    public function updatingPerPage(): void
    {
        $this->page = 1;
    }

    /** Server mode only. */
    public function sort(string $key): void
    {
        $this->sortDir = $this->sortKey === $key && $this->sortDir === 'asc' ? 'desc' : 'asc';
        $this->sortKey = $key;
        $this->page = 1;
    }

    public function previousPage(): void
    {
        $this->page = max(1, $this->page - 1);
    }

    public function nextPage(): void
    {
        $this->page++;
    }

    public function gotoPage(int $page): void
    {
        $this->page = max(1, $page);
    }

    /**
     * Client mode only.
     *
     * @param  array<int, array<string, mixed>>  $rows
     */
    public function refreshTable(array $rows): void
    {
        if ($this->isClientMode()) {
            $this->dispatch('data-table-refresh', rows: $rows);
        }
    }
}
