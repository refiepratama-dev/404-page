<?php

namespace Tests\Feature\Livewire;

use App\Models\Event;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\TicketType;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminEventShowTest extends TestCase
{
    use RefreshDatabase;

    private Event $event;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->event = Event::factory()->published()->create();
    }

    private function adminPage()
    {
        return Livewire::test('pages::admin.events.show', ['event' => $this->event]);
    }

    public function test_toggle_status(): void
    {
        $this->adminPage()->call('toggleStatus');

        $this->assertSame(Event::STATUS_DRAFT, $this->event->refresh()->status);
    }

    public function test_create_ticket_type(): void
    {
        $this->adminPage()
            ->call('createTicket')
            ->set('ticketName', 'Early Bird')
            ->set('ticketDescription', 'Harga khusus awal')
            ->set('ticketPrice', '150000')
            ->set('ticketQuota', '250')
            ->set('ticketSalesStart', now()->format('Y-m-d\TH:i'))
            ->set('ticketSalesEnd', now()->addWeek()->format('Y-m-d\TH:i'))
            ->call('saveTicket')
            ->assertHasNoErrors();

        $type = TicketType::sole();

        $this->assertSame('Early Bird', $type->name);
        $this->assertSame(150000, $type->price);
        $this->assertSame(250, $type->quota);
        $this->assertSame(0, $type->sold);
        $this->assertSame($this->event->id, $type->event_id);
    }

    public function test_ticket_price_must_be_an_integer(): void
    {
        $this->adminPage()
            ->call('createTicket')
            ->set('ticketName', 'Harga Ngawur')
            ->set('ticketPrice', '150.000')
            ->set('ticketQuota', '10')
            ->call('saveTicket')
            ->assertHasErrors(['ticketPrice']);
    }

    public function test_ticket_quota_must_be_at_least_one(): void
    {
        $this->adminPage()
            ->call('createTicket')
            ->set('ticketName', 'Kuota Ngawur')
            ->set('ticketPrice', '10000')
            ->set('ticketQuota', '0')
            ->call('saveTicket')
            ->assertHasErrors(['ticketQuota']);
    }

    public function test_ticket_sales_window_must_be_ordered(): void
    {
        $this->adminPage()
            ->call('createTicket')
            ->set('ticketName', 'Jendela Ngawur')
            ->set('ticketPrice', '10000')
            ->set('ticketQuota', '10')
            ->set('ticketSalesStart', now()->addWeek()->format('Y-m-d\TH:i'))
            ->set('ticketSalesEnd', now()->format('Y-m-d\TH:i'))
            ->call('saveTicket')
            ->assertHasErrors(['ticketSalesEnd']);
    }

    public function test_quota_cannot_drop_below_sold(): void
    {
        $type = TicketType::factory()->sold(8, 10)->create(['event_id' => $this->event->id]);

        $this->adminPage()
            ->call('editTicket', $type->id)
            ->set('ticketQuota', '5')
            ->call('saveTicket')
            ->assertHasErrors(['ticketQuota']);

        $this->assertSame(10, $type->refresh()->quota);
    }

    public function test_edit_ticket_type(): void
    {
        $type = TicketType::factory()->create(['event_id' => $this->event->id, 'name' => 'Reguler', 'price' => 100000]);

        $this->adminPage()
            ->call('editTicket', $type->id)
            ->set('ticketName', 'Reguler Plus')
            ->set('ticketPrice', '125000')
            ->call('saveTicket')
            ->assertHasNoErrors();

        $type->refresh();

        $this->assertSame('Reguler Plus', $type->name);
        $this->assertSame(125000, $type->price);
    }

    public function test_delete_ticket_type(): void
    {
        $type = TicketType::factory()->create(['event_id' => $this->event->id]);

        $this->adminPage()->call('deleteTicket', $type->id);

        $this->assertNull(TicketType::find($type->id));
    }

    public function test_delete_ticket_type_used_by_orders_is_rejected(): void
    {
        $type = TicketType::factory()->create(['event_id' => $this->event->id]);
        $order = Order::factory()->create(['event_id' => $this->event->id]);
        OrderItem::factory()->create(['order_id' => $order->id, 'ticket_type_id' => $type->id]);

        $this->adminPage()->call('deleteTicket', $type->id);

        $this->assertNotNull(TicketType::find($type->id));
    }

    public function test_ticket_of_another_event_is_not_editable(): void
    {
        $other = Event::factory()->create();
        $type = TicketType::factory()->create(['event_id' => $other->id, 'name' => 'Milik Lass']);

        try {
            $this->adminPage()->call('editTicket', $type->id);
        } catch (ModelNotFoundException) {
            // Expected: the ticket is scoped to the current event.
        }

        $this->assertSame('Milik Lass', $type->refresh()->name);
        $this->assertNull($this->adminPage()->get('editingTicketId'));
    }

    public function test_create_session(): void
    {
        $this->adminPage()
            ->call('createSession')
            ->set('sessionTitle', 'Keynote')
            ->set('sessionSpeaker', 'Sari Wulandari')
            ->set('sessionTrack', 'Main Stage')
            ->set('sessionStartsAt', now()->addDays(3)->format('Y-m-d\TH:i'))
            ->set('sessionEndsAt', now()->addDays(3)->addHour()->format('Y-m-d\TH:i'))
            ->call('saveSession')
            ->assertHasNoErrors();

        $session = $this->event->sessions()->sole();

        $this->assertSame('Keynote', $session->title);
        $this->assertSame('Sari Wulandari', $session->speaker);
        $this->assertSame(0, $session->sort_order);
    }

    public function test_session_end_must_not_precede_start(): void
    {
        $this->adminPage()
            ->call('createSession')
            ->set('sessionTitle', 'Sesi Ngawur')
            ->set('sessionStartsAt', now()->addDay()->format('Y-m-d\TH:i'))
            ->set('sessionEndsAt', now()->subDay()->format('Y-m-d\TH:i'))
            ->call('saveSession')
            ->assertHasErrors(['sessionEndsAt']);
    }

    public function test_edit_and_delete_session(): void
    {
        $session = $this->event->sessions()->create([
            'title' => 'Sesi Lama',
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addHour(),
            'sort_order' => 0,
        ]);

        $this->adminPage()
            ->call('editSession', $session->id)
            ->set('sessionTitle', 'Sesi Baru')
            ->call('saveSession')
            ->assertHasNoErrors();

        $this->assertSame('Sesi Baru', $session->refresh()->title);

        $this->adminPage()->call('deleteSession', $session->id);

        $this->assertNull($session->fresh());
    }
}
