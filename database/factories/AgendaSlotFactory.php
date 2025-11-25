<?php

namespace Database\Factories;

use App\Models\AgendaDay;
use App\Models\AgendaSlot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AgendaSlot>
 */
class AgendaSlotFactory extends Factory
{
    protected $model = AgendaSlot::class;

    public function definition(): array
    {
        $startHour = $this->faker->numberBetween(9, 16);
        $start = now()->startOfDay()->setTime($startHour, 0);

        return [
            'agenda_day_id' => AgendaDay::factory(),
            'start_time' => $start->format('H:i:s'),
            'end_time' => $start->copy()->addHour()->format('H:i:s'),
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->optional()->sentence(8),
            'location' => $this->faker->optional()->city(),
        ];
    }
}
