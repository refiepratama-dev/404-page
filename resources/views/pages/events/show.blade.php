<?php

use App\Models\Event;
use App\Support\Format;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public Event $event;

    /** @var array<int, int> */
    public array $quantities = [];

    public string $quantityError = '';

    public function mount(Event $event): void
    {
        abort_unless($event->isPublished(), 404);

        $this->event = $event->load(['venue', 'sessions', 'ticketTypes']);
        $this->quantities = $this->event->ticketTypes
            ->mapWithKeys(fn ($type) => [$type->id => 0])
            ->all();
    }

    #[Computed]
    public function lines(): array
    {
        $lines = [];

        foreach ($this->event->ticketTypes as $type) {
            $qty = (int) ($this->quantities[$type->id] ?? 0);

            if ($qty < 1) {
                continue;
            }

            $lines[] = [
                'type' => $type,
                'quantity' => $qty,
                'subtotal' => $qty * $type->price,
            ];
        }

        return $lines;
    }

    #[Computed]
    public function total(): int
    {
        return (int) array_sum(array_column($this->lines, 'subtotal'));
    }

    #[Computed]
    public function totalQuantity(): int
    {
        return (int) array_sum(array_column($this->lines, 'quantity'));
    }

    public function increment(int $ticketTypeId): void
    {
        $type = $this->event->ticketTypes->firstWhere('id', $ticketTypeId);

        if (! $type || $type->isSoldOut() || ! $type->isOnSale()) {
            return;
        }

        $current = (int) ($this->quantities[$ticketTypeId] ?? 0);

        if ($current + 1 > $type->remaining) {
            $this->quantityError = 'Kuota '.$type->name.' tinggal '.$type->remaining.' tiket.';

            return;
        }

        if ($current + 1 > 10) {
            $this->quantityError = 'Maksimal 10 tiket per tipe per transaksi.';

            return;
        }

        $this->quantityError = '';
        $this->quantities[$ticketTypeId] = $current + 1;
    }

    public function decrement(int $ticketTypeId): void
    {
        $current = (int) ($this->quantities[$ticketTypeId] ?? 0);
        $this->quantities[$ticketTypeId] = max(0, $current - 1);
        $this->quantityError = '';
    }

    public function setQuantity(int $ticketTypeId, string $value): void
    {
        $type = $this->event->ticketTypes->firstWhere('id', $ticketTypeId);

        if (! $type) {
            return;
        }

        $qty = max(0, (int) $value);
        $qty = min($qty, $type->remaining, 10);

        $this->quantities[$ticketTypeId] = $qty;
        $this->quantityError = '';
    }

    public function proceedToCheckout(): void
    {
        $this->validate([
            'quantities' => ['required', 'array', 'min:1'],
        ], [
            'quantities.required' => 'Pilih minimal satu tiket sebelum melanjutkan.',
        ], [
            'quantities' => 'pilihan tiket',
        ]);

        $cart = [];

        foreach ($this->lines as $line) {
            $cart[$line['type']->id] = $line['quantity'];
        }

        if ($cart === []) {
            $this->addError('quantities', 'Pilih minimal satu tiket sebelum melanjutkan.');

            return;
        }

        session()->put('checkout.cart', [
            'event_id' => $this->event->id,
            'quantities' => $cart,
        ]);

        $this->redirectRoute('checkout', navigate: true);
    }

    public function render(): View
    {
        return view('pages.events.show')
            ->layout('layouts.public')
            ->title($this->event->title);
    }
};

?>

