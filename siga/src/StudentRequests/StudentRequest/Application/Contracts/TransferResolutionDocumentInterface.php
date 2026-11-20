<?php

declare(strict_types=1);

namespace Src\StudentRequests\StudentRequest\Application\Contracts;

interface TransferResolutionDocumentInterface
{
    /** Emite el RSREC-001 de una convalidación y lo deja guardado. */
    public function issueFor(int $studentRequestId, ?int $issuedByUserId): void;
}
