<?php

declare(strict_types=1);

namespace Src\Shared\Export\Contracts;

use Symfony\Component\HttpFoundation\StreamedResponse;

/** Port for streaming tabular data to the browser as. */
interface ExcelExporterInterface
{
    /**
     * @param  iterable<array<string, scalar|null>>  $rows  Cada elemento es una fila; las claves de la primera son el encabezado.
     */
    public function streamDownload(iterable $rows, string $filename): StreamedResponse;
}
