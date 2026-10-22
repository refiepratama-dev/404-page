<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\TicketType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TicketType>
 */
class TicketTypeFactory extends Factory
{
    protected $model = TicketType::class;

    public function definition(): array
    {
        $price = $this->faker->randomElement([50000, 75000, 100000, 150000, 200000, 350000, 500000]);

        return [
            'event_id' => Event::factory(),
            'name' => $this->faker->randomElement(['Early Bird', 'Regular', 'Presale', 'VIP', 'Festival Pass']),
            'description' => $this->faker->sentence(6),
            'price' => $price,
            'quota' => $this->faker->randomElement([50, 100, 200, 500, 1000]),
            'sold' => 0,
            'sales_start' => null,
            'sales_end' => null,
        ];
    }

    public function sold(int $sold, int $quota = 100): static
    {
        return $this->state(fn () => ['sold' => $sold, 'quota' => $quota]);
    }
}
