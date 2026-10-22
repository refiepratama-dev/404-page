<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Order;
use App\Models\TicketType;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_renders(): void
    {
        Event::factory()->published()->create(['title' => 'Kopdar Komunitas Seru']);

        $this->get('/')
            ->assertOk()
            ->assertSee('Kopdar Komunitas Seru');
    }

    public function test_event_index_renders(): void
    {
        Event::factory()->published()->create(['title' => 'Pesta Vibrasi 2026']);
        Event::factory()->draft()->create(['title' => 'Rencana Kloning']);

        $this->get('/events')
            ->assertOk()
            ->assertSee('Pesta Vibrasi 2026')
            ->assertDontSee('Rencana Kloning');
    }

    public function test_event_index_can_search(): void
    {
        Event::factory()->published()->create(['title' => 'Kopdar Komunitas Seru']);
        Event::factory()->published()->create(['title' => 'Pesta Vibrasi Viby']);

        $this->get('/events?q=Kopdar')
            ->assertOk()
            ->assertSee('Kopdar Komunitas Seru')
            ->assertDontSee('Pesta Vibrasi Viby');
    }

    public function test_event_show_renders_with_ticket_types(): void
    {
        $venue = Venue::factory()->create(['name' => 'Gedung Serbaguna']);
        $event = Event::factory()->published()->create([
            'title' => 'Festival Seni Kota',
            'venue_id' => $venue->id,
        ]);

        TicketType::factory()->create([
            'event_id' => $event->id,
            'name' => 'Early Bird',
            'price' => 150000,
            'quota' => 100,
            'sold' => 10,
        ]);

        $this->get('/events/'.$event->slug)
            ->assertOk()
            ->assertSee('Festival Seni Kota')
            ->assertSee('Gedung Serbaguna')
            ->assertSee('Early Bird')
            ->assertSee('Rp150.000');
    }

    public function test_draft_event_is_not_publicly_visible(): void
    {
        $event = Event::factory()->draft()->create();

        $this->get('/events/'.$event->slug)->assertNotFound();
    }

    public function test_checkout_redirects_without_a_cart(): void
    {
        Livewire::test('pages::checkout')
            ->assertRedirect(route('events.index'));
    }

    public function test_login_page_renders(): void
    {
        $this->get('/login')->assertOk()->assertSee('Masuk');
    }

    public function test_logout_requires_post(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/logout')
            ->assertRedirect('/');

        $this->assertGuest();
    }

    public function test_admin_dashboard_is_protected_from_guests(): void
    {
        $this->get('/admin')->assertRedirect('/login');
    }

    public function test_home_shows_only_upcoming_published_events(): void
    {
        Event::factory()->finished()->create(['title' => 'Event Lampau']);
        Event::factory()->published()->create(['title' => 'Event Mendatang']);

        $this->get('/')
            ->assertOk()
            ->assertSee('Event Mendatang')
            ->assertDontSee('Event Lampau');
    }

    public function test_public_pages_do_not_expose_draft_orders(): void
    {
        $order = Order::factory()->create(['buyer_name' => 'Rahasia_ORDER_VALUE']);

        $this->get('/')->assertOk()->assertDontSee($order->buyer_name);
    }
}
