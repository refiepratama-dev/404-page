<?php

namespace Tests\Feature\Livewire;

use App\Models\Event;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\TicketType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminOrdersTest extends TestCase
{
    use RefreshDatabase;

    private Event $event;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->event = Event::factory()->published()->create();
    }

    private function ordersPage()
    {
        return Livewire::test('pages::admin.orders.index');
    }

    public function test_mark_paid_marks_the_order_as_paid(): void
    {
        $order = Order::factory()->pending()->create(['event_id' => $this->event->id, 'total' => 150000]);

        $this->ordersPage()->call('markPaid', $order->id);

        $order->refresh();

        $this->assertSame(Order::STATUS_PAID, $order->status);
        $this->assertNotNull($order->paid_at);
    }

    public function test_mark_paid_is_a_no_op_for_cancelled_orders(): void
    {
        $order = Order::factory()->create(['event_id' => $this->event->id, 'status' => Order::STATUS_CANCELLED]);

        $this->ordersPage()->call('markPaid', $order->id);

        $this->assertSame(Order::STATUS_CANCELLED, $order->refresh()->status);
    }

    public function test_cancel_marks_cancelled_and_releases_stock(): void
    {
        $type = TicketType::factory()->sold(10, 50)->create(['event_id' => $this->event->id]);
        $order = Order::factory()->paid()->create(['event_id' => $this->event->id]);
        OrderItem::factory()->create(['order_id' => $order->id, 'ticket_type_id' => $type->id, 'quantity' => 3]);

        $this->ordersPage()->call('cancel', $order->id);

        $this->assertSame(Order::STATUS_CANCELLED, $order->refresh()->status);
        $this->assertSame(7, $type->refresh()->sold);
    }

    public function test_cancel_releases_stock_for_every_line(): void
    {
        $first = TicketType::factory()->sold(5, 20)->create(['event_id' => $this->event->id]);
        $second = TicketType::factory()->sold(2, 20)->create(['event_id' => $this->event->id]);

        $order = Order::factory()->paid()->create(['event_id' => $this->event->id]);
        OrderItem::factory()->create(['order_id' => $order->id, 'ticket_type_id' => $first->id, 'quantity' => 4]);
        OrderItem::factory()->create(['order_id' => $order->id, 'ticket_type_id' => $second->id, 'quantity' => 1]);

        $this->ordersPage()->call('cancel', $order->id);

        $this->assertSame(1, $first->refresh()->sold);
        $this->assertSame(1, $second->refresh()->sold);
    }

    public function test_cancelled_stock_can_be_resold(): void
    {
        $type = TicketType::factory()->sold(10, 10)->create(['event_id' => $this->event->id]);
        $order = Order::factory()->paid()->create(['event_id' => $this->event->id]);
        OrderItem::factory()->create(['order_id' => $order->id, 'ticket_type_id' => $type->id, 'quantity' => 4]);

        $this->assertTrue($type->isSoldOut());

        $this->ordersPage()->call('cancel', $order->id);

        $this->assertSame(6, $type->refresh()->sold);
        $this->assertFalse($type->isSoldOut());
        $this->assertTrue($type->reserve(4));
    }

    public function test_cancel_is_idempotent(): void
    {
        $type = TicketType::factory()->sold(10, 20)->create(['event_id' => $this->event->id]);
        $order = Order::factory()->paid()->create(['event_id' => $this->event->id]);
        OrderItem::factory()->create(['order_id' => $order->id, 'ticket_type_id' => $type->id, 'quantity' => 3]);

        $this->ordersPage()->call('cancel', $order->id);
        $this->ordersPage()->call('cancel', $order->id);

        $this->assertSame(7, $type->refresh()->sold);
    }

    public function test_show_detail_opens_the_modal(): void
    {
        $order = Order::factory()->create(['event_id' => $this->event->id]);

        $this->ordersPage()
            ->call('showDetail', $order->id)
            ->assertSet('detailId', $order->id)
            ->assertSet('detailModal', true);
    }

    public function test_search_matches_buyer_name(): void
    {
        Order::factory()->create(['event_id' => $this->event->id, 'buyer_name' => 'Gita kumala']);
        Order::factory()->create(['event_id' => $this->event->id, 'buyer_name' => 'Budi Santoso']);

        $this->ordersPage()
            ->set('search', 'Gita')
            ->assertSee('Gita kumala')
            ->assertDontSee('Budi Santoso');
    }
}
