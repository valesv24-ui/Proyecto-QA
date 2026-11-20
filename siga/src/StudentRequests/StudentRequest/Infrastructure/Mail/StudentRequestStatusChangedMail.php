<?php

declare(strict_types=1);

namespace Src\StudentRequests\StudentRequest\Infrastructure\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Src\StudentRequests\StudentRequest\Domain\Entities\StudentRequest;

/** Los DOS correos que recibe el estudiante en todo. */
final class StudentRequestStatusChangedMail extends Mailable
{
    public function __construct(
        public readonly int $requestId,
        public readonly string $studentName,
        public readonly string $type,
        public readonly string $courseLabel,
        /** true = acuse de recibo; false = resolución publicada. */
        public readonly bool $submitted,
        public readonly string $publicStatus,
        public readonly ?string $estimatedResolutionDate,
        public readonly ?string $statement,
        public readonly ?string $justification,
        /** Ruta en el disco `local` del RSREC-001 archivado, o null. */
        public readonly ?string $resolutionPath = null,
    ) {}

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        if ($this->resolutionPath === null) {
            return [];
        }

        return [
            Attachment::fromStorageDisk('local', $this->resolutionPath)
                ->as('RSREC-001.pdf')
                ->withMime('application/pdf'),
        ];
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->submitted
                ? __('We received your student request')
                : __('Resolution of your student request — :status', ['status' => $this->publicStatus]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.student-requests.status-changed',
            with: [
                'typeLabel' => $this->type === StudentRequest::TYPE_WAIVER
                    ? __('requirement waiver')
                    : __('course transfer credit'),
            ],
        );
    }
}
