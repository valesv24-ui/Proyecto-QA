<?php

declare(strict_types=1);

namespace Src\Shared\Export\Infrastructure;

use Spatie\SimpleExcel\SimpleExcelWriter;
use Src\Shared\Export\Contracts\ExcelExporterInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Streams rows to the browser via spatie/simple-excel. */
final class SpatieExcelExporter implements ExcelExporterInterface
{
    /** Flush PHP's output buffer periodically so bytes reach the browser. */
    private const FLUSH_EVERY = 1000;

    public function streamDownload(iterable $rows, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($rows, $filename): void {
            $writer = SimpleExcelWriter::streamDownload($filename);

            $count = 0;

            foreach ($rows as $row) {
                $writer->addRow($row);

                if (++$count % self::FLUSH_EVERY === 0) {
                    flush();
                }
            }

            $writer->close();
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
