<?php

declare(strict_types=1);

namespace Tests\Unit\Shared;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Src\Shared\Attachment\Domain\Entities\Attachment;

/**
 * Tarea 7.6 del Plan de Trabajo.
 *
 * @dataProvider de docblock: phpunit/phpunit ^12 ya no reconoce metadatos
 * por comentario.
 */
final class AttachmentTest extends TestCase
{
    private const VALID_ARGS = [
        'uuid' => 'a1a1a1a1-0000-0000-0000-000000000000',
        'userId' => 1,
        'attachableType' => 'student_request',
        'attachableId' => 10,
        'documentType' => 'supporting_document',
        'originalName' => 'documento.pdf',
        'disk' => 'local',
        'path' => 'attachments/documento.pdf',
        'mimeType' => 'application/pdf',
        'sizeBytes' => 1024,
        'sha256Hash' => 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa',
    ];

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function make(array $overrides = []): Attachment
    {
        $args = array_replace(self::VALID_ARGS, $overrides);

        return Attachment::create(
            uuid: $args['uuid'],
            userId: $args['userId'],
            attachableType: $args['attachableType'],
            attachableId: $args['attachableId'],
            documentType: $args['documentType'],
            originalName: $args['originalName'],
            disk: $args['disk'],
            path: $args['path'],
            mimeType: $args['mimeType'],
            sizeBytes: $args['sizeBytes'],
            sha256Hash: $args['sha256Hash'],
        );
    }

    public function test_a_valid_attachment_is_created_without_error(): void
    {
        $attachment = $this->make();

        self::assertSame('application/pdf', $attachment->mimeType());
        self::assertSame(1024, $attachment->sizeBytes());
    }

    public function test_zero_bytes_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->make(['sizeBytes' => 0]);
    }

    public function test_negative_size_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->make(['sizeBytes' => -1]);
    }

    public function test_exactly_the_10mb_limit_is_accepted(): void
    {
        $attachment = $this->make(['sizeBytes' => Attachment::MAX_SIZE_BYTES]);

        self::assertSame(Attachment::MAX_SIZE_BYTES, $attachment->sizeBytes());
    }

    public function test_one_byte_over_the_10mb_limit_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->make(['sizeBytes' => Attachment::MAX_SIZE_BYTES + 1]);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function allowedMimeTypesProvider(): array
    {
        return [
            'PDF' => ['application/pdf'],
            'JPEG' => ['image/jpeg'],
            'PNG' => ['image/png'],
            'WebP' => ['image/webp'],
            'Word 97-2003 (.doc)' => ['application/msword'],
            'Word (.docx)' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
            'Excel 97-2003 (.xls)' => ['application/vnd.ms-excel'],
            'Excel (.xlsx)' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        ];
    }

    #[DataProvider('allowedMimeTypesProvider')]
    public function test_each_allowed_mime_type_is_accepted(string $mimeType): void
    {
        $attachment = $this->make(['mimeType' => $mimeType]);

        self::assertSame($mimeType, $attachment->mimeType());
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function rejectedMimeTypesProvider(): array
    {
        return [
            'executable' => ['application/x-msdownload'],
            'zip archive' => ['application/zip'],
            'plain text' => ['text/plain'],
            'legacy PowerPoint (not on the allow-list)' => ['application/vnd.ms-powerpoint'],
        ];
    }

    #[DataProvider('rejectedMimeTypesProvider')]
    public function test_each_disallowed_mime_type_is_rejected(string $mimeType): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->make(['mimeType' => $mimeType]);
    }
}
