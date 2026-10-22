<?php

use App\Models\Event;
use App\Models\Venue;
use App\Support\Format;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
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

    public bool $formModal = false;

    public ?int $editingId = null;

    public string $title = '';

    public string $category = '';

    public ?int $venueId = null;

    public string $description = '';

    public string $accent = 'violet';

    public string $startsAt = '';

    public string $endsAt = '';

    public string $status = Event::STATUS_DRAFT;

    public function updated(string $property): void
    {
        if ($property === 'search' || $property === 'statusFilter') {
            $this->resetPage();
        }
    }

    public function headers(): array
    {
        return [
            ['key' => 'title', 'label' => 'Event', 'class' => 'min-w-64'],
            ['key' => 'category', 'label' => 'Kategori', 'class' => 'w-40'],
            ['key' => 'starts_at', 'label' => 'Jadwal', 'class' => 'w-44'],
            ['key' => 'sold', 'label' => 'Terjual', 'class' => 'w-32'],
            ['key' => 'status', 'label' => 'Status', 'class' => 'w-28'],
        ];
    }

    public function events()
    {
        return Event::query()
            ->with('venue')
            ->withSum('ticketTypes as sold_sum', 'sold')
            ->withSum('ticketTypes as quota_sum', 'quota')
            ->search($this->search)
            ->when($this->statusFilter !== '', fn ($q) => $q->where('status', $this->statusFilter))
            ->orderByDesc('starts_at')
            ->paginate(10);
    }

    public function with(): array
    {
        return [
            'events' => $this->events(),
            'headers' => $this->headers(),
            'venues' => Venue::orderBy('name')->get(),
            'categories' => collect(Event::CATEGORIES)
                ->map(fn (string $category) => ['name' => $category, 'id' => $category])
                ->all(),
            'accents' => collect(Event::ACCENTS)
                ->map(fn (string $accent) => ['name' => ucfirst($accent), 'id' => $accent])
                ->all(),
            'statuses' => [
                ['name' => 'Terbit', 'id' => Event::STATUS_PUBLISHED],
                ['name' => 'Draf', 'id' => Event::STATUS_DRAFT],
                ['name' => 'Selesai', 'id' => Event::STATUS_FINISHED],
                ['name' => 'Dibatalkan', 'id' => Event::STATUS_CANCELLED],
            ],
        ];
    }

    public function create(): void
    {
        $this->resetForm();
        $this->formModal = true;
    }

    public function edit(int $id): void
    {
        $event = Event::findOrFail($id);

        $this->editingId = $event->id;
        $this->title = $event->title;
        $this->category = $event->category;
        $this->venueId = $event->venue_id;
        $this->description = (string) $event->description;
        $this->accent = $event->accent;
        $this->startsAt = $event->starts_at->format('Y-m-d\TH:i');
        $this->endsAt = $event->ends_at->format('Y-m-d\TH:i');
        $this->status = $event->status;

        $this->formModal = true;
    }

    public function save(): void
    {
        $this->validate([
            'title' => ['required', 'string', 'min:5', 'max:180'],
            'category' => ['required', Rule::in(Event::CATEGORIES)],
            'venueId' => ['nullable', 'exists:venues,id'],
            'description' => ['nullable', 'string', 'max:5000'],
            'accent' => ['required', Rule::in(Event::ACCENTS)],
            'startsAt' => ['required', 'date'],
            'endsAt' => ['required', 'date', 'after_or_equal:startsAt'],
            'status' => ['required', Rule::in([
                Event::STATUS_DRAFT, Event::STATUS_PUBLISHED,
                Event::STATUS_FINISHED, Event::STATUS_CANCELLED,
            ])],
        ], [
            'title.required' => 'Judul event wajib diisi.',
            'title.min' => 'Judul minimal 5 karakter.',
            'category.required' => 'Pilih satu kategori.',
            'endsAt.after_or_equal' => 'Waktu selesai tidak boleh lebih awal dari waktu mulai.',
        ], [
            'title' => 'judul',
            'category' => 'kategori',
            'venueId' => 'lokasi',
            'startsAt' => 'waktu mulai',
            'endsAt' => 'waktu selesai',
            'status' => 'status',
        ]);

        $payload = [
            'title' => $this->title,
            'category' => $this->category,
            'venue_id' => $this->venueId ?: null,
            'description' => $this->description ?: null,
            'accent' => $this->accent,
            'starts_at' => $this->startsAt,
            'ends_at' => $this->endsAt,
            'status' => $this->status,
        ];

        if ($this->editingId) {
            $event = Event::findOrFail($this->editingId);
            $event->update($payload + ['slug' => Event::uniqueSlug($this->title, $event->id)]);
            $message = 'Event diperbarui.';
        } else {
            $event = Event::create($payload);
            $message = 'Event dibuat. Tambahkan tipe tiket agar bisa dijual.';
        }

        $this->formModal = false;
        $this->resetForm();

        $this->success($message, position: 'toast-top');
        $this->redirectRoute('admin.events.show', $event, navigate: true);
    }

    public function toggleStatus(int $id): void
    {
        $event = Event::findOrFail($id);

        $event->update([
            'status' => $event->isPublished() ? Event::STATUS_DRAFT : Event::STATUS_PUBLISHED,
        ]);

        $this->success(
            $event->isPublished() ? 'Event diterbitkan.' : 'Event dikembalikan ke draf.',
            position: 'toast-top'
        );
    }

    public function delete(int $id): void
    {
        $event = Event::withCount('orders')->findOrFail($id);

        if ($event->orders_count > 0) {
            $this->error('Event yang sudah punya pesanan tidak bisa dihapus. Ubah statusnya menjadi Dibatalkan.', position: 'toast-top');

            return;
        }

        $event->delete();

        $this->success('Event dihapus.', position: 'toast-top');
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'title', 'description', 'startsAt', 'endsAt']);
        $this->category = Event::CATEGORIES[0];
        $this->accent = Event::ACCENTS[0];
        $this->status = Event::STATUS_DRAFT;
        $this->venueId = null;
    }

    public function render(): View
    {
        return view('pages.admin.events.index')
            ->layout('layouts.admin')
            ->title('Kelola Event');
    }
};

