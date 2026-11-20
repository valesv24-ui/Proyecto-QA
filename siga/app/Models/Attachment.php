<?php

namespace App\Models;

use Database\Factories\AttachmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Documento adjunto.
 *
 * @property int $id
 * @property string $uuid
 * @property int|null $user_id
 * @property string $attachable_type
 * @property int $attachable_id
 * @property string $document_type
 * @property string $original_name
 * @property string $disk
 * @property string $path
 * @property string $mime_type
 * @property int $size_bytes
 * @property string $sha256_hash
 * @property-read User|null $user
 * @property-read Model $attachable
 */
#[Fillable(['uuid', 'user_id', 'attachable_type', 'attachable_id', 'document_type', 'original_name', 'disk', 'path', 'mime_type', 'size_bytes', 'sha256_hash'])]
class Attachment extends Model
{
    /** @use HasFactory<AttachmentFactory> */
    use HasFactory;

    protected $casts = [
        'size_bytes' => 'integer',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }
}
