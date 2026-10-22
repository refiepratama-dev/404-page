<?php

namespace Tests\Feature\Livewire;

use App\Models\Event;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminVenuesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create(['role' => 'admin']));
    }

    public function test_create_venue(): void
    {
        Livewire::test('pages::admin.venues.index')
            ->call('create')
            ->set('name', 'Istora Senayan')
            ->set('address', 'Jl. Pintu Satu Senayan')
            ->set('city', 'Jakarta Pusat')
            ->set('capacity', '7000')
            ->call('save')
            ->assertHasNoErrors();

        $venue = Venue::sole();

        $this->assertSame('Istora Senayan', $venue->name);
        $this->assertSame(7000, $venue->capacity);
    }

    public function test_capacity_is_optional(): void
    {
        Livewire::test('pages::admin.venues.index')
            ->call('create')
            ->set('name', 'Lapangan Komplek')
            ->set('address', 'Blok C')
            ->set('city', 'Bekasi')
            ->set('capacity', '')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertNull(Venue::sole()->capacity);
    }

    public function test_validation_rules(): void
    {
        Livewire::test('pages::admin.venues.index')
            ->call('create')
            ->set('name', '')
            ->set('address', '')
            ->set('city', '')
            ->set('capacity', 'banyak')
            ->call('save')
            ->assertHasErrors(['name', 'address', 'city', 'capacity']);
    }

    public function test_edit_venue(): void
    {
        $venue = Venue::factory()->create(['name' => 'Venue Lama']);

        Livewire::test('pages::admin.venues.index')
            ->call('edit', $venue->id)
            ->set('name', 'Venue Baru')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Venue Baru', $venue->refresh()->name);
    }

    public function test_delete_venue(): void
    {
        $venue = Venue::factory()->create();

        Livewire::test('pages::admin.venues.index')->call('delete', $venue->id);

        $this->assertNull(Venue::find($venue->id));
    }

    public function test_delete_venue_used_by_events_is_rejected(): void
    {
        $venue = Venue::factory()->create();
        Event::factory()->create(['venue_id' => $venue->id]);

        Livewire::test('pages::admin.venues.index')->call('delete', $venue->id);

        $this->assertNotNull(Venue::find($venue->id));
    }

    public function test_search_filters_venues(): void
    {
        Venue::factory()->create(['name' => 'Gedung Serbaguna']);
        Venue::factory()->create(['name' => 'Ruang Nada', 'city' => 'Surabaya']);

        Livewire::test('pages::admin.venues.index')
            ->set('search', 'Serbaguna')
            ->assertSee('Gedung Serbaguna')
            ->assertDontSee('Ruang Nada');
    }
}
