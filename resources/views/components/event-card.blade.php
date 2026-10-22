@props([
    'event',
])

@php

    $icon = match ($event->category) {
        'Konser Musik' => 'o-musical-note',
        'Tech & Startup' => 'o-cpu-chip',
        'Bisnis' => 'o-briefcase',
        'Seni & Budaya' => 'o-paint-brush',
        'Olahraga' => 'o-trophy',
        'Komunitas' => 'o-users',
        default => 'o-calendar-days',
    };

    $soldOut = $event->isSoldOut();
@endphp

<article class="group card border border-base-300 bg-base-100 shadow-sm transition duration-200 hover:-translate-y-1 hover:shadow-xl">
    <a href="{{ route('events.show', $event) }}" wire:navigate class="block">
        <div class="h-40">
            <x-event-artwork
                :accent="$event->accent"
                :category="$event->category"
                :starts-at="$event->starts_at"
                :icon="$icon"
                class="h-full"
            />
        </div>

        <div class="card-body gap-3 p-5">
            <div class="flex items-start justify-between gap-3">
                <h3 class="card-title text-base leading-snug transition group-hover:text-primary">
                    {{ $event->title }}
                </h3>
            </div>

            <div class="space-y-1.5 text-sm text-base-content/60">
                <p class="flex items-center gap-2">
                    <x-icon name="o-clock" class="size-4 shrink-0" />
                    <span>{{ \App\Support\Format::dayName($event->starts_at) }}, {{ $event->starts_at->format('d M Y') }}</span>
                </p>
                <p class="flex items-center gap-2">
                    <x-icon name="o-map-pin" class="size-4 shrink-0" />
                    <span class="truncate">{{ $event->venue?->name ?? 'Lokasi menyusul' }}</span>
                </p>
            </div>

            @if (! $soldOut)
                <div>
                    <div class="mb-1.5 flex items-center justify-between text-xs">
                        <span class="font-medium text-base-content/50">
                            {{ \App\Support\Format::number($event->quota_sold) }} / {{ \App\Support\Format::number($event->quota_total) }} tiket
                        </span>
                        <span class="font-semibold text-base-content/70">{{ $event->sold_percent }}%</span>
                    </div>
                    <progress
                        class="progress progress-primary h-1.5 w-full"
                        value="{{ $event->sold_percent }}"
                        max="100"
                    ></progress>
                </div>
            @endif

            <div class="card-actions items-center justify-between border-t border-base-300 pt-3">
                <div>
                    <p class="text-[0.65rem] uppercase tracking-wider text-base-content/50">Mulai dari</p>
                    <p class="text-base font-bold">{{ \App\Support\Format::rupiah($event->min_price) }}</p>
                </div>

                @if ($soldOut)
                    <span class="badge badge-error badge-outline">Habis</span>
                @else
                    <span class="btn btn-primary btn-sm gap-1.5">
                        Beli tiket
                        <x-icon name="o-arrow-right" class="size-4" />
                    </span>
                @endif
            </div>
        </div>
    </a>
</article>
