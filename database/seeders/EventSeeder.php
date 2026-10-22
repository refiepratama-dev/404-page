<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\EventSession;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\TicketType;
use App\Models\Venue;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class EventSeeder extends Seeder
{
    /**
     * Deterministic catalogue so the seeded demo always looks the same.
     *
     * @var array<int, array<string, mixed>>
     */
    private array $catalogue = [
        [
            'title' => 'Pesta Vibrasi: Konser Tunggal Seniman Nusantara',
            'category' => 'Konser Musik',
            'accent' => 'violet',
            'status' => Event::STATUS_PUBLISHED,
            'in' => 12,
            'duration' => 6,
            'venue' => ['Istora Senayan', 'Jl. Pintu Satu Senayan, Jakarta Pusat', 'Jakarta Pusat', 7000],
            'description' => "Malam spektakuler yang mempertemukan enam seniman dari berbagai generasi dalam satu panggung. Satu tiket, enam penyanyi, satu ingatan yang tidak akan pernah sama.\n\nDilengkapi panggung lighting kelas dunia, sound system line array, dan area food court dengan 40+ tenant lokal pilihan kami.",
            'tickets' => [
                ['name' => 'Early Bird', 'description' => 'Hanya tersedia sampai 30 hari sebelum acara.', 'price' => 250000, 'quota' => 500, 'sold' => 500],
                ['name' => 'Regular', 'description' => 'Akses tribun dan area aquarium.', 'price' => 450000, 'quota' => 3000, 'sold' => 2140],
                ['name' => 'VIP Tribune', 'description' => 'Tribun tengah, tribun Response, akses meet and greet setelah acara.', 'price' => 1500000, 'quota' => 200, 'sold' => 184],
            ],
            'sessions' => [
                ['Gate Open & Pre-Show Music', 'DJ Sora', 'Gerbang Utama', '16:00', '17:00'],
                ['Opening Set', 'Nirmala Sari', 'Main Stage', '17:00', '18:00'],
                ['Grand Performance', '6 Seniman', 'Main Stage', '19:00', '22:00'],
                ['Meet & Greet', 'Panitia Rakit', 'Area Tribune', '22:00', '22:45'],
            ],
        ],
        [
            'title' => 'Rakitan Tech Summit 2026: Membangun AI yang Bertanggung Jawab',
            'category' => 'Tech & Startup',
            'accent' => 'sky',
            'status' => Event::STATUS_PUBLISHED,
            'in' => 26,
            'duration' => 9,
            'venue' => ['Jiexpo Kemayoran', 'Jl. Gatot Subroto, Jakarta Selatan', 'Jakarta Selatan', 12000],
            'description' => "Konferensi dua hari untuk praktisi teknologi, founder, dan peneliti. Empat track tematik, 30 pembicara, dan hacking lab yang buka sampai larut.\n\nBawa laptop, setiap peserta mendapat akses ke repositori dan environment labs untuk seluruh materi acara.",
            'tickets' => [
                ['name' => 'Student', 'description' => 'Wajib menunjukkan kartu pelajar.', 'price' => 150000, 'quota' => 800, 'sold' => 612],
                ['name' => 'Professional', 'description' => 'Tiket penuh dua hari + akses rekaman.', 'price' => 950000, 'quota' => 4000, 'sold' => 2874],
                ['name' => 'Team Pass (5 orang)', 'description' => 'Lima tiket profesional dalam satu transaksi.', 'price' => 4000000, 'quota' => 300, 'sold' => 96],
            ],
            'sessions' => [
                ['Registration', 'Panitia Rakit', 'Lobby', '08:00', '09:00'],
                ['Keynote: AI Beyond the Hype', 'Dr. Amara Nugraha', 'Main Stage', '09:00', '10:00'],
                ['Track: MLOps at Scale', 'Bagas Prakoso', 'Track A', '10:30', '12:00'],
                ['Track: Responsible AI', 'Sinta Halim', 'Track B', '10:30', '12:00'],
                ['Hacking Lab', 'Open Community', 'Lab 1-3', '14:00', '18:00'],
            ],
        ],
        [
            'title' => 'Marketplace Konten Kreatif & Brand Summit',
            'category' => 'Bisnis',
            'accent' => 'emerald',
            'status' => Event::STATUS_PUBLISHED,
            'in' => 40,
            'duration' => 8,
            'venue' => ['Balkota Convention Hall', 'Jl. Jenderal Sudirman Kav. 52-53', 'Jakarta Selatan', 2500],
            'description' => "Satu hari untuk founder, marketing lead, dan kreator. Bahasan tentang monetisasi konten, negosiasi royalti, dan membangun brand yang bertahan lama.\n\nBring your case study — ada sesi clinic untuk review strategi langsung oleh praktisi senior.",
            'tickets' => [
                ['name' => 'General Admission', 'description' => 'Akses seluruh sesi dan expo.', 'price' => 350000, 'quota' => 1500, 'sold' => 934],
                ['name' => 'Founder Pass', 'description' => 'GA + akses lounge founder + sesi clinic.', 'price' => 1200000, 'quota' => 150, 'sold' => 141],
            ],
            'sessions' => [
                ['Opening Keynote', 'Rangga Prasetyo', 'Main Hall', '09:00', '09:45'],
                ['Monetisasi Konten 2026', 'Maya Kusuma', 'Main Hall', '10:00', '11:00'],
                ['Brand Clinic', 'Tim Rakit', 'Ruang C', '13:00', '15:00'],
            ],
        ],
        [
            'title' => 'Pameran Seni Rupa Nusantara: Rupa Baru',
            'category' => 'Seni & Budaya',
            'accent' => 'rose',
            'status' => Event::STATUS_PUBLISHED,
            'in' => 5,
            'duration' => 10,
            'venue' => ['Gedung Kesenian Jakarta', 'Jl. Senopati No. 1', 'Jakarta Selatan', 1500],
            'description' => "Pameran retval karya 40 seniman Indonesia dari lima kota. Instalasi, seni digital, sampai performance art — semuanya dalam satu gedung bersejarah.\n\nTiket berlaku untuk seluruh durasi pameran. Anak-anak di bawah 7 tahun gratis.",
            'tickets' => [
                ['name' => 'Regular', 'description' => 'Tiket masuk utuh.', 'price' => 75000, 'quota' => 900, 'sold' => 302],
                ['name' => 'Special Exhibition', 'description' => 'Termasuk area instalasi khusus dan dokumentasi.', 'price' => 150000, 'quota' => 200, 'sold' => 156],
            ],
            'sessions' => [
                ['Opening Ceremony', 'Kurator Durand', 'Lobby', '10:00', '11:00'],
                ['Kurator Talk', 'Kurator Durand', 'Ruang A', '14:00', '15:00'],
                ['Performance Night', 'Kolektif Senja', 'Halaman', '19:00', '21:00'],
            ],
        ],
        [
            'title' => 'Rakit Fun Run & Community Health Festival',
            'category' => 'Olahraga',
            'accent' => 'cyan',
            'status' => Event::STATUS_PUBLISHED,
            'in' => 18,
            'duration' => 5,
            'venue' => ['Dapur Ulang Together', 'Jl. Dapur Ulang No. 3', 'Jakarta Selatan', 2000],
            'description' => "Lari 5K, 10K, dan fun run keluarga 1.5K sepanjang rute kota yang dikepung pohon-pohon hijau. Setelah lari ada medical check-up gratis, yoga pagi, dan bazar makanan lokal.\n\nBawa bottle sendiri, setiap peserta dapat bottle minum dan medal finisher.",
            'tickets' => [
                ['name' => '5K Individual', 'description' => 'Medal + race pack + medical check.', 'price' => 120000, 'quota' => 1000, 'sold' => 764],
                ['name' => '10K Individual', 'description' => 'Medal finisher + timing chip + race pack.', 'price' => 180000, 'quota' => 600, 'sold' => 598],
                ['name' => 'Paket Keluarga (4 orang)', 'description' => '4 peserta fun run 1.5K + bazar voucher.', 'price' => 300000, 'quota' => 250, 'sold' => 87],
            ],
            'sessions' => [
                ['Registration & Bib Take', 'Panitia Rakit', 'Base Camp', '05:30', '06:45'],
                ['Fun Run 1.5K', 'Korong 1-3', 'Rute Kota', '07:00', '09:00'],
                ['Yoga & Stretching', 'YogaSpace', 'Lapangan', '09:30', '10:30'],
            ],
        ],
        [
            'title' => 'Kopdar Komunitas Seru: Ngobrol Santai Sore',
            'category' => 'Komunitas',
            'accent' => 'amber',
            'status' => Event::STATUS_PUBLISHED,
            'in' => 3,
            'duration' => 4,
            'venue' => ['Balai Sarbini', 'Jl. Purnawarman No. 2', 'Bandung', 300],
            'description' => 'Kopdar kecil untuk 300 orang. Round table per batch, lightning talk 7 menit, dan sesi-sesi kecil yang selalu butuh tempat tambahan.',
            'tickets' => [
                ['name' => 'Regular', 'description' => 'Tiket masuk + snack box.', 'price' => 25000, 'quota' => 250, 'sold' => 118],
                ['name' => 'Early Bird', 'description' => 'Harga khusus 100 tiket pertama.', 'price' => 10000, 'quota' => 100, 'sold' => 100],
            ],
            'sessions' => [
                ['Check-in & Ngopi', 'Panitia Rakit', 'Lobby', '09:00', '09:45'],
                ['Round Table Batch 1-3', 'Fasilitator', 'Ruang 1-3', '10:00', '11:30'],
                ['Lightning Talks', 'Komunitas', 'Aula', '13:00', '14:00'],
            ],
        ],
        [
            'title' => 'Indie Music Fest: Panggung Underground',
            'category' => 'Konser Musik',
            'accent' => 'rose',
            'status' => Event::STATUS_PUBLISHED,
            'in' => 21,
            'duration' => 7,
            'venue' => ['GOR C-Tra Arena', 'Jl. Gatot Subroto No. 1', 'Jakarta Selatan', 3500],
            'description' => 'Dua panggung, sebelas band independen dari seluruh Indonesia. Sound system profesional dan area bazaar Record Fair di lokasi yang sama.',
            'tickets' => [
                ['name' => 'Presale', 'description' => 'Harga presale, terbatas.', 'price' => 200000, 'quota' => 800, 'sold' => 800],
                ['name' => 'Regular', 'description' => 'Dua panggung + bazaar record fair.', 'price' => 320000, 'quota' => 2000, 'sold' => 1306],
                ['name' => 'Backstage Pass', 'description' => 'Akses soundcheck + backstage.', 'price' => 1250000, 'quota' => 100, 'sold' => 93],
            ],
            'sessions' => [
                ['Doors Open', 'DJ Resi', 'Panggung B', '15:00', '16:00'],
                ['Band Set 1', '4 Band', 'Panggung A', '16:00', '19:00'],
                ['Band Set 2', '7 Band', 'Panggung A', '19:30', '22:00'],
            ],
        ],
        [
            'title' => 'Workshop Digital Product & UX Sprint',
            'category' => 'Tech & Startup',
            'accent' => 'sky',
            'status' => Event::STATUS_PUBLISHED,
            'in' => 9,
            'duration' => 8,
            'venue' => ['Dapur Ulang Together', 'Jl. Dapur Ulang No. 3', 'Jakarta Selatan', 120],
            'description' => "Sprint 1 hari untuk membangun dan menguji prototipe produk digital. Tim Rakit menurunkan fasilitator senior untuk setiap tim.\n\nPeserta membawa ide produk sendiri — diuji bersama tim dan peserta lain.",
            'tickets' => [
                ['name' => 'Workshop Seat', 'description' => 'Kursi, makan siang, dan material cetak.', 'price' => 850000, 'quota' => 60, 'sold' => 58],
                ['name' => 'Observer', 'description' => 'Hanya resepsionis dan absen.', 'price' => 200000, 'quota' => 40, 'sold' => 12],
            ],
            'sessions' => [
                ['Briefing & Setup', 'Fasilitator', 'Lab 1', '09:00', '09:30'],
                ['Design Sprint — Mapping', 'Per Tim', 'Lab 1-4', '09:30', '12:00'],
                ['Prototype Test', 'Per Tim', 'Lab 1-4', '13:00', '15:30'],
                ['Pitch & Review', 'Tim Rakit', 'Aula', '16:00', '17:00'],
            ],
        ],
        [
            'title' => 'Pesta Akhir Tahun Kantoran',
            'category' => 'Komunitas',
            'accent' => 'amber',
            'status' => Event::STATUS_DRAFT,
            'in' => 60,
            'duration' => 5,
            'venue' => ['Balai Sarbini', 'Jl. Purnawarman No. 2', 'Bandung', 400],
            'description' => 'Masih draf, detail finalisasi. Paket private event, belum dipublikasikan.',
            'tickets' => [
                ['name' => 'Regular', 'description' => 'Tiket masuk + makan malam.', 'price' => 300000, 'quota' => 300, 'sold' => 0],
            ],
            'sessions' => [
                ['Registration', 'Panitia Rakit', 'Lobby', '17:00', '18:00'],
            ],
        ],
        [
            'title' => 'Konser Tunggal Album Perdana: Malam Kala',
            'category' => 'Konser Musik',
            'accent' => 'violet',
            'status' => Event::STATUS_FINISHED,
            'in' => -45,
            'duration' => 6,
            'venue' => ['Istora Senayan', 'Jl. Pintu Satu Senayan, Jakarta Pusat', 'Jakarta Pusat', 7000],
            'description' => 'Event selesai — arsip untuk laporan penjualan.',
            'tickets' => [
                ['name' => 'Regular', 'description' => 'Tiket reguler.', 'price' => 300000, 'quota' => 4000, 'sold' => 3980],
            ],
            'sessions' => [],
        ],
    ];

    public function run(): void
    {
        foreach ($this->catalogue as $data) {
            $this->seedEvent($data);
        }
    }

    private function seedEvent(array $data): void
    {
        [$venueName, $address, $city, $capacity] = $data['venue'];

        $venue = Venue::firstOrCreate(
            ['name' => $venueName],
            ['address' => $address, 'city' => $city, 'capacity' => $capacity],
        );

        $startsAt = now()->addDays($data['in'])->setTime(9, 0);

        $event = Event::updateOrCreate(
            ['slug' => Str::slug($data['title'])],
            [
                'venue_id' => $venue->id,
                'title' => $data['title'],
                'category' => $data['category'],
                'description' => $data['description'],
                'accent' => $data['accent'],
                'status' => $data['status'],
                'starts_at' => $startsAt,
                'ends_at' => $startsAt->copy()->addHours($data['duration']),
            ],
        );

        foreach ($data['sessions'] as $index => [$title, $speaker, $track, $from, $to]) {
            EventSession::updateOrCreate(
                ['event_id' => $event->id, 'title' => $title],
                [
                    'speaker' => $speaker,
                    'track' => $track,
                    'starts_at' => $event->starts_at->copy()->setTimeFromTimeString($from),
                    'ends_at' => $event->starts_at->copy()->setTimeFromTimeString($to),
                    'sort_order' => $index,
                ],
            );
        }

        $ticketTypes = [];

        foreach ($data['tickets'] as $ticket) {
            $ticketTypes[] = TicketType::updateOrCreate(
                ['event_id' => $event->id, 'name' => $ticket['name']],
                [
                    'description' => $ticket['description'],
                    'price' => $ticket['price'],
                    'quota' => $ticket['quota'],
                    'sold' => 0,
                ],
            );
        }

        if ($event->isPublished()) {
            $this->seedOrders($event, $ticketTypes);
        }

        $this->recalculateSold($ticketTypes);
    }

    /**
     * Generate believable order history for a published event.
     *
     * @param  array<int, TicketType>  $ticketTypes
     */
    private function seedOrders(Event $event, array $ticketTypes): void
    {
        if ($ticketTypes === []) {
            return;
        }

        $count = fake()->numberBetween(3, 7);

        for ($i = 0; $i < $count; $i++) {
            $ticketType = fake()->randomElement($ticketTypes);
            $quantity = fake()->numberBetween(1, 3);
            $subtotal = $ticketType->price * $quantity;

            $status = fake()->randomElement([
                Order::STATUS_PAID,
                Order::STATUS_PAID,
                Order::STATUS_PAID,
                Order::STATUS_PENDING,
                Order::STATUS_CANCELLED,
            ]);

            $order = Order::create([
                'order_number' => Order::generateNumber(),
                'event_id' => $event->id,
                'buyer_name' => fake()->name(),
                'buyer_email' => fake()->safeEmail(),
                'buyer_phone' => '08'.fake()->numerify('##########'),
                'notes' => fake()->boolean(25) ? fake()->sentence() : null,
                'total' => $subtotal,
                'status' => $status,
                'paid_at' => $status === Order::STATUS_PAID
                    ? fake()->dateTimeBetween('-30 days', 'now')
                    : null,
            ]);

            OrderItem::create([
                'order_id' => $order->id,
                'ticket_type_id' => $ticketType->id,
                'quantity' => $quantity,
                'unit_price' => $ticketType->price,
                'subtotal' => $subtotal,
            ]);
        }
    }

    /**
     * Keep the sold counter consistent with the seeded order items so the
     * dashboard numbers and the progress bars always agree with the data.
     *
     * @param  array<int, TicketType>  $ticketTypes
     */
    private function recalculateSold(array $ticketTypes): void
    {
        foreach ($ticketTypes as $ticketType) {
            $sold = (int) OrderItem::query()
                ->join('orders', 'orders.id', '=', 'order_items.order_id')
                ->where('order_items.ticket_type_id', $ticketType->id)
                ->where('orders.status', Order::STATUS_PAID)
                ->sum('order_items.quantity');

            $ticketType->update(['sold' => min($sold, $ticketType->quota)]);
        }
    }
}
