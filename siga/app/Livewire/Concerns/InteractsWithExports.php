<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use Src\Shared\Export\Contracts\ExcelExporterInterface;
use Src\Shared\Export\Contracts\PdfExporterInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Turns the export ports (Src\Shared\Export\Contracts\*) into two ready-to-call helpers any. */
trait InteractsWithExports
{
    /**
     * @param  array<int, array{key: string, label: string, format?: callable}>  $headers
     * @param  iterable<array<string, mixed>>  $rows
     */
    protected function streamExcel(array $headers, iterable $rows, string $filename, ExcelExporterInterface $exporter): StreamedResponse
    {
        return $exporter->streamDownload($this->mapRowsForExport($headers, $rows), $filename);
    }

    /**
     * @param  array<int, array{key: string, label: string, format?: callable}>  $headers
     * @param  iterable<array<string, mixed>>  $rows
     * @param  string  $paperSize  Passed straight through to PdfExporterInterface — see that contract for why it.
     */
    protected function streamPdf(string $title, array $headers, iterable $rows, string $filename, PdfExporterInterface $exporter, string $paperSize = 'a4'): StreamedResponse
    {
        $html = view('exports.table-pdf', [
            'title' => $title,
            'headers' => $headers,
            'rows' => $this->mapRowsForExport($headers, $rows),
        ])->render();

        return $exporter->fromHtml($html, $filename, $paperSize);
    }

    /**
     * Projects each row down to just the exportable columns.
     *
     * @param  array<int, array{key: string, label: string, format?: callable}>  $headers
     * @param  iterable<array<string, mixed>>  $rows
     * @return \Generator<int, array<string, mixed>>
     */
    private function mapRowsForExport(array $headers, iterable $rows): \Generator
    {
        foreach ($rows as $row) {
            $mapped = [];

            foreach ($headers as $header) {
                $value = $row[$header['key']] ?? '';
                $mapped[$header['label']] = isset($header['format']) ? ($header['format'])($value) : $value;
            }

            yield $mapped;
        }
    }
}
