<?php

declare(strict_types=1);

namespace Src\StudentRequests\StudentRequest\Presentation\Http\Controllers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Src\Shared\Attachment\Application\UseCases\FindAttachmentUseCase;
use Src\StudentRequests\StudentRequest\Application\UseCases\FindStudentRequestUseCase;
use Src\StudentRequests\TransferCreditPrecedent\Domain\Entities\TransferCreditPrecedent;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Previsualización de la resolución del PRECEDENTE que respalda una convalidación. */
final class PreviewPrecedentDocumentController
{
    public function __invoke(
        int $studentRequest,
        int $attachment,
        FindStudentRequestUseCase $findRequest,
        FindAttachmentUseCase $findAttachment,
    ): StreamedResponse {
        $request = $findRequest->handle($studentRequest);

        Gate::authorize('view', $request);

        $file = $findAttachment->handle($attachment);

        $belongsToThisRequestPrecedent = $file->attachableType() === TransferCreditPrecedent::class
            && $request->transferCreditPrecedentId() !== null
            && $file->attachableId() === $request->transferCreditPrecedentId();

        if (! $belongsToThisRequestPrecedent) {
            abort(404);
        }

        // >response() y no ->download(): Content-Disposition: inline.
        return Storage::disk($file->disk())->response($file->path(), $file->originalName());
    }
}
