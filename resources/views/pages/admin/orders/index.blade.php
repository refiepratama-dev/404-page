<?php

use App\Models\Event;
use App\Models\Order;
use App\Support\Format;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

new class extends Component
{
    use WithPagination, Toast;

    #[Url(as: 'search', history: true)]
    public string $search = '';

    #[Url(as: 'status', history: true)]
    public string $statusFilter = '';

    public bool $detailModal = false;

    public ?int $detailId = null;

    public function updated(string $property): void
    {
        if ($property === 'search' || $property === 'statusFilter') {
            $this->resetPage();
        }
    }

    public function headers(): array
    {
        return [
            ['key' => 'order_number', 'label' => 'Nomor', 'class' => 'w-44'],
            ['key' => 'event', 'label' => 'Event', 'class' => 'min-w-48'],
            ['key' => 'buyer', 'label' => 'Pemesan', 'class' => 'min-w-48'],
            ['key' => 'total', 'label' => 'Total', 'class' => 'w-32'],
            ['key' => 'status', 'label' => 'Status', 'class' => 'w-32'],
        ];
    }

    public function orders()
    {
        return Order::query()
            ->with('event')
            ->when($this->search !== '', function ($q) {
                $like = '%'.$this->search.'%';
                $q->where(function ($sub) use ($like) {
                    $sub->where('order_number', 'like', $like)
                        ->orWhere('buyer_name', 'like', $like)
                        ->orWhere('buyer_email', 'like', $like);
                });
            })
            ->when($this->statusFilter !== '', fn ($q) => $q->where('status', $this->statusFilter))
            ->latest()
            ->paginate(15);
    }

    public function with(): array
    {
        return [
            'orders' => $this->orders(),
            'headers' => $this->headers(),
            'summary' => [
                'pending' => Order::where('status', Order::STATUS_PENDING)->count(),
                'paid' => Order::where('status', Order::STATUS_PAID)->count(),
                'cancelled' => Order::where('status', Order::STATUS_CANCELLED)->count(),
                'revenue' => (int) Order::where('status', Order::STATUS_PAID)->sum('total'),
            ],
        ];
    }

    public function showDetail(int $id): void
    {
        $this->detailId = $id;
        $this->detailModal = true;
    }

    public function markPaid(int $id): void
    {
        $order = Order::findOrFail($id);

        if ($order->isPaid() || $order->isCancelled()) {
            return;
        }

        $order->update([
            'status' => Order::STATUS_PAID,
            'paid_at' => now(),
        ]);

        $this->success('Pesanan ditandai lunas.', position: 'toast-top');
    }

    /**
     * Cancelling an order returns its ticket stock to the available quota.
     */
    public function cancel(int $id): void
    {
        $order = Order::with('items')->findOrFail($id);

        if ($order->isCancelled()) {
            return;
        }

        DB::transaction(function () use ($order) {
            foreach ($order->items as $item) {
                $item->ticketType?->release($item->quantity);
            }

            $order->update(['status' => Order::STATUS_CANCELLED]);
        });

        $this->success('Pesanan dibatalkan dan stok tiket dikembalikan.', position: 'toast-top');
    }

    public function render(): View
    {
        return view('pages.admin.orders.index')
            ->layout('layouts.admin')
            ->title('Pesanan');
    }
};

?>

