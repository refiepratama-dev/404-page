<?php

namespace Tests\Feature\Livewire;

use App\Models\Event;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\TicketType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    private function publishedEvent(): Event
    {
        return Event::factory()->published()->create(['title' => 'Pesta Vibrasi 2026']);
    }

    public function test_redirects_when_cart_is_empty(): void
    {
        Livewire::test('pages::checkout')
            ->assertRedirect(route('events.index'));
    }

    public function test_shows_order_summary_from_session_cart(): void
    {
        $event = $this->publishedEvent();
        $type = TicketType::factory()->create(['event_id' => $event->id, 'name' => 'Reguler', 'price' => 100000, 'quota' => 10, 'sold' => 0]);

        session(['checkout.cart' => ['event_id' => $event->id, 'quantities' => [$type->id => 3]]]);

        Livewire::test('pages::checkout')
            ->assertOk()
            ->assertSee('Reguler')
            ->assertSee('Rp300.000');
    }

    public function test_submit_creates_order_and_reserves_stock(): void
    {
        $event = $this->publishedEvent();
        $type = TicketType::factory()->create(['event_id' => $event->id, 'price' => 100000, 'quota' => 10, 'sold' => 0]);

        session(['checkout.cart' => ['event_id' => $event->id, 'quantities' => [$type->id => 3]]]);

        Livewire::test('pages::checkout')
            ->set('buyerName', 'Sinta Wulandari')
            ->set('buyerEmail', 'sinta@example.com')
            ->call('submit')
            ->assertHasNoErrors();

        $order = Order::sole();

        $this->assertSame(Order::STATUS_PENDING, $order->status);
        $this->assertSame(300000, $order->total);
        $this->assertSame('sinta@example.com', $order->buyer_email);

        $item = OrderItem::sole();
        $this->assertSame(3, $item->quantity);
        $this->assertSame($type->id, $item->ticket_type_id);

        $this->assertSame(3, $type->refresh()->sold);
        $this->assertNull(session('checkout.cart'));
    }

    public function test_submit_fails_validation_without_buyer_name(): void
    {
        $event = $this->publishedEvent();
        $type = TicketType::factory()->create(['event_id' => $event->id, 'quota' => 10, 'sold' => 0]);

        session(['checkout.cart' => ['event_id' => $event->id, 'quantities' => [$type->id => 1]]]);

        Livewire::test('pages::checkout')
            ->set('buyerEmail', 'budi@example.com')
            ->call('submit')
            ->assertHasErrors(['buyerName']);

        $this->assertSame(0, Order::count());
        $this->assertSame(0, $type->refresh()->sold);
    }

    public function test_submit_rejects_order_when_quota_is_insufficient(): void
    {
        $event = $this->publishedEvent();
        $type = TicketType::factory()->create(['event_id' => $event->id, 'price' => 100000, 'quota' => 5, 'sold' => 4]);

        // Ask for 3 tickets while only 1 is left.
        session(['checkout.cart' => ['event_id' => $event->id, 'quantities' => [$type->id => 3]]]);

        Livewire::test('pages::checkout')
            ->set('buyerName', 'Budi Santoso')
            ->set('buyerEmail', 'budi@example.com')
            ->call('submit')
            ->assertHasErrors(['cart']);

        $this->assertSame(0, Order::count());
        $this->assertSame(4, $type->refresh()->sold);
    }

    /**
     * When a later cart line cannot be reserved, the earlier lines must be given
     * back instead of being silently kept.
     */
    public function test_partial_reservation_is_rolled_back(): void
    {
        $event = $this->publishedEvent();

        $available = TicketType::factory()->create(['event_id' => $event->id, 'price' => 50000, 'quota' => 10, 'sold' => 0]);
        $almostGone = TicketType::factory()->create(['event_id' => $event->id, 'price' => 75000, 'quota' => 4, 'sold' => 3]);

        session(['checkout.cart' => [
            'event_id' => $event->id,
            'quantities' => [
                $available->id => 2,
                $almostGone->id => 5,
            ],
        ]]);

        Livewire::test('pages::checkout')
            ->set('buyerName', 'Rita Prameswari')
            ->set('buyerEmail', 'rita@example.com')
            ->call('submit')
            ->assertHasErrors(['cart']);

        $this->assertSame(0, Order::count());
        $this->assertSame(0, $available->refresh()->sold, 'Stock for the first line must be released.');
        $this->assertSame(3, $almostGone->refresh()->sold);
    }

    public function test_submit_rejects_unpublished_event(): void
    {
        $event = Event::factory()->draft()->create();
        $type = TicketType::factory()->create(['event_id' => $event->id, 'quota' => 10, 'sold' => 0]);

        session(['checkout.cart' => ['event_id' => $event->id, 'quantities' => [$type->id => 1]]]);

        Livewire::test('pages::checkout')
            ->set('buyerName', 'Tono Santoso')
            ->set('buyerEmail', 'tono@example.com')
            ->call('submit')
            ->assertHasErrors(['cart']);

        $this->assertSame(0, Order::count());
        $this->assertSame(0, $type->refresh()->sold);
    }
}
