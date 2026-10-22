<?php

use App\Exceptions\CheckoutFailed;
use App\Models\Event;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\TicketType;
use App\Support\Format;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Mary\Traits\Toast;

new class extends Component
{
    use Toast;

    public ?int $orderId = null;

    public string $buyerName = '';

    public string $buyerEmail = '';

    public string $buyerPhone = '';

    public string $notes = '';

    public function mount(): void
    {
        if (! session()->has('checkout.cart')) {
            $this->redirectRoute('events.index', navigate: true);

            return;
        }

        $user = auth()->user();

        if ($user) {
            $this->buyerName = $user->name;
            $this->buyerEmail = $user->email;
        }
    }

    public function submit(): void
    {
        $this->validate([
            'buyerName' => ['required', 'string', 'min:3', 'max:120'],
            'buyerEmail' => ['required', 'email', 'max:180'],
            'buyerPhone' => ['nullable', 'string', 'min:8', 'max:25'],
            'notes' => ['nullable', 'string', 'max:500'],
        ], [
            'buyerName.required' => 'Nama pemesan wajib diisi.',
            'buyerName.min' => 'Nama minimal 3 karakter.',
            'buyerEmail.required' => 'Email wajib diisi.',
            'buyerEmail.email' => 'Format email tidak valid.',
            'buyerPhone.min' => 'Nomor telepon minimal 8 karakter.',
        ], [
            'buyerName' => 'nama pemesan',
            'buyerEmail' => 'email',
            'buyerPhone' => 'nomor telepon',
            'notes' => 'catatan',
        ]);

        if (! $this->event) {
            $this->redirectRoute('events.index', navigate: true);

            return;
        }

        // Anything that cannot be reserved throws, so the whole transaction rolls
        // back and stock reserved for earlier cart lines is given back.
        try {
            $order = DB::transaction(function () {
                $cart = session('checkout.cart');
                $event = $this->event;

                if (! $event->isPublished()) {
                    throw new CheckoutFailed('Event ini sudah tidak menerima pemesanan.');
                }

                $lines = [];
                $total = 0;

                foreach ($cart['quantities'] as $ticketTypeId => $quantity) {
                    $quantity = (int) $quantity;

                    if ($quantity < 1) {
                        continue;
                    }

                    /** @var TicketType|null $type */
                    $type = TicketType::find($ticketTypeId);

                    // A ticket type that was deleted or delisted mid-checkout is skipped.
                    if (! $type || $type->event_id !== $event->id) {
                        continue;
                    }

                    // Atomic reservation: the UPDATE only lands when the quota still fits,
                    // so two concurrent checkouts can never oversell the same ticket type.
                    if (! $type->reserve($quantity)) {
                        throw new CheckoutFailed(
                            'Kuota tiket '.$type->name.' tidak mencukupi lagi. Muat ulang halaman untuk melihat stok terbaru.'
                        );
                    }

                    $subtotal = $type->price * $quantity;
                    $total += $subtotal;

                    $lines[] = compact('type', 'quantity', 'subtotal');
                }

                if ($lines === []) {
                    throw new CheckoutFailed('Keranjang tiket kosong.');
                }

                $order = Order::create([
                    'order_number' => Order::generateNumber(),
                    'event_id' => $event->id,
                    'buyer_name' => $this->buyerName,
                    'buyer_email' => $this->buyerEmail,
                    'buyer_phone' => $this->buyerPhone ?: null,
                    'notes' => $this->notes ?: null,
                    'total' => $total,
                    'status' => Order::STATUS_PENDING,
                ]);

                foreach ($lines as $line) {
                    OrderItem::create([
                        'order_id' => $order->id,
                        'ticket_type_id' => $line['type']->id,
                        'quantity' => $line['quantity'],
                        'unit_price' => $line['type']->price,
                        'subtotal' => $line['subtotal'],
                    ]);
                }

                return $order;
            });
        } catch (CheckoutFailed $e) {
            $this->addError('cart', $e->getMessage());
            $this->error('Pesanan gagal dibuat. Periksa kembali keranjang tiket.', position: 'toast-top');

            return;
        }

        session()->forget('checkout.cart');
        $this->orderId = $order->id;
        $this->success('Pesanan berhasil dibuat!', position: 'toast-top');
    }

    #[Computed]
    public function event(): ?Event
    {
        $eventId = session('checkout.cart.event_id');

        if (! $eventId) {
            return null;
        }

        return Event::with('venue', 'ticketTypes')->find($eventId);
    }

    #[Computed]
    public function order(): ?Order
    {
        return $this->orderId ? Order::with('items.ticketType', 'event')->find($this->orderId) : null;
    }

    #[Computed]
    public function lines(): array
    {
        $event = $this->event;

        if (! $event) {
            return [];
        }

        $quantities = session('checkout.cart.quantities', []);
        $lines = [];

        foreach ($event->ticketTypes as $type) {
            $quantity = (int) ($quantities[$type->id] ?? 0);

            if ($quantity < 1) {
                continue;
            }

            $lines[] = [
                'type' => $type,
                'quantity' => $quantity,
                'subtotal' => $quantity * $type->price,
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

    public function render(): View
    {
        return view('pages.checkout')
            ->layout('layouts.public')
            ->title('Checkout');
    }
};

?>

<div class="mx-auto max-w-5xl px-4 py-10 sm:px-6">
    @if ($this->order)
        {{-- SUCCESS --}}
        <div class="card mx-auto max-w-2xl border border-success/30 bg-base-100 shadow-lg">
            <div class="card-body items-center text-center">
                <div class="flex size-16 items-center justify-center rounded-full bg-success/15 text-success">
                    <x-icon name="o-check-badge" class="size-9" />
                </div>

                <h1 class="card-title mt-3 text-2xl">Pesanan berhasil dibuat</h1>
                <p class="text-sm text-base-content/60">
                    Simpan nomor pesanan berikut. Detail tiket akan dikirim ke
                    <span class="font-semibold text-base-content">{{ $this->order->buyer_email }}</span>.
                </p>

                <div class="mt-4 w-full rounded-xl border border-dashed border-base-300 bg-base-200/50 p-5">
                    <p class="text-xs uppercase tracking-wider text-base-content/50">Nomor pesanan</p>
                    <p class="mt-1 font-mono text-xl font-black">{{ $this->order->order_number }}</p>
                </div>

                <div class="mt-2 w-full overflow-hidden rounded-xl border border-base-300 text-left">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Tiket</th>
                                <th class="text-right">Jumlah</th>
                                <th class="text-right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($this->order->items as $item)
                                <tr>
                                    <td>{{ $item->ticketType->name }}</td>
                                    <td class="text-right">{{ $item->quantity }}</td>
                                    <td class="text-right font-medium">{{ Format::rupiah($item->subtotal) }}</td>
                                </tr>
                            @endforeach
                            <tr class="font-black">
                                <td colspan="2">Total</td>
                                <td class="text-right text-primary">{{ Format::rupiah($this->order->total) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="card-actions mt-4">
                    <x-button label="Kembali ke daftar event" link="{{ route('events.index') }}" wire:navigate />
                    <x-button label="Lihat event" link="{{ route('events.show', $this->order->event) }}" wire:navigate class="btn-primary" />
                </div>
            </div>
        </div>
    @elseif (! $this->event)
        {{-- CART KOSONG --}}
        <div class="py-20 text-center">
            <x-icon name="o-shopping-cart" class="mx-auto size-12 text-base-content/30" />
            <h1 class="mt-4 text-xl font-bold">Keranjang tiket kosong</h1>
            <p class="mx-auto mt-1 max-w-sm text-sm text-base-content/60">
                Pilih event dan tipe tiket terlebih dahulu sebelum melanjutkan ke checkout.
            </p>
            <x-button label="Lihat event" link="{{ route('events.index') }}" wire:navigate class="btn-primary mt-5" />
        </div>
    @else
        {{-- FORM --}}
        <h1 class="text-2xl font-black tracking-tight sm:text-3xl">Checkout</h1>
        <p class="mt-1 text-sm text-base-content/60">
            Lengkapi data pemesan untuk {{ $this->event->title }}.
        </p>

        <div class="mt-8 gap-8 lg:flex lg:items-start">
            {{-- FORM --}}
            <div class="min-w-0 flex-1">
                <x-card shadow class="border border-base-300">
                    <x-slot:title class="text-base font-bold">Data pemesan</x-slot:title>

                    <div class="space-y-4">
                        <x-input
                            label="Nama lengkap"
                            placeholder="Sesuai identitas"
                            wire:model.blur="buyerName"
                            :error-field="'buyerName'"
                            required
                        />

                        <x-input
                            label="Email"
                            type="email"
                            placeholder="nama@email.com"
                            icon="o-envelope"
                            wire:model.blur="buyerEmail"
                            :error-field="'buyerEmail'"
                            required
                        />

                        <x-input
                            label="Nomor telepon"
                            placeholder="08xxxxxxxxxx"
                            icon="o-phone"
                            wire:model.blur="buyerPhone"
                            :error-field="'buyerPhone'"
                            hint="Opsional, dipakai untuk pemberitahuan urgent"
                        />

                        <x-textarea
                            label="Catatan"
                            placeholder="Kebutuhan khusus, permintaan akses, dan sebagainya."
                            wire:model.blur="notes"
                            :error-field="'notes'"
                            rows="3"
                        />
                    </div>

                    @error('cart')
                        <x-alert :title="$message" icon="o-exclamation-triangle" class="alert-error mt-4" />
                    @enderror
                </x-card>
            </div>

            {{-- SUMMARY --}}
            <div class="mt-8 w-full shrink-0 lg:mt-0 lg:w-96">
                <x-card shadow class="border border-base-300 lg:sticky lg:top-24">
                    <x-slot:title class="text-base font-bold">Ringkasan pesanan</x-slot:title>

                    <div class="space-y-3 border-b border-base-300 pb-4">
                        <div class="flex gap-3">
                            <div class="size-14 shrink-0 overflow-hidden rounded-lg">
                                <x-event-artwork :accent="$this->event->accent" :starts-at="$this->event->starts_at" class="h-full" />
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm leading-snug font-semibold">{{ $this->event->title }}</p>
                                <p class="mt-0.5 text-xs text-base-content/60">
                                    {{ Format::dateLong($this->event->starts_at) }}
                                </p>
                                <p class="truncate text-xs text-base-content/60">{{ $this->event->venue?->name }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-2 py-4 text-sm">
                        @foreach ($this->lines as $line)
                            <div class="flex justify-between gap-3">
                                <span class="text-base-content/70">
                                    {{ $line['quantity'] }}&times; {{ $line['type']->name }}
                                </span>
                                <span class="shrink-0 font-medium">{{ Format::rupiah($line['subtotal']) }}</span>
                            </div>
                        @endforeach
                    </div>

                    <div class="space-y-1.5 border-t border-base-300 pt-4 text-sm">
                        <div class="flex justify-between">
                            <span class="text-base-content/60">Jumlah tiket</span>
                            <span class="font-medium">{{ $this->totalQuantity() }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-base-content/60">Biaya layanan</span>
                            <span class="font-medium">Rp0</span>
                        </div>
                    </div>

                    <div class="mt-3 flex items-baseline justify-between border-t border-base-300 pt-3">
                        <span class="font-bold">Total</span>
                        <span class="text-2xl font-black text-primary">{{ Format::rupiah($this->total()) }}</span>
                    </div>

                    <x-button
                        label="Buat pesanan"
                        icon="o-lock-closed"
                        class="btn-primary btn-block mt-5"
                        wire:click="submit"
                        wire:loading.attr="disabled"
                        wire:loading.class="btn-loading"
                        :disabled="$this->totalQuantity() === 0"
                    />

                    <p class="mt-3 text-center text-xs text-base-content/50">
                        Dengan menekan tombol di atas, stok tiket langsung diamankan untuk pesanan Anda.
                    </p>
                </x-card>
            </div>
        </div>
    @endif
</div>
