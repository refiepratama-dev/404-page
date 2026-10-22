<?php

namespace Tests\Feature\Livewire;

use App\Models\Event;
use App\Models\Order;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminEventIndexTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create(['role' => 'admin']));
    }

    public function test_create_event(): void
    {
        $venue = Venue::factory()->create();

        Livewire::test('pages::admin.events.index')
            ->call('create')
            ->set('title', 'Rapat Tahunan Worker')
            ->set('category', Event::CATEGORIES[1])
            ->set('venueId', $venue->id)
            ->set('accent', 'emerald')
            ->set('startsAt', now()->addMonth()->format('Y-m-d\TH:i'))
            ->set('endsAt', now()->addMonth()->addHours(8)->format('Y-m-d\TH:i'))
            ->set('status', Event::STATUS_PUBLISHED)
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.events.show', Event::sole()));

        $event = Event::sole();

        $this->assertSame('Rapat Tahunan Worker', $event->title);
        $this->assertSame('emerald', $event->accent);
        $this->assertSame($venue->id, $event->venue_id);
        $this->assertSame(Event::STATUS_PUBLISHED, $event->status);
    }

    public function test_create_event_requires_a_title(): void
    {
        Livewire::test('pages::admin.events.index')
            ->call('create')
            ->set('title', '')
            ->call('save')
            ->assertHasErrors(['title']);
    }

    public function test_create_event_rejects_end_before_start(): void
    {
        Livewire::test('pages::admin.events.index')
            ->call('create')
            ->set('title', 'Acak Bolak Time')
            ->set('startsAt', now()->addMonth()->format('Y-m-d\TH:i'))
            ->set('endsAt', now()->subMonth()->format('Y-m-d\TH:i'))
            ->call('save')
            ->assertHasErrors(['endsAt']);
    }

    public function test_create_event_rejects_unknown_category(): void
    {
        Livewire::test('pages::admin.events.index')
            ->call('create')
            ->set('title', 'Kategori Ngawur')
            ->set('category', 'Kategori Ngawur')
            ->call('save')
            ->assertHasErrors(['category']);
    }

    public function test_edit_event_updates_fields_and_slug(): void
    {
        $event = Event::factory()->draft()->create(['title' => 'Judul Lama Sekali', 'slug' => 'judul-lama-sekali']);

        Livewire::test('pages::admin.events.index')
            ->call('edit', $event->id)
            ->assertSet('title', 'Judul Lama Sekali')
            ->set('title', 'Judul Baru Sekali')
            ->set('status', Event::STATUS_PUBLISHED)
            ->call('save')
            ->assertHasNoErrors();

        $event->refresh();

        $this->assertSame('Judul Baru Sekali', $event->title);
        $this->assertSame('judul-baru-sekali', $event->slug);
        $this->assertSame(Event::STATUS_PUBLISHED, $event->status);
    }

    public function test_toggle_status_flips_between_published_and_draft(): void
    {
        $event = Event::factory()->published()->create();

        Livewire::test('pages::admin.events.index')->call('toggleStatus', $event->id);
        $this->assertSame(Event::STATUS_DRAFT, $event->refresh()->status);

        Livewire::test('pages::admin.events.index')->call('toggleStatus', $event->id);
        $this->assertSame(Event::STATUS_PUBLISHED, $event->refresh()->status);
    }

    public function test_delete_event(): void
    {
        $event = Event::factory()->create();

        Livewire::test('pages::admin.events.index')->call('delete', $event->id);

        $this->assertNull(Event::find($event->id));
    }

    public function test_delete_event_with_orders_is_rejected(): void
    {
        $event = Event::factory()->create();
        Order::factory()->create(['event_id' => $event->id]);

        Livewire::test('pages::admin.events.index')->call('delete', $event->id);

        $this->assertNotNull(Event::find($event->id));
    }

    public function test_search_filters_rows(): void
    {
        Event::factory()->create(['title' => 'Kopdar Komunitas Seru']);
        Event::factory()->create(['title' => 'Pesta Vibrasi Viby']);

        Livewire::test('pages::admin.events.index')
            ->set('search', 'Kopdar')
            ->assertSee('Kopdar Komunitas Seru')
            ->assertDontSee('Pesta Vibrasi Viby');
    }
}
