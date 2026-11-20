<?php

namespace Database\Factories;

use App\Models\StudentRequest;
use App\Models\StudentRequestStatusHistory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudentRequestStatusHistory>
 */
class StudentRequestStatusHistoryFactory extends Factory
{
    protected $model = StudentRequestStatusHistory::class;

    public function definition(): array
    {
        return [
            'student_request_id' => StudentRequest::factory(),
            'previous_status' => null,
            'new_status' => 'Pendiente de revisión',
            'comment' => null,
            'user_id' => null,
            'notified_at' => null,
        ];
    }
}