?>

<div>
    <x-header title="Kelola Event" subtitle="Buat, ubah, dan publikasikan event" separator>
        <x-slot:middle class="!justify-end">
            <x-input placeholder="Cari event..." wire:model.live.debounce.400ms="search" clearable icon="o-magnifying-glass" />
        </x-slot:middle>
        <x-slot:actions>
            <x-button label="Event baru" icon="o-plus" class="btn-primary" wire:click="create" />
        </x-slot:actions>
    </x-header>

    <x-card shadow>
        <div class="mb-4 flex flex-wrap items-center gap-2">
            @foreach (['' => 'Semua', Event::STATUS_PUBLISHED => 'Terbit', Event::STATUS_DRAFT => 'Draf', Event::STATUS_FINISHED => 'Selesai', Event::STATUS_CANCELLED => 'Dibatalkan'] as $value => $label)
                <x-button
                    :label="$label"
                    wire:click="$set('statusFilter', @js($value))"
                    class="btn btn-xs {{ $statusFilter === $value ? 'btn-primary' : 'btn-ghost border-base-300' }}"
                />
            @endforeach
        </div>

        <x-table :headers="$headers" :rows="$events" with-pagination>
            @scope('cell_title', $event)
                <div class="flex items-center gap-3">
                    <div class="size-9 shrink-0 overflow-hidden rounded-lg">
                        <x-event-artwork :accent="$event->accent" :starts-at="$event->starts_at" class="h-full" />
                    </div>
                    <div class="min-w-0">
                        <a href="{{ route('admin.events.show', $event) }}" wire:navigate
                           class="block truncate font-semibold hover:text-primary">{{ $event->title }}</a>
                        <span class="truncate text-xs text-base-content/50">{{ $event->venue?->name ?? 'Tanpa lokasi' }}</span>
                    </div>
                </div>
            @endscope

            @scope('cell_starts_at', $event)
                <div class="text-sm">
                    <p class="font-medium">{{ Format::date($event->starts_at) }}</p>
                    <p class="text-xs text-base-content/50">{{ $event->starts_at->format('H:i') }} - {{ $event->ends_at->format('H:i') }}</p>
                </div>
            @endscope

            @scope('cell_sold', $event)
                @php
                    $percent = $event->quota_sum > 0 ? round($event->sold_sum / $event->quota_sum * 100) : 0;
                @endphp
                <div class="w-28">
                    <p class="text-xs font-semibold">{{ Format::number($event->sold_sum) }} / {{ Format::number($event->quota_sum) }}</p>
                    <progress class="progress progress-primary h-1.5 w-full" value="{{ $percent }}" max="100"></progress>
                </div>
            @endscope

            @scope('cell_status', $event)
                @php
                    $badge = match ($event->status) {
                        Event::STATUS_PUBLISHED => 'badge-success',
                        Event::STATUS_FINISHED => 'badge-info',
                        Event::STATUS_CANCELLED => 'badge-error',
                        default => 'badge-warning',
                    };
                @endphp
                <span class="badge badge-sm {{ $badge }} badge-outline">{{ $event->status_label }}</span>
            @endscope

            @scope('actions', $event)
                <div class="flex justify-end gap-1">
                    <x-button icon="o-arrow-top-right-on-square" tooltip="Detail"
                              link="{{ route('admin.events.show', $event) }}" wire:navigate class="btn-ghost btn-sm" />
                    <x-button icon="o-pencil-square" tooltip="Ubah" wire:click="edit({{ $event->id }})" class="btn-ghost btn-sm" />
                    <x-button icon="o-eye" tooltip="{{ $event->isPublished() ? 'Tarik jadi draf' : 'Terbitkan' }}"
                              wire:click="toggleStatus({{ $event->id }})" class="btn-ghost btn-sm" />
                    <x-button icon="o-trash" tooltip="Hapus" wire:click="delete({{ $event->id }})"
                              wire:confirm="Hapus event &quot;{{ $event->title }}&quot;? Tindakan ini tidak bisa dibatalkan."
                              class="btn-ghost btn-sm text-error" />
                </div>
            @endscope
        </x-table>
    </x-card>

    {{-- FORM MODAL --}}
    <x-modal wire:model="formModal" title="{{ $editingId ? 'Ubah event' : 'Event baru' }}"
             subtitle="{{ $editingId ? 'Perbarui detail event yang sudah ada.' : 'Isi detail dasar event, lalu tambahkan tipe tiket.' }}"
             box-class="w-11/12 lg:w-2/3">
        <form wire:submit="save" class="space-y-4">
            <x-input label="Judul event" wire:model.blur="title" placeholder="Contoh: Pesta Vibrasi 2026"
                     :error-field="'title'" required />

            <div class="grid gap-4 sm:grid-cols-2">
                <x-select label="Kategori" wire:model.live="category" :options="$categories" required
                          :error-field="'category'" />

                <x-select label="Lokasi" wire:model.live="venueId" :options="$venues" placeholder="Pilih lokasi"
                          :error-field="'venueId'" />
            </div>

            <x-textarea label="Deskripsi" wire:model.blur="description" rows="4"
                        placeholder="Ceritakan detail event, benefit, dan apa yang didapat peserta."
                        :error-field="'description'" />

            <div class="grid gap-4 sm:grid-cols-2">
                <x-input label="Mulai" type="datetime-local" wire:model.live="startsAt" :error-field="'startsAt'" required />
                <x-input label="Selesai" type="datetime-local" wire:model.live="endsAt" :error-field="'endsAt'" required />
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <x-select label="Warna aksen" wire:model.live="accent" :options="$accents" :error-field="'accent'" />
                <x-select label="Status" wire:model.live="status" :options="$statuses" :error-field="'status'" />
            </div>

            <x-slot:actions>
                <x-button label="Batal" wire:click="$set('formModal', false)" />
                <x-button type="submit" label="{{ $editingId ? 'Simpan perubahan' : 'Buat event' }}" class="btn-primary"
                          wire:loading.attr="disabled" wire:loading.class="btn-loading" />
            </x-slot:actions>
        </form>
    </x-modal>
</div>
