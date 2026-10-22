<?php

use App\Models\Event;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'kategori', except: '')]
    public string $category = '';

    #[Url(as: 'urut', except: 'terdekat')]
    public string $sort = 'terdekat';

    public function updated(string $property): void
    {
        if ($property === 'search' || $property === 'category') {
            $this->resetPage();
        }
    }

    public function setCategory(string $category): void
    {
        $this->category = $this->category === $category ? '' : $category;
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'category']);
        $this->sort = 'terdekat';
        $this->resetPage();
    }

    public function with(): array
    {
        $events = Event::query()
            ->with('venue')
            ->published()
            ->upcoming()
            ->search($this->search)
            ->when($this->category !== '', fn ($q) => $q->where('category', $this->category))
            ->when($this->sort === 'termurah', fn ($q) => $q->orderByRaw('(select min(price) from ticket_types where ticket_types.event_id = events.id) asc'))
            ->when($this->sort === 'mahal', fn ($q) => $q->orderByRaw('(select min(price) from ticket_types where ticket_types.event_id = events.id) desc'))
            ->when($this->sort === 'populer', fn ($q) => $q->withSum('ticketTypes as quota_sold_sum', 'sold')->orderByDesc('quota_sold_sum'))
            ->when($this->sort === 'terdekat', fn ($q) => $q->orderBy('starts_at'))
            ->paginate(9);

        return [
            'events' => $events,
            'categories' => Event::CATEGORIES,
        ];
    }

    public function render(): View
    {
        return view('pages.events.index')
            ->layout('layouts.public')
            ->title('Jadwal Event');
    }
};

?>

<div>
    {{-- HERO --}}
    <section class="relative overflow-hidden border-b border-base-300 bg-base-200/40">
        <div class="absolute inset-0 -z-10 bg-[radial-gradient(60%_60%_at_50%_0%,var(--color-primary)/12,transparent)]"></div>

        <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 sm:py-20">
            <div class="mx-auto max-w-3xl text-center">
                <span class="badge badge-primary badge-outline mb-5 gap-1.5">
                    <x-icon name="o-sparkles" class="size-3.5" />
                    {{ $events->total() }} event tersedia
                </span>

                <h1 class="text-4xl leading-tight font-black tracking-tight sm:text-5xl">
                    Temukan event<br class="hidden sm:block"> yang tidak mau kamu lewatkan
                </h1>

                <p class="mx-auto mt-4 max-w-xl text-base text-base-content/60">
                    Konser, summit, pameran, sampai fun run. Satu tempat untuk discovering
                    dan membeli tiketnya.
                </p>

                <div class="mx-auto mt-8 max-w-xl">
                    <x-input
                        wire:model.live.debounce.400ms="search"
                        placeholder="Cari event, kategori, atau kata kunci..."
                        icon="o-magnifying-glass"
                        clearable
                        class="input-lg w-full"
                    />
                </div>
            </div>
        </div>
    </section>

    {{-- FILTER + GRID --}}
    <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6">
        <div class="mb-6 flex flex-col gap-4">
            <div class="flex flex-wrap items-center gap-2">
                <x-button
                    label="Semua kategori"
                    wire:click="setCategory('')"
                    class="btn btn-sm {{ $category === '' ? 'btn-primary' : 'btn-ghost border-base-300' }}"
                />

                @foreach ($categories as $item)
                    <x-button
                        :label="$item"
                        wire:click="setCategory(@js($item))"
                        class="btn btn-sm {{ $category === $item ? 'btn-primary' : 'btn-ghost border-base-300' }}"
                    />
                @endforeach
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3">
                <p class="text-sm text-base-content/60">
                    Menampilkan <span class="font-semibold text-base-content">{{ $events->total() }}</span> event
                    @if ($search !== '')
                        untuk &ldquo;<span class="font-semibold">{{ $search }}</span>&rdquo;
                    @endif
                </p>

                <div class="flex items-center gap-2">
                    <label class="text-sm text-base-content/60" for="sort">Urutkan</label>
                    <x-select wire:model.live="sort" id="sort" class="select-sm w-40" :options="[
                        ['name' => 'Terdekat', 'id' => 'terdekat'],
                        ['name' => 'Paling populer', 'id' => 'populer'],
                        ['name' => 'Harga termurah', 'id' => 'termurah'],
                        ['name' => 'Harga termahal', 'id' => 'mahal'],
                    ]" option-label="name" option-value="id" />
                </div>
            </div>
        </div>

        @if ($events->isEmpty())
            <div class="rounded-2xl border border-dashed border-base-300 bg-base-200/40 py-20 text-center">
                <x-icon name="o-calendar" class="mx-auto size-12 text-base-content/30" />
                <h3 class="mt-4 text-lg font-bold">Belum ada event yang cocok</h3>
                <p class="mx-auto mt-1 max-w-sm text-sm text-base-content/60">
                    Coba kata kunci lain atau reset filter untuk melihat semua event yang akan datang.
                </p>
                <x-button label="Reset filter" icon="o-x-mark" wire:click="clearFilters" class="btn-primary btn-sm mt-5" />
            </div>
        @else
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($events as $event)
                    <x-event-card :event="$event" />
                @endforeach
            </div>

            @if ($events->hasPages())
                <div class="mt-10">
                    {{ $events->onEachSide(1)->links() }}
                </div>
            @endif
        @endif
    </section>
</div>
