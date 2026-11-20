<?php

declare(strict_types=1);

namespace Src\Shared\Attachment\Infrastructure\Persistence\Repositories;

use App\Models\Attachment as AttachmentModel;
use Src\Shared\Attachment\Domain\Contracts\AttachmentRepositoryInterface;
use Src\Shared\Attachment\Domain\Entities\Attachment;

final class EloquentAttachmentRepository implements AttachmentRepositoryInterface
{
    public function find(int $id): ?Attachment
    {
        $model = AttachmentModel::query()->find($id);

        return $model ? $this->toDomain($model) : null;
    }

    public function findByUuid(string $uuid): ?Attachment
    {
        $model = AttachmentModel::query()->where('uuid', $uuid)->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function forAttachable(string $attachableType, int $attachableId): array
    {
        return AttachmentModel::query()
            ->where('attachable_type', $attachableType)
            ->where('attachable_id', $attachableId)
            ->get()
            ->map($this->toDomain(...))
            ->all();
    }

    public function save(Attachment $attachment): Attachment
    {
        $model = $attachment->id()
            ? AttachmentModel::query()->findOrFail($attachment->id())
            : new AttachmentModel;

        $model->uuid = $attachment->uuid();
        $model->user_id = $attachment->userId();
        $model->attachable_type = $attachment->attachableType();
        $model->attachable_id = $attachment->attachableId();
        $model->document_type = $attachment->documentType();
        $model->original_name = $attachment->originalName();
        $model->disk = $attachment->disk();
        $model->path = $attachment->path();
        $model->mime_type = $attachment->mimeType();
        $model->size_bytes = $attachment->sizeBytes();
        $model->sha256_hash = $attachment->sha256Hash();
        $model->save();

        return $this->toDomain($model);
    }

    public function delete(int $id): void
    {
        AttachmentModel::query()->whereKey($id)->delete();
    }

    private function toDomain(AttachmentModel $model): Attachment
    {
        return Attachment::reconstitute(
            id: $model->id,
            uuid: $model->uuid,
            userId: $model->user_id,
            attachableType: $model->attachable_type,
            attachableId: $model->attachable_id,
            documentType: $model->document_type,
            originalName: $model->original_name,
            disk: $model->disk,
            path: $model->path,
            mimeType: $model->mime_type,
            sizeBytes: $model->size_bytes,
            sha256Hash: $model->sha256_hash,
        );
    }
}
