<?php

namespace Database\Factories;

use App\Models\Venue;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Venue>
 */
class VenueFactory extends Factory
{
    protected $model = Venue::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->randomElement([
            'Gedung Kesenian Jakarta',
            'Jiexpo Kemayoran',
            'Istora Senayan',
            'Balai Sarbini',
            'Dapur Ulang Together',
            'GOR C-Tra Arena',
            'Balkota Convention Hall',
            'Taman Ismail Marzuki',
        ]);

        return [
            'name' => $name,
            'address' => $this->faker->streetAddress().', '.$this->faker->randomElement([
                'Lt. 3, Kavling 27', 'Blok C, No. 12', 'Lt. 8, Unit 402', 'Ruang 1-2', 'Lt. 5, Pintu Timur',
            ]),
            'city' => $this->faker->randomElement(['Jakarta Selatan', 'Jakarta Pusat', 'Jakarta Utara', 'Bandung', 'Surabaya', 'Yogyakarta']),
            'capacity' => $this->faker->randomElement([150, 300, 500, 800, 1200, 2500, 5000]),
        ];
    }

    public function name(string $name): static
    {
        return $this->state(fn () => ['name' => $name, 'slug' => Str::slug($name)]);
    }
}
