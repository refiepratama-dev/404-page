@props([
    'accent' => 'violet',
    'category' => '',
    'startsAt' => null,
    'icon' => 'o-calendar-days',
])

@php
    /**
     * Tailwind only generates classes it can see as complete literals, so the
     * accent key is mapped to full class strings here instead of being
     * interpolated at runtime.
     */
    $gradient = match ($accent) {
        'sky' => 'from-sky-500 via-sky-600 to-sky-800',
        'emerald' => 'from-emerald-500 via-emerald-600 to-emerald-800',
        'amber' => 'from-amber-400 via-amber-500 to-amber-700',
        'rose' => 'from-rose-500 via-rose-600 to-rose-800',
        'cyan' => 'from-cyan-400 via-cyan-600 to-cyan-800',
        default => 'from-violet-500 via-violet-600 to-violet-800',
    };

    $ring = match ($accent) {
        'sky' => 'ring-sky-500/30',
        'emerald' => 'ring-emerald-500/30',
        'amber' => 'ring-amber-500/30',
        'rose' => 'ring-rose-500/30',
        'cyan' => 'ring-cyan-500/30',
        default => 'ring-violet-500/30',
    };

    $text = match ($accent) {
        'sky' => 'text-sky-600 dark:text-sky-400',
        'emerald' => 'text-emerald-600 dark:text-emerald-400',
        'amber' => 'text-amber-600 dark:text-amber-400',
        'rose' => 'text-rose-600 dark:text-rose-400',
        'cyan' => 'text-cyan-600 dark:text-cyan-400',
        default => 'text-violet-600 dark:text-violet-400',
    };
@endphp

<div class="relative overflow-hidden bg-gradient-to-br {{ $gradient }}">
    <div class="absolute -right-8 -top-10 size-32 rounded-full bg-white/10 blur-2xl"></div>
    <div class="absolute -bottom-12 -left-6 size-28 rounded-full bg-black/10 blur-2xl"></div>

    <x-icon :name="$icon" class="absolute right-4 top-4 size-16 text-white/15" />

    <div class="relative flex h-full flex-col justify-between p-4">
        @if ($startsAt)
            <div class="inline-flex w-fit flex-col items-center rounded-lg bg-white/90 px-3 py-1.5 text-center shadow-sm ring-1 {{ $ring }}">
                <span class="text-lg leading-none font-black text-slate-900">
                    {{ \App\Support\Format::dayMonth($startsAt) }}
                </span>
                <span class="mt-0.5 text-[0.6rem] font-bold uppercase tracking-wider text-slate-500">
                    {{ \App\Support\Format::monthShort($startsAt) }}
                </span>
            </div>
        @endif

        @if ($category !== '')
            <span class="w-fit rounded-full bg-slate-900/70 px-2.5 py-1 text-[0.65rem] font-semibold tracking-wide text-white backdrop-blur-sm">
                {{ $category }}
            </span>
        @endif
    </div>
</div>
