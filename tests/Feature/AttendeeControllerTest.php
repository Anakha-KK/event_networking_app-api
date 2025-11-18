<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendeeControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_list_attendees(): void
    {
        $this->getJson('/api/attendees')->assertStatus(401);
    }

    public function test_authenticated_user_receives_attendees_collection(): void
    {
        $currentUser = User::factory()->create();

        $attendee = User::factory()->create(['name' => 'Emma Johnson']);
        $attendee->profile()->create([
            'job_title' => 'Product Manager',
            'company_name' => 'Event Co',
            'avatar_url' => 'https://example.com/avatar.png',
            'location' => 'NYC',
            'bio' => 'Connector',
            'phone_number' => '1234567890',
            'is_first_timer' => true,
            'tags' => ['product', 'events'],
        ]);

        $response = $this->actingAs($currentUser, 'sanctum')->getJson('/api/attendees?q=Emma');

        $response->assertOk()
            ->assertJsonPath('data.0.name', 'Emma Johnson')
            ->assertJsonPath('data.0.company_name', 'Event Co')
            ->assertJsonPath('data.0.tags', ['product', 'events']);
    }

    public function test_can_filter_first_timer_attendees(): void
    {
        $currentUser = User::factory()->create();

        $firstTimer = User::factory()->create(['name' => 'First Timer']);
        $firstTimer->profile()->create([
            'job_title' => 'Designer',
            'company_name' => 'Design Hub',
            'avatar_url' => null,
            'location' => null,
            'bio' => null,
            'phone_number' => '1112223333',
            'is_first_timer' => true,
            'tags' => ['design'],
        ]);

        $regular = User::factory()->create(['name' => 'Regular Attendee']);
        $regular->profile()->create([
            'job_title' => 'Engineer',
            'company_name' => 'Build Things',
            'avatar_url' => null,
            'location' => null,
            'bio' => null,
            'phone_number' => '9998887777',
            'is_first_timer' => false,
            'tags' => ['engineering'],
        ]);

        $response = $this->actingAs($currentUser, 'sanctum')->getJson('/api/attendees?filter=first_timers');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'First Timer');
    }
}
