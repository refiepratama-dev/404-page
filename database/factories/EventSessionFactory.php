<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\EventSession;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventSession>
 */
class EventSessionFactory extends Factory
{
    protected $model = EventSession::class;

    public function definition(): array
    {
        $startsAt = $this->faker->dateTimeBetween('now', '+6 months');

        return [
            'event_id' => Event::factory(),
            'title' => $this->faker->randomElement([
                'Registrasi & Pembukaan',
                'Keynote: Masa Depan Industri',
                'Sesi Tanya Jawab',
                'Workshop Praktis',
                'Panel Diskusi',
                'Penutupan & Penghargaan',
            ]),
            'speaker' => $this->faker->name(),
            'track' => $this->faker->randomElement(['Main Stage', 'Ruang A', 'Ruang B', 'Online']),
            'starts_at' => $startsAt,
            'ends_at' => (clone $startsAt)->modify('+'.$this->faker->randomElement([30, 45, 60, 90]).' minutes'),
            'sort_order' => $this->faker->numberBetween(0, 20),
        ];
    }
}
