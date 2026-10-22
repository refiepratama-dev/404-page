<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\TicketType;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPagesTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/admin')->assertRedirect('/login');
        $this->get('/admin/events')->assertRedirect('/login');
        $this->get('/admin/orders')->assertRedirect('/login');
        $this->get('/admin/venues')->assertRedirect('/login');
    }

    public function test_non_admin_user_is_forbidden(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'user']))
            ->get('/admin')
            ->assertForbidden();
    }

    public function test_dashboard_renders(): void
    {
        $venue = Venue::factory()->create();
        $event = Event::factory()->published()->create(['venue_id' => $venue->id]);
        $type = TicketType::factory()->sold(12, 100)->create(['event_id' => $event->id]);
        $order = Order::factory()->paid()->create(['event_id' => $event->id, 'total' => 350000]);
        OrderItem::factory()->create(['order_id' => $order->id, 'ticket_type_id' => $type->id, 'quantity' => 2, 'unit_price' => 175000, 'subtotal' => 350000]);

        $this->actingAs($this->admin())
            ->get('/admin')
            ->assertOk()
            ->assertSee('Dashboard')
            ->assertSee('Rp350.000')
            ->assertSee($event->title);
    }

    public function test_event_index_renders(): void
    {
        Event::factory()->count(3)->create();

        $this->actingAs($this->admin())
            ->get('/admin/events')
            ->assertOk()
            ->assertSee('Kelola Event');
    }

    public function test_event_index_can_filter_by_status(): void
    {
        $draft = Event::factory()->draft()->create(['title' => 'Rencana Kloning']);
        Event::factory()->published()->create(['title' => 'Sudah Terbentur']);

        $this->actingAs($this->admin())
            ->get('/admin/events?status=draft')
            ->assertOk()
            ->assertSee('Rencana Kloning')
            ->assertDontSee('Sudah Terbentur');
    }

    public function test_event_index_can_search(): void
    {
        Event::factory()->create(['title' => 'Kopdar Komunitas Seru']);
        Event::factory()->create(['title' => 'Pesta Vibrasi Viby']);

        $this->actingAs($this->admin())
            ->get('/admin/events?search=Kopdar')
            ->assertOk()
            ->assertSee('Kopdar Komunitas Seru')
            ->assertDontSee('Pesta Vibrasi Viby');
    }

    public function test_event_show_renders(): void
    {
        $event = Event::factory()->published()->create(['title' => 'Rapat Tahunan']);
        $type = TicketType::factory()->sold(3, 50)->create(['event_id' => $event->id, 'name' => 'Reguler']);
        $event->sessions()->create([
            'title' => 'Keynote',
            'speaker' => 'Ibu Rina',
            'track' => 'Main Stage',
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addHour(),
            'sort_order' => 0,
        ]);

        $this->actingAs($this->admin())
            ->get('/admin/events/'.$event->slug)
            ->assertOk()
            ->assertSee('Rapat Tahunan')
            ->assertSee('Reguler')
            ->assertSee('Keynote')
            ->assertSee('Ibu Rina');
    }

    public function test_orders_index_renders(): void
    {
        $order = Order::factory()->paid()->create(['buyer_name' => 'Budi Santoso', 'total' => 250000]);

        $this->actingAs($this->admin())
            ->get('/admin/orders')
            ->assertOk()
            ->assertSee('Pesanan')
            ->assertSee('Budi Santoso')
            ->assertSee($order->order_number);
    }

    public function test_orders_index_can_filter_by_status(): void
    {
        Order::factory()->pending()->create(['buyer_name' => 'Pending Pakai']);
        Order::factory()->paid()->create(['buyer_name' => 'Paid Pakai']);

        $this->actingAs($this->admin())
            ->get('/admin/orders?status=pending')
            ->assertOk()
            ->assertSee('Pending Pakai')
            ->assertDontSee('Paid Pakai');
    }

    public function test_venues_index_renders(): void
    {
        Venue::factory()->create(['name' => 'Gedung Serbagunawow']);

        $this->actingAs($this->admin())
            ->get('/admin/venues')
            ->assertOk()
            ->assertSee('Lokasi')
            ->assertSee('Gedung Serbagunawow');
    }
}