<div>
    {{-- BREADCRUMB --}}
    <div class="mx-auto max-w-7xl px-4 pt-6 sm:px-6">
        <div class="breadcrumbs text-sm text-base-content/60">
            <ul>
                <li><a href="{{ route('events.index') }}" wire:navigate>Event</a></li>
                <li><a href="{{ route('events.index', ['kategori' => $event->category]) }}" wire:navigate>{{ $event->category }}</a></li>
                <li class="font-medium text-base-content">{{ $event->title }}</li>
            </ul>
        </div>
    </div>

    <div class="mx-auto max-w-7xl gap-10 px-4 py-6 sm:px-6 lg:flex lg:items-start">
        {{-- LEFT --}}
        <div class="min-w-0 flex-1">
            {{-- BANNER --}}
            <div class="overflow-hidden rounded-2xl">
                <x-event-artwork
                    :accent="$event->accent"
                    :starts-at="$event->starts_at"
                    class="h-56 sm:h-72"
                />
            </div>

            {{-- TITLE --}}
            <div class="mt-6">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="badge badge-primary badge-outline">{{ $event->category }}</span>
                    <span class="badge badge-ghost gap-1.5">
                        <x-icon name="o-clock" class="size-3.5" />
                        {{ Format::countdown($event->starts_at) }}
                    </span>
                </div>

                <h1 class="mt-3 text-3xl leading-tight font-black tracking-tight sm:text-4xl">
                    {{ $event->title }}
                </h1>

                <div class="mt-5 grid gap-4 sm:grid-cols-2">
                    <div class="flex gap-3 rounded-xl border border-base-300 bg-base-100 p-4">
                        <x-icon name="o-calendar-days" class="size-5 shrink-0 text-primary" />
                        <div>
                            <p class="text-xs uppercase tracking-wider text-base-content/50">Tanggal</p>
                            <p class="text-sm font-semibold">{{ Format::dateLong($event->starts_at) }}</p>
                            <p class="text-xs text-base-content/50">sampai {{ $event->ends_at->format('H:i') }} WIB</p>
                        </div>
                    </div>

                    <div class="flex gap-3 rounded-xl border border-base-300 bg-base-100 p-4">
                        <x-icon name="o-map-pin" class="size-5 shrink-0 text-primary" />
                        <div class="min-w-0">
                            <p class="text-xs uppercase tracking-wider text-base-content/50">Lokasi</p>
                            <p class="truncate text-sm font-semibold">{{ $event->venue?->name ?? 'Menyusul' }}</p>
                            <p class="truncate text-xs text-base-content/50">
                                {{ $event->venue?->address }}, {{ $event->venue?->city }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- DESKRIPSI --}}
            @if ($event->description)
                <div class="mt-8">
                    <h2 class="text-lg font-bold">Tentang event</h2>
                    <div class="prose prose-sm mt-3 max-w-none text-base-content/75">
                        @foreach (explode("\n\n", $event->description) as $paragraph)
                            <p>{{ $paragraph }}</p>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- SESI / JADWAL --}}
            @if ($event->sessions->isNotEmpty())
                <div class="mt-10">
                    <h2 class="text-lg font-bold">Susunan acara</h2>

                    <ol class="mt-4 space-y-3">
                        @foreach ($event->sessions as $session)
                            <li class="flex gap-4 rounded-xl border border-base-300 bg-base-100 p-4">
                                <div class="shrink-0 text-center">
                                    <p class="text-sm font-black">{{ Format::time($session->starts_at) }}</p>
                                    <p class="text-[0.65rem] text-base-content/50">s/d {{ Format::time($session->ends_at) }}</p>
                                </div>
                                <div class="min-w-0 flex-1 border-l border-base-300 pl-4">
                                    <p class="font-semibold">{{ $session->title }}</p>
                                    @if ($session->speaker)
                                        <p class="text-sm text-base-content/60">{{ $session->speaker }}</p>
                                    @endif
                                    @if ($session->track)
                                        <span class="badge badge-ghost badge-sm mt-2">{{ $session->track }}</span>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ol>
                </div>
            @endif
        </div>

        {{-- RIGHT: TICKET PICKER --}}
        <div class="mt-10 w-full shrink-0 lg:sticky lg:top-24 lg:mt-0 lg:w-96">
            <x-card shadow class="border border-base-300">
                <x-slot:title class="text-base font-bold">Pilih tiket</x-slot:title>
                <x-slot:subtitle class="text-xs">
                    {{ Format::number($event->quota_total - $event->quota_sold) }} tiket tersisa
                </x-slot:subtitle>

                <div class="space-y-3">
                    @forelse ($event->ticketTypes as $type)
                        @php $qty = (int) ($quantities[$type->id] ?? 0); @endphp

                        <div class="rounded-xl border p-3 transition {{ $qty > 0 ? 'border-primary bg-primary/5' : 'border-base-300' }}">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-sm font-bold">{{ $type->name }}</p>
                                    @if ($type->description)
                                        <p class="mt-0.5 text-xs leading-relaxed text-base-content/60">{{ $type->description }}</p>
                                    @endif
                                </div>
                                <div class="shrink-0 text-right">
                                    <p class="text-sm font-black">{{ Format::rupiah($type->price) }}</p>
                                    <p class="text-[0.65rem] text-base-content/50">sisa {{ $type->remaining }}</p>
                                </div>
                            </div>

                            <div class="mt-3 flex items-center justify-between">
                                @if ($type->isSoldOut() || ! $type->isOnSale())
                                    <span class="badge badge-ghost badge-sm">Tidak tersedia</span>
                                @else
                                    <span class="text-[0.65rem] text-base-content/50">
                                        {{ $type->remaining <= 20 ? 'Hampir habis!' : 'Tersedia' }}
                                    </span>

                                    <div class="flex items-center gap-1">
                                        <button
                                            type="button"
                                            class="btn btn-circle btn-ghost btn-xs"
                                            wire:click="decrement({{ $type->id }})"
                                            wire:loading.attr="disabled"
                                            aria-label="Kurangi {{ $type->name }}"
                                        >
                                            <x-icon name="o-minus" class="size-3.5" />
                                        </button>

                                        <input
                                            type="number"
                                            min="0"
                                            max="{{ min($type->remaining, 10) }}"
                                            value="{{ $qty }}"
                                            wire:change="setQuantity({{ $type->id }}, $event.target.value)"
                                            class="input input-sm w-14 text-center font-bold"
                                            aria-label="Jumlah tiket {{ $type->name }}"
                                        />

                                        <button
                                            type="button"
                                            class="btn btn-circle btn-ghost btn-xs"
                                            wire:click="increment({{ $type->id }})"
                                            wire:loading.attr="disabled"
                                            aria-label="Tambah {{ $type->name }}"
                                        >
                                            <x-icon name="o-plus" class="size-3.5" />
                                        </button>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="py-6 text-center text-sm text-base-content/60">
                            Tiket untuk event ini belum tersedia.
                        </p>
                    @endforelse
                </div>

                @error('quantities')
                    <p class="mt-3 text-sm text-error">{{ $message }}</p>
                @enderror

                @if ($quantityError !== '')
                    <p class="mt-3 text-sm text-error">{{ $quantityError }}</p>
                @endif

                @if ($this->lines())
                    <div class="mt-5 space-y-1.5 border-t border-base-300 pt-4 text-sm">
                        @foreach ($this->lines() as $line)
                            <div class="flex justify-between">
                                <span class="text-base-content/70">
                                    {{ $line['quantity'] }}&times; {{ $line['type']->name }}
                                </span>
                                <span class="font-medium">{{ Format::rupiah($line['subtotal']) }}</span>
                            </div>
                        @endforeach

                        <div class="flex justify-between border-t border-base-300 pt-2 text-base font-black">
                            <span>Total</span>
                            <span class="text-primary">{{ Format::rupiah($this->total()) }}</span>
                        </div>
                    </div>
                @endif

                <x-button
                    label="Lanjut ke pembayaran"
                    icon-right="o-arrow-right"
                    class="btn-primary btn-block mt-5"
                    wire:click="proceedToCheckout"
                    wire:loading.attr="disabled"
                    wire:loading.class="btn-loading"
                    :disabled="$this->totalQuantity() === 0"
                />
            </x-card>

            <p class="mt-3 flex items-start gap-2 text-xs text-base-content/50">
                <x-icon name="o-shield-check" class="mt-0.5 size-4 shrink-0" />
                Tiket dipesan langsung dari kuota resmi. Stok diperbarui otomatis saat checkout.
            </p>
        </div>
    </div>
</div>
