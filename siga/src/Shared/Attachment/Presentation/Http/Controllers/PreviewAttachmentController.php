<?php

declare(strict_types=1);

namespace Src\Shared\Attachment\Presentation\Http\Controllers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Src\Shared\Attachment\Application\UseCases\FindAttachmentUseCase;
use Src\StudentRequests\StudentRequest\Application\UseCases\FindStudentRequestUseCase;
use Src\StudentRequests\StudentRequest\Domain\Entities\StudentRequest;
use Src\StudentRequests\TransferCreditPrecedent\Application\UseCases\FindTransferCreditPrecedentUseCase;
use Src\StudentRequests\TransferCreditPrecedent\Domain\Entities\TransferCreditPrecedent;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Previsualización EN LÍNEA (no descarga) de un documento adjunto. */
final class PreviewAttachmentController
{
    public function __invoke(
        int $attachment,
        FindAttachmentUseCase $findAttachment,
        FindStudentRequestUseCase $findStudentRequest,
        FindTransferCreditPrecedentUseCase $findPrecedent,
    ): StreamedResponse {
        $file = $findAttachment->handle($attachment);

        $owner = match ($file->attachableType()) {
            StudentRequest::class => $findStudentRequest->handle($file->attachableId()),
            TransferCreditPrecedent::class => $findPrecedent->handle($file->attachableId()),
            default => abort(404),
        };

        Gate::authorize('view', $owner);

        // >response() y no ->download(): manda Content-Disposition: inline.
        return Storage::disk($file->disk())->response($file->path(), $file->originalName());
    }
}
