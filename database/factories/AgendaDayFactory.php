<?php

namespace Database\Factories;

use App\Models\AgendaDay;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AgendaDay>
 */
class AgendaDayFactory extends Factory
{
    protected $model = AgendaDay::class;

    public function definition(): array
    {
        $startDate = now()->startOfDay();

        return [
            'day_number' => $this->faker->numberBetween(1, 7),
            'date' => $startDate->copy()->addDays($this->faker->numberBetween(0, 6))->toDateString(),
        ];
    }
}
