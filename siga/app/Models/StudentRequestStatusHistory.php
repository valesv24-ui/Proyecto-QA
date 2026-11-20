<?php

namespace App\Models;

use Database\Factories\StudentRequestStatusHistoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Historial de cambios de estado de una solicitud (ES-03).
 *
 * @property int $id
 * @property int $student_request_id
 * @property string|null $previous_status
 * @property string $new_status
 * @property string|null $comment
 * @property int|null $user_id
 * @property Carbon|null $notified_at
 * @property-read StudentRequest $studentRequest
 * @property-read User|null $user
 */
#[Fillable(['student_request_id', 'previous_status', 'new_status', 'comment', 'user_id', 'notified_at'])]
class StudentRequestStatusHistory extends Model
{
    /** @use HasFactory<StudentRequestStatusHistoryFactory> */
    use HasFactory;

    /** Se declara explícitamente porque la convención de Laravel NO acierta. */
    protected $table = 'student_request_status_history';

    public $timestamps = false;

    protected $casts = [
        'notified_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<StudentRequest, $this>
     */
    public function studentRequest(): BelongsTo
    {
        return $this->belongsTo(StudentRequest::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
