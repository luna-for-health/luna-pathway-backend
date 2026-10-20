<?php

namespace Database\Factories;

use App\Enums\ConsultationRequestStatus;
use App\Models\ConsultationRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ConsultationRequest>
 */
class ConsultationRequestFactory extends Factory
{
    protected $model = ConsultationRequest::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'reason' => fake()->sentence(3),
            'history' => fake()->optional()->paragraph(),
            'status' => ConsultationRequestStatus::New,
            'dysmenorrhea' => fake()->numberBetween(0, 10),
            'pelvic_pain' => fake()->numberBetween(0, 10),
            'dyspareunia' => fake()->numberBetween(0, 10),
            'digestive_issues' => fake()->numberBetween(0, 10),
            'urinary_issues' => fake()->numberBetween(0, 10),
            'fatigue' => fake()->numberBetween(0, 10),
            'has_mri' => fake()->boolean(),
        ];
    }

    public function reminder(): static
    {
        return $this->state(fn (array $attributes) => ['status' => ConsultationRequestStatus::Reminder]);
    }

    public function validated(): static
    {
        return $this->state(fn (array $attributes) => ['status' => ConsultationRequestStatus::Validated]);
    }

    public function treated(): static
    {
        return $this->state(fn (array $attributes) => ['status' => ConsultationRequestStatus::Treated]);
    }
}
