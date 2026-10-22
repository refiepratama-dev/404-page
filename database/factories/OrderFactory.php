<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        $status = $this->faker->randomElement([
            Order::STATUS_PAID,
            Order::STATUS_PAID,
            Order::STATUS_PENDING,
            Order::STATUS_CANCELLED,
        ]);

        return [
            'order_number' => Order::generateNumber(),
            'event_id' => Event::factory(),
            'buyer_name' => $this->faker->name(),
            'buyer_email' => $this->faker->safeEmail(),
            'buyer_phone' => '08'.$this->faker->numerify('##########'),
            'notes' => $this->faker->boolean(20) ? $this->faker->sentence() : null,
            'total' => 0,
            'status' => $status,
            'paid_at' => $status === Order::STATUS_PAID ? now()->subDays($this->faker->numberBetween(0, 20)) : null,
        ];
    }

    public function paid(): static
    {
        return $this->state(fn () => ['status' => Order::STATUS_PAID, 'paid_at' => now()]);
    }

    public function pending(): static
    {
        return $this->state(fn () => ['status' => Order::STATUS_PENDING, 'paid_at' => null]);
    }
}
