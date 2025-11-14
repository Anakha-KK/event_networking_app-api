<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserProfileSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $user = User::where('email', 'test@example.com')->first();

        if (! $user) {
            return;
        }

        $profileData = [
            'job_title' => 'Product Marketing Lead',
            'company_name' => 'EventSphere Inc.',
            'avatar_url' => 'https://example.com/avatar.jpg',
            'location' => 'San Francisco, CA',
            'bio' => 'Conference enthusiast focused on building meaningful event experiences.',
            'phone_number' => '+1-555-123-4567',
            'is_first_timer' => false,
        ];

        if ($user->profile) {
            $user->profile->update($profileData);
        } else {
            $user->profile()->create($profileData);
        }
    }
}
