<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\WaiverRule;
use Illuminate\Database\Eloquent\Factories\Factory;
use Src\StudentRequests\WaiverRule\Domain\Entities\WaiverRule as WaiverRuleEntity;

/**
 * @extends Factory<WaiverRule>
 */
class WaiverRuleFactory extends Factory
{
    protected $model = WaiverRule::class;

    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'evaluation_order' => 1,
            // El caso especial es el más neutro para una regla.
            'type' => WaiverRuleEntity::TYPE_SPECIAL_CASE,
            'is_active' => true,
        ];
    }

    /** Regla que admite una justificación concreta de la boleta. */
    public function admitting(string $justification): static
    {
        return $this->state(fn (): array => ['type' => $justification]);
    }
}
