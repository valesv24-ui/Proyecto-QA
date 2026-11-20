<?php

declare(strict_types=1);

namespace Src\Shared\Export\Infrastructure;

use Spatie\Browsershot\Browsershot;
use Spatie\LaravelPdf\Facades\Pdf;
use Src\Shared\Export\Contracts\PdfExporterInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Renders HTML to PDF via spatie/laravel-pdf. */
final class SpatiePdfExporter implements PdfExporterInterface
{
    public function fromHtml(string $html, string $filename, string $paperSize = 'a4'): StreamedResponse
    {
        $pdfBytes = $this->bytesFromHtml($html, $paperSize);

        // We already have the complete PDF in memory at this.
        return response()->streamDownload(function () use ($pdfBytes): void {
            echo $pdfBytes;
        }, $filename, [
            'Content-Type' => 'application/pdf',
            'Content-Length' => (string) strlen($pdfBytes),
        ]);
    }

    public function bytesFromHtml(string $html, string $paperSize = 'a4'): string
    {
        return Pdf::html($html)
            ->format($paperSize)
            ->withBrowsershot(function (Browsershot $browsershot): void {
                $browsershot
                    ->setNodeModulePath(base_path('node_modules'))
                    ->timeout(15)
                    ->showBackground()
                    ->addChromiumArguments([
                        'disable-extensions',
                        'disable-background-networking',
                        'disable-default-apps',
                        'disable-sync',
                        'disable-translate',
                        'metrics-recording-only',
                        'mute-audio',
                        'no-first-run',
                        'safebrowsing-disable-auto-update',
                        'disable-gpu',
                        'disable-dev-shm-usage',
                        'disable-software-rasterizer',
                    ]);
            })
            ->generatePdfContent();
    }
}
