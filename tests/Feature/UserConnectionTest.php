<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserConnection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserConnectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_connection_with_first_timer_and_notes(): void
    {
        $user = User::factory()->create();
        $attendee = User::factory()->create();

        $attendee->profile()->create([
            'job_title' => 'Engineer',
            'company_name' => 'Tech Corp',
            'location' => 'Remote',
            'bio' => null,
            'phone_number' => null,
            'is_first_timer' => true,
        ]);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/connections', [
            'attendee_id' => (string) $attendee->id,
            'notes' => 'Great chat about product ideas.',
            'signature' => $attendee->qrSignature(),
        ]);

        $response->assertCreated()
            ->assertJsonPath('connection.base_points', 50)
            ->assertJsonPath('connection.total_points', 100)
            ->assertJsonPath('connection.notes_added', true);

        $this->assertDatabaseHas('user_connections', [
            'user_id' => $user->id,
            'attendee_id' => $attendee->id,
            'notes_added' => true,
        ]);
    }

    public function test_user_can_create_connection_with_returning_attendee_without_notes(): void
    {
        $user = User::factory()->create();
        $attendee = User::factory()->create();

        $attendee->profile()->create([
            'job_title' => 'PM',
            'company_name' => 'API Hub',
            'location' => null,
            'bio' => null,
            'phone_number' => null,
            'is_first_timer' => false,
        ]);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/connections', [
            'attendee_id' => (string) $attendee->id,
            'signature' => $attendee->qrSignature(),
        ]);

        $response->assertCreated()
            ->assertJsonPath('connection.base_points', 25)
            ->assertJsonPath('connection.total_points', 25)
            ->assertJsonPath('connection.notes_added', false);
    }

    public function test_daily_cap_prevents_more_than_fifteen_connections(): void
    {
        $user = User::factory()->create();

        User::factory()->count(15)->create()->each(function (User $attendee) use ($user) {
            UserConnection::factory()->create([
                'user_id' => $user->id,
                'attendee_id' => $attendee->id,
                'pair_token' => $this->pairToken($user->id, $attendee->id),
                'connected_at' => now(),
            ]);
        });

        $extra = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/connections', [
            'attendee_id' => (string) $extra->id,
            'signature' => $extra->qrSignature(),
        ]);

        $response->assertStatus(429)
            ->assertJsonPath('message', 'Daily connection limit reached. Try again tomorrow.');
    }

    public function test_user_cannot_connect_with_same_attendee_twice(): void
    {
        $user = User::factory()->create();
        $attendee = User::factory()->create();

        UserConnection::factory()->create([
            'user_id' => $user->id,
            'attendee_id' => $attendee->id,
            'pair_token' => $this->pairToken($user->id, $attendee->id),
        ]);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/connections', [
            'attendee_id' => (string) $attendee->id,
            'signature' => $attendee->qrSignature(),
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'You have already connected with this attendee.');
    }

    public function test_user_cannot_connect_if_other_party_already_connected(): void
    {
        $user = User::factory()->create();
        $attendee = User::factory()->create();

        UserConnection::factory()->create([
            'user_id' => $attendee->id,
            'attendee_id' => $user->id,
            'pair_token' => $this->pairToken($user->id, $attendee->id),
        ]);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/connections', [
            'attendee_id' => (string) $attendee->id,
            'signature' => $attendee->qrSignature(),
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'You have already connected with this attendee.');
    }

    public function test_invalid_signature_rejected(): void
    {
        $user = User::factory()->create();
        $attendee = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/connections', [
            'attendee_id' => (string) $attendee->id,
            'signature' => 'invalid',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'The scan cannot be verified. Please try again.');
    }

    public function test_notes_can_be_submitted_later_and_double_points(): void
    {
        $user = User::factory()->create();
        $attendee = User::factory()->create();

        $connection = UserConnection::factory()->create([
            'user_id' => $user->id,
            'attendee_id' => $attendee->id,
            'base_points' => 25,
            'total_points' => 25,
            'notes_added' => false,
            'notes' => null,
        ]);

        $response = $this->actingAs($user, 'sanctum')->patchJson("/api/connections/{$connection->id}/notes", [
            'notes' => 'Sent follow up email.',
        ]);

        $response->assertOk()
            ->assertJsonPath('connection.total_points', 50)
            ->assertJsonPath('connection.notes_added', true);

        $this->assertDatabaseHas('user_connections', [
            'id' => $connection->id,
            'notes_added' => true,
            'total_points' => 50,
        ]);
    }

    public function test_attendee_must_exist(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/connections', [
            'attendee_id' => '999',
            'signature' => hash_hmac('sha256', '999', config('app.key')),
        ]);

        $response->assertStatus(404)
            ->assertJsonPath('message', 'Attendee not found.');
    }

    public function test_user_cannot_update_someone_elses_connection_notes(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $attendee = User::factory()->create();

        $connection = UserConnection::factory()->create([
            'user_id' => $other->id,
            'attendee_id' => $attendee->id,
            'notes_added' => false,
        ]);

        $this->actingAs($user, 'sanctum')
            ->patchJson("/api/connections/{$connection->id}/notes", [
                'notes' => 'Trying to update.',
            ])
            ->assertStatus(403);
    }

    public function test_notes_endpoint_blocks_duplicate_submission(): void
    {
        $user = User::factory()->create();
        $attendee = User::factory()->create();

        $connection = UserConnection::factory()->create([
            'user_id' => $user->id,
            'attendee_id' => $attendee->id,
            'notes_added' => true,
        ]);

        $response = $this->actingAs($user, 'sanctum')->patchJson("/api/connections/{$connection->id}/notes", [
            'notes' => 'Second attempt.',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Notes were already added for this connection.');
    }

    private function pairToken(int $userId, int $attendeeId): string
    {
        $ids = [$userId, $attendeeId];
        sort($ids);

        return implode(':', $ids);
    }
}
