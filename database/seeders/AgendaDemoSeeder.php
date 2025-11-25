<?php

namespace Database\Seeders;

use App\Models\AgendaDay;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

class AgendaDemoSeeder extends Seeder
{
    public function run(): void
    {
        $startDate = CarbonImmutable::now()->startOfDay();
        $daysCount = 5;

        $slotTemplates = [
            9 => [
                'title' => 'Welcome Coffee & Check-in',
                'description' => 'Grab coffee, pick up your badge, and meet other builders.',
                'location' => 'Lobby',
            ],
            10 => [
                'title' => 'Workshop 1: Fast Prototyping in FileMaker',
                'description' => 'Hands-on build; bring your laptop and ship a small app in 45 minutes.',
                'location' => 'Workshop Room A',
            ],
            11 => [
                'title' => 'Tea Break & Hallway Chats',
                'description' => 'Tea/coffee carts plus guided “meet 3 new people” prompts.',
                'location' => 'Lounge',
            ],
            12 => [
                'title' => 'Lunch Break & Birds-of-a-Feather Tables',
                'description' => 'Sit by topic: integrations, UX, performance, automation.',
                'location' => 'Café Pavilion',
            ],
            13 => [
                'title' => 'Workshop 2: API Integration Clinic',
                'description' => 'Connect FileMaker with REST services, webhooks, and auth flows.',
                'location' => 'Workshop Room B',
            ],
            14 => [
                'title' => 'Activity: Build-a-Bot Challenge',
                'description' => 'Teams prototype a FileMaker + AI workflow; judges pick best UX.',
                'location' => 'Collab Pods',
            ],
            15 => [
                'title' => 'Chit-Chat Networking Break',
                'description' => 'Facilitated meetups; trade QR codes and set up 1:1s.',
                'location' => 'Expo Floor',
            ],
            16 => [
                'title' => 'Showcase & Office Hours',
                'description' => 'Lightning share-outs from the challenge plus expert office hours.',
                'location' => 'Main Hall',
            ],
        ];

        $dayThemes = [
            'Day 1: Kickoff & Foundations',
            'Day 2: Integration & Automation',
            'Day 3: UX & Performance',
            'Day 4: Data Quality & Security',
            'Day 5: Deploy & Scale',
        ];

        // Reset existing agenda so demo data stays deterministic.
        AgendaDay::query()->delete();

        for ($i = 0; $i < $daysCount; $i++) {
            $day = AgendaDay::create([
                'day_number' => $i + 1,
                'date' => $startDate->addDays($i)->toDateString(),
            ]);

            $slots = [];
            for ($hour = 9; $hour < 17; $hour++) {
                $start = CarbonImmutable::createFromTime($hour, 0);
                $template = $slotTemplates[$hour] ?? [
                    'title' => 'TBD Session',
                    'description' => 'Session details to be announced.',
                    'location' => 'Main Hall',
                ];

                $titlePrefix = $dayThemes[$i] ?? 'Conference Day';

                $slots[] = [
                    'start_time' => $start->format('H:i:s'),
                    'end_time' => $start->addHour()->format('H:i:s'),
                    'title' => "{$titlePrefix} — {$template['title']}",
                    'description' => $template['description'],
                    'location' => $template['location'],
                ];
            }

            $day->slots()->createMany($slots);
        }
    }
}
