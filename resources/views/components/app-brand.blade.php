@props([
    'name' => config('app.name'),
    'href' => '/',
])

<a href="{{ $href }}" {{ $attributes->merge(['class' => 'flex items-center gap-2.5']) }}>
    <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-primary text-primary-content">
        <x-icon name="o-calendar-days" class="size-5" />
    </span>
    <span class="flex flex-col leading-none">
        <span class="text-base font-black tracking-tight">{{ $name }}</span>
        <span class="mt-1 text-[0.65rem] font-medium uppercase tracking-widest text-base-content/50">
            Event Organizer
        </span>
    </span>
</a>
