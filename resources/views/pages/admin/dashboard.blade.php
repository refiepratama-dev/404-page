<?php

use App\Models\Event;
use App\Models\Order;
use App\Models\TicketType;
use App\Support\Format;
use Illuminate\Contracts\View\View;
use Livewire\Component;

new class extends Component
{
    public function with(): array
    {
        $paidOrders = Order::where('status', Order::STATUS_PAID);

        $revenue = (clone $paidOrders)->sum('total');
        $revenueThisMonth = (clone $paidOrders)
            ->whereBetween('paid_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->sum('total');

        $previousMonthStart = now()->subMonthNoOverflow()->startOfMonth();

        $revenuePrevious = Order::where('status', Order::STATUS_PAID)
            ->whereBetween('paid_at', [$previousMonthStart, $previousMonthStart->copy()->endOfMonth()])
            ->sum('total');

        $growth = $revenuePrevious > 0
            ? round(($revenueThisMonth - $revenuePrevious) / $revenuePrevious * 100)
            : null;

        return [
            'stats' => [
                'events' => Event::count(),
                'published' => Event::published()->count(),
                'draft' => Event::where('status', Event::STATUS_DRAFT)->count(),
                'orders' => Order::count(),
                'pending' => Order::where('status', Order::STATUS_PENDING)->count(),
                'cancelled' => Order::where('status', Order::STATUS_CANCELLED)->count(),
                'ticketsSold' => (int) TicketType::sum('sold'),
                'ticketsQuota' => (int) TicketType::sum('quota'),
                'revenue' => $revenue,
                'revenueThisMonth' => $revenueThisMonth,
                'growth' => $growth,
            ],
            'recentOrders' => Order::with('event')
                ->latest()
                ->limit(8)
                ->get(),
            'upcomingEvents' => Event::with('venue')
                ->published()
                ->upcoming()
                ->orderBy('starts_at')
                ->limit(5)
                ->get(),
            'topEvents' => Event::with('venue')
                ->published()
                ->withSum('ticketTypes as sold_sum', 'sold')
                ->withSum('ticketTypes as quota_sum', 'quota')
                ->orderByDesc('sold_sum')
                ->limit(5)
                ->get(),
        ];
    }

    public function render(): View
    {
        return view('pages.admin.dashboard')
            ->layout('layouts.admin')
            ->title('Dashboard');
    }
};

?>

<div class="space-y-6">
    <x-header title="Dashboard" subtitle="Ringkasan penjualan dan aktivitas event" separator />

    {{-- STATS --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat
            title="Pendapatan lunas"
            :value="Format::rupiah($stats['revenue'])"
            icon="o-banknotes"
            color="text-success"
            :description="'Bulan ini: '.Format::rupiah($stats['revenueThisMonth'])"
        />

        <x-stat
            title="Tiket terjual"
            :value="Format::number($stats['ticketsSold']).' / '.Format::number($stats['ticketsQuota'])"
            icon="o-ticket"
            color="text-primary"
            :description="round($stats['ticketsQuota'] > 0 ? $stats['ticketsSold'] / $stats['ticketsQuota'] * 100 : 0).'% dari total kuota'"
        />

        <x-stat
            title="Event terbit"
            :value="Format::number($stats['published'])"
            icon="o-calendar-days"
            color="text-info"
            :description="$stats['draft'].' masih draf'"
        />

        <x-stat
            title="Pesanan menunggu"
            :value="Format::number($stats['pending'])"
            icon="o-clock"
            color="{{ $stats['pending'] > 0 ? 'text-warning' : 'text-base-content/40' }}"
            :description="$stats['cancelled'].' dibatalkan'"
        />
    </div>

    <div class="grid gap-6 lg:grid-cols-5">
        {{-- RECENT ORDERS --}}
        <x-card shadow class="lg:col-span-3">
            <x-slot:title class="text-base font-bold">Pesanan terbaru</x-slot:title>
            <x-slot:subtitle class="text-xs">8 pesanan terakhir</x-slot:subtitle>
            <x-slot:actions>
                <x-button label="Semua" link="{{ route('admin.orders.index') }}" wire:navigate class="btn-ghost btn-xs" />
            </x-slot:actions>

            <div class="overflow-x-auto">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Nomor</th>
                            <th>Event</th>
                            <th>Pemesan</th>
                            <th class="text-right">Total</th>
                            <th class="text-right">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentOrders as $order)
                            <tr>
                                <td class="font-mono text-xs">{{ $order->order_number }}</td>
                                <td class="max-w-40 truncate text-xs">{{ $order->event?->title }}</td>
                                <td class="text-xs">{{ $order->buyer_name }}</td>
                                <td class="text-right text-xs font-semibold">{{ Format::rupiahShort($order->total) }}</td>
                                <td class="text-right">
                                    @php
                                        $badge = match ($order->status) {
                                            Order::STATUS_PAID => 'badge-success',
                                            Order::STATUS_CANCELLED => 'badge-error',
                                            default => 'badge-warning',
                                        };
                                    @endphp
                                    <span class="badge badge-sm {{ $badge }} badge-outline">{{ $order->status_label }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="py-8 text-center text-sm text-base-content/50">Belum ada pesanan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>

        {{-- UPCOMING --}}
        <x-card shadow class="lg:col-span-2">
            <x-slot:title class="text-base font-bold">Event terdekat</x-slot:title>

            <div class="space-y-3">
                @forelse ($upcomingEvents as $event)
                    <a href="{{ route('admin.events.show', $event) }}" wire:navigate
                       class="flex items-center gap-3 rounded-lg p-2 transition hover:bg-base-200">
                        <div class="flex size-11 shrink-0 flex-col items-center justify-center rounded-lg bg-primary/10 text-primary">
                            <span class="text-sm leading-none font-black">{{ Format::dayMonth($event->starts_at) }}</span>
                            <span class="text-[0.6rem] font-bold uppercase">{{ Format::monthShort($event->starts_at) }}</span>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold">{{ $event->title }}</p>
                            <p class="truncate text-xs text-base-content/50">
                                {{ $event->venue?->name }} &middot; {{ $event->sold_percent }}% terjual
                            </p>
                        </div>
                    </a>
                @empty
                    <p class="py-6 text-center text-sm text-base-content/50">Belum ada event terjadwal.</p>
                @endforelse
            </div>
        </x-card>
    </div>

    {{-- TOP EVENTS --}}
    <x-card shadow>
        <x-slot:title class="text-base font-bold">Event terlaris</x-slot:title>
        <x-slot:subtitle class="text-xs">Berdasarkan jumlah tiket terjual</x-slot:subtitle>

        <div class="space-y-4">
            @forelse ($topEvents as $event)
                @php
                    $percent = $event->quota_sum > 0 ? round($event->sold_sum / $event->quota_sum * 100) : 0;
                @endphp
                <div>
                    <div class="mb-1.5 flex items-center justify-between gap-4 text-sm">
                        <a href="{{ route('admin.events.show', $event) }}" wire:navigate
                           class="truncate font-medium hover:text-primary">{{ $event->title }}</a>
                        <span class="shrink-0 text-xs text-base-content/60">
                            {{ Format::number($event->sold_sum) }} / {{ Format::number($event->quota_sum) }} tiket
                        </span>
                    </div>
                    <progress class="progress progress-primary h-2 w-full" value="{{ $percent }}" max="100"></progress>
                </div>
            @empty
                <p class="py-6 text-center text-sm text-base-content/50">Belum ada penjualan.</p>
            @endforelse
        </div>
    </x-card>
</div>
