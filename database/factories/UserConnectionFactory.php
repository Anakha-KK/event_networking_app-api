<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserConnection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserConnection>
 */
class UserConnectionFactory extends Factory
{
    protected $model = UserConnection::class;

    public function definition(): array
    {
        $isFirstTimer = (bool) $this->faker->boolean;
        $basePoints = $isFirstTimer ? 50 : 25;
        $notesAdded = $this->faker->boolean;
        $totalPoints = $notesAdded ? $basePoints * 2 : $basePoints;

        return [
            'user_id' => User::factory(),
            'attendee_id' => User::factory(),
            'pair_token' => null,
            'is_first_timer' => $isFirstTimer,
            'base_points' => $basePoints,
            'total_points' => $totalPoints,
            'notes_added' => $notesAdded,
            'notes' => $notesAdded ? $this->faker->sentence : null,
            'connected_at' => now(),
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (UserConnection $connection) {
            $connection->pair_token = $this->pairToken(
                (int) $connection->user_id,
                (int) $connection->attendee_id
            );
        })->afterCreating(function (UserConnection $connection) {
            $connection->updateQuietly([
                'pair_token' => $this->pairToken(
                    (int) $connection->user_id,
                    (int) $connection->attendee_id
                ),
            ]);
        });
    }

    private function pairToken(int $userId, int $attendeeId): string
    {
        $ids = [$userId, $attendeeId];
        sort($ids);

        return implode(':', $ids);
    }
}