<div>
    <x-header title="Pesanan" subtitle="Semua transaksi tiket masuk" separator>
        <x-slot:middle class="!justify-end">
            <x-input placeholder="Cari nomor, nama, atau email..." wire:model.live.debounce.400ms="search"
                     clearable icon="o-magnifying-glass" />
        </x-slot:middle>
    </x-header>

    <div class="mb-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat title="Menunggu pembayaran" :value="Format::number($summary['pending'])" icon="o-clock"
                color="{{ $summary['pending'] > 0 ? 'text-warning' : 'text-base-content/40' }}" />
        <x-stat title="Lunas" :value="Format::number($summary['paid'])" icon="o-check-badge" color="text-success" />
        <x-stat title="Dibatalkan" :value="Format::number($summary['cancelled'])" icon="o-x-circle" color="text-error" />
        <x-stat title="Total pendapatan" :value="Format::rupiah($summary['revenue'])" icon="o-banknotes" color="text-primary" />
    </div>

    <x-card shadow>
        <div class="mb-4 flex flex-wrap items-center gap-2">
            @foreach (['' => 'Semua', Order::STATUS_PENDING => 'Menunggu', Order::STATUS_PAID => 'Lunas', Order::STATUS_CANCELLED => 'Dibatalkan'] as $value => $label)
                <x-button
                    :label="$label"
                    wire:click="$set('statusFilter', @js($value))"
                    class="btn btn-xs {{ $statusFilter === $value ? 'btn-primary' : 'btn-ghost border-base-300' }}"
                />
            @endforeach
        </div>

        <x-table :headers="$headers" :rows="$orders" with-pagination>
            @scope('cell_event', $order)
                <div class="min-w-0">
                    <a href="{{ $order->event ? route('admin.events.show', $order->event) : '#' }}" wire:navigate
                       class="block truncate font-semibold hover:text-primary">
                        {{ $order->event?->title ?? 'Event dihapus' }}
                    </a>
                </div>
            @endscope

            @scope('cell_buyer', $order)
                <div class="min-w-0">
                    <p class="truncate font-medium">{{ $order->buyer_name }}</p>
                    <p class="truncate text-xs text-base-content/50">{{ $order->buyer_email }}</p>
                </div>
            @endscope

            @scope('cell_total', $order)
                <span class="font-semibold">{{ Format::rupiah($order->total) }}</span>
            @endscope

            @scope('cell_status', $order)
                @php
                    $badge = match ($order->status) {
                        Order::STATUS_PAID => 'badge-success',
                        Order::STATUS_CANCELLED => 'badge-error',
                        default => 'badge-warning',
                    };
                @endphp
                <span class="badge badge-sm {{ $badge }} badge-outline">{{ $order->status_label }}</span>
            @endscope

            @scope('actions', $order)
                <div class="flex justify-end gap-1">
                    <x-button icon="o-eye" tooltip="Detail" wire:click="showDetail({{ $order->id }})" class="btn-ghost btn-sm" />
                    @unless ($order->isPaid() || $order->isCancelled())
                        <x-button icon="o-check-badge" tooltip="Tandai lunas" wire:click="markPaid({{ $order->id }})"
                                  class="btn-ghost btn-sm text-success" />
                        <x-button icon="o-x-circle" tooltip="Batalkan" wire:click="cancel({{ $order->id }})"
                                  wire:confirm="Batalkan pesanan {{ $order->order_number }}? Stok tiket akan dikembalikan."
                                  class="btn-ghost btn-sm text-error" />
                    @endunless
                </div>
            @endscope
        </x-table>
    </x-card>

    {{-- DETAIL MODAL --}}
    <x-modal wire:model="detailModal" title="Detail pesanan" box-class="w-11/12 lg:w-2/3">
        @if ($detailId && ($order = Order::with(['items.ticketType', 'event'])->find($detailId)))
            <div class="space-y-5">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <p class="text-xs uppercase tracking-wider text-base-content/50">Nomor pesanan</p>
                        <p class="font-mono text-sm font-bold">{{ $order->order_number }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wider text-base-content/50">Dibuat</p>
                        <p class="text-sm">{{ Format::dateLong($order->created_at) }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wider text-base-content/50">Pemesan</p>
                        <p class="text-sm font-semibold">{{ $order->buyer_name }}</p>
                        <p class="text-xs text-base-content/50">{{ $order->buyer_email }}</p>
                        @if ($order->buyer_phone)
                            <p class="text-xs text-base-content/50">{{ $order->buyer_phone }}</p>
                        @endif
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wider text-base-content/50">Event</p>
                        <p class="text-sm font-semibold">{{ $order->event?->title ?? '-' }}</p>
                        <p class="text-xs text-base-content/50">{{ Format::dateLong($order->event?->starts_at) }}</p>
                    </div>
                </div>

                @if ($order->notes)
                    <div class="rounded-lg bg-base-200 p-3 text-sm">
                        <p class="text-xs uppercase tracking-wider text-base-content/50">Catatan pemesan</p>
                        <p class="mt-1">{{ $order->notes }}</p>
                    </div>
                @endif

                <div class="overflow-x-auto">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Tiket</th>
                                <th class="text-right">Harga</th>
                                <th class="text-right">Jumlah</th>
                                <th class="text-right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($order->items as $item)
                                <tr>
                                    <td>{{ $item->ticketType?->name ?? 'Tipe tiket dihapus' }}</td>
                                    <td class="text-right">{{ Format::rupiah($item->unit_price) }}</td>
                                    <td class="text-right">{{ $item->quantity }}</td>
                                    <td class="text-right font-semibold">{{ Format::rupiah($item->subtotal) }}</td>
                                </tr>
                            @endforeach
                            <tr class="font-black">
                                <td colspan="3">Total</td>
                                <td class="text-right text-primary">{{ Format::rupiah($order->total) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <x-slot:actions>
                    <x-button label="Tutup" wire:click="$set('detailModal', false)" />
                    @unless ($order->isPaid() || $order->isCancelled())
                        <x-button label="Tandai lunas" icon="o-check-badge" wire:click="markPaid({{ $order->id }})" class="btn-success" />
                        <x-button label="Batalkan pesanan" icon="o-x-circle" wire:click="cancel({{ $order->id }})"
                                  wire:confirm="Batalkan pesanan ini? Stok tiket akan dikembalikan."
                                  class="btn-error btn-outline" />
                    @endunless
                </x-slot:actions>
            </div>
        @endif
    </x-modal>
</div>
