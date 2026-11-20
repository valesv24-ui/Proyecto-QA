<?php

declare(strict_types=1);

namespace Src\Shared\Export\Contracts;

use Symfony\Component\HttpFoundation\StreamedResponse;

/** Port for rendering HTML to a downloadable PDF. Takes. */
interface PdfExporterInterface
{
    /**
     * @param  string  $paperSize  Any size the underlying renderer accepts.
     */
    public function fromHtml(string $html, string $filename, string $paperSize = 'a4'): StreamedResponse;

    /** Los bytes del PDF. */
    public function bytesFromHtml(string $html, string $paperSize = 'a4'): string;
}
