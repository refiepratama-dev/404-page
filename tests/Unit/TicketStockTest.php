<?php

namespace Tests\Unit;

use App\Models\Event;
use App\Models\TicketType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketStockTest extends TestCase
{
    use RefreshDatabase;

    private function ticket(int $quota, int $sold = 0): TicketType
    {
        $event = Event::factory()->create();

        return TicketType::factory()->create([
            'event_id' => $event->id,
            'quota' => $quota,
            'sold' => $sold,
        ]);
    }

    public function test_remaining_is_quota_minus_sold(): void
    {
        $this->assertSame(7, $this->ticket(quota: 10, sold: 3)->remaining);
    }

    public function test_remaining_never_goes_below_zero(): void
    {
        $this->assertSame(0, $this->ticket(quota: 5, sold: 9)->remaining);
    }

    public function test_sold_out_detection(): void
    {
        $this->assertTrue($this->ticket(quota: 5, sold: 5)->isSoldOut());
        $this->assertFalse($this->ticket(quota: 5, sold: 4)->isSoldOut());
    }

    public function test_reserve_increases_sold(): void
    {
        $type = $this->ticket(quota: 10);

        $this->assertTrue($type->reserve(4));
        $this->assertSame(4, $type->refresh()->sold);
        $this->assertSame(6, $type->remaining);
    }

    public function test_reserve_fills_the_quota_exactly(): void
    {
        $type = $this->ticket(quota: 10);

        $this->assertTrue($type->reserve(10));
        $this->assertTrue($type->refresh()->isSoldOut());
    }

    public function test_reserve_beyond_quota_is_rejected_and_leaves_stock_untouched(): void
    {
        $type = $this->ticket(quota: 10, sold: 8);

        $this->assertFalse($type->reserve(3));
        $this->assertSame(8, $type->refresh()->sold);
    }

    public function test_only_the_exact_remaining_amount_can_be_reserved(): void
    {
        $type = $this->ticket(quota: 10, sold: 9);

        $this->assertFalse($type->reserve(2), 'One more than the remaining amount must be rejected.');
        $this->assertTrue($type->reserve(1), 'The exact remaining amount must be accepted.');
        $this->assertTrue($type->refresh()->isSoldOut());
    }

    public function test_reserve_ignores_non_positive_quantities(): void
    {
        $type = $this->ticket(quota: 10, sold: 2);

        $this->assertFalse($type->reserve(0));
        $this->assertFalse($type->reserve(-5));
        $this->assertSame(2, $type->refresh()->sold);
    }

    public function test_release_gives_stock_back(): void
    {
        $type = $this->ticket(quota: 10, sold: 10);

        $type->release(4);

        $this->assertSame(6, $type->refresh()->sold);
    }

    public function test_release_never_pushes_sold_below_zero(): void
    {
        $type = $this->ticket(quota: 10, sold: 2);

        $type->release(5);

        $this->assertSame(0, $type->refresh()->sold);
    }

    public function test_release_ignores_non_positive_quantities(): void
    {
        $type = $this->ticket(quota: 10, sold: 3);

        $type->release(0);
        $type->release(-2);

        $this->assertSame(3, $type->refresh()->sold);
    }

    public function test_reserve_then_release_returns_to_the_original_value(): void
    {
        $type = $this->ticket(quota: 20, sold: 4);

        $type->reserve(6);
        $type->release(6);

        $this->assertSame(4, $type->refresh()->sold);
    }

    public function test_is_on_sale_respects_the_sales_window(): void
    {
        $open = $this->ticket(quota: 10);
        $this->assertTrue($open->isOnSale());

        $future = TicketType::factory()->create([
            'event_id' => Event::factory()->create()->id,
            'sales_start' => now()->addWeek(),
        ]);
        $this->assertFalse($future->isOnSale());

        $past = TicketType::factory()->create([
            'event_id' => Event::factory()->create()->id,
            'sales_end' => now()->subDay(),
        ]);
        $this->assertFalse($past->isOnSale());
    }

    public function test_on_sale_scope_filters_sold_out_and_out_of_window_types(): void
    {
        $event = Event::factory()->create();

        $open = TicketType::factory()->create(['event_id' => $event->id, 'quota' => 10, 'sold' => 1]);
        $soldOut = TicketType::factory()->create(['event_id' => $event->id, 'quota' => 5, 'sold' => 5]);
        $notStarted = TicketType::factory()->create(['event_id' => $event->id, 'sales_start' => now()->addWeek()]);
        $ended = TicketType::factory()->create(['event_id' => $event->id, 'sales_end' => now()->subDay()]);

        $onSale = TicketType::onSale()->pluck('id');

        $this->assertTrue($onSale->contains($open->id));
        $this->assertFalse($onSale->contains($soldOut->id));
        $this->assertFalse($onSale->contains($notStarted->id));
        $this->assertFalse($onSale->contains($ended->id));
    }
}
