<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\Venue;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    protected $model = Event::class;

    /**
     * Hand written word pools instead of faker's company provider, because the
     * app faker locale (id_ID) has no `catchPhrase` formatter.
     */
    private static array $prefixes = [
        'Kopdar', 'Ngobrol', 'Pesta', 'Rapat', 'Bazar', 'Kelas', 'Turnamen', 'Festival',
    ];

    private static array $subjects = [
        'Komunitas Seru', 'Startup Lokal', 'Kreator Digital', 'Karyawan Kantor',
        'Students Meetup', 'Kolaborasi Sandi', 'Belajar Bareng', 'Impact Weekend',
    ];

    private static array $suffixes = [
        'Vol. 1', 'Vol. 2', 'Edition 2026', 'Sesi 3', 'Ronde Dua', 'Chapter Baru',
    ];

    public function definition(): array
    {
        $title = $this->faker->unique()->randomElement(self::$prefixes)
            .' '.$this->faker->randomElement(self::$subjects)
            .($this->faker->boolean(35) ? ' '.$this->faker->randomElement(self::$suffixes) : '');

        $startsAt = $this->faker->dateTimeBetween('now', '+6 months');
        $endsAt = (clone $startsAt)->modify('+'.$this->faker->randomElement([4, 6, 8, 10]).' hours');

        return [
            'venue_id' => Venue::factory(),
            'title' => $title,
            'slug' => Str::slug($title).'-'.Str::lower(Str::random(5)),
            'category' => $this->faker->randomElement(Event::CATEGORIES),
            'description' => $this->faker->paragraphs(3, true),
            'banner_url' => null,
            'accent' => $this->faker->randomElement(['violet', 'sky', 'emerald', 'amber', 'rose', 'cyan']),
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'status' => Event::STATUS_PUBLISHED,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => ['status' => Event::STATUS_DRAFT]);
    }

    public function published(): static
    {
        return $this->state(fn () => ['status' => Event::STATUS_PUBLISHED]);
    }

    public function finished(): static
    {
        return $this->state(fn () => [
            'status' => Event::STATUS_FINISHED,
            'starts_at' => now()->subDays(30),
            'ends_at' => now()->subDays(29),
        ]);
    }

    public function category(string $category): static
    {
        return $this->state(fn () => ['category' => $category]);
    }
}
