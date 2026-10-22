<?php

use App\Models\Venue;
use App\Support\Format;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Mary\Traits\Toast;

new class extends Component
{
    use Toast;

    #[Url(as: 'search', history: true)]
    public string $search = '';

    public bool $formModal = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $address = '';

    public string $city = '';

    public string $capacity = '';

    public function headers(): array
    {
        return [
            ['key' => 'name', 'label' => 'Nama lokasi', 'class' => 'min-w-56'],
            ['key' => 'city', 'label' => 'Kota', 'class' => 'w-40'],
            ['key' => 'capacity', 'label' => 'Kapasitas', 'class' => 'w-28'],
            ['key' => 'events_count', 'label' => 'Event', 'class' => 'w-24'],
        ];
    }

    public function venues()
    {
        return Venue::query()
            ->withCount('events')
            ->when($this->search !== '', function ($q) {
                $like = '%'.$this->search.'%';
                $q->where(function ($sub) use ($like) {
                    $sub->where('name', 'like', $like)
                        ->orWhere('city', 'like', $like)
                        ->orWhere('address', 'like', $like);
                });
            })
            ->orderBy('name')
            ->get();
    }

    public function with(): array
    {
        return [
            'venues' => $this->venues(),
            'headers' => $this->headers(),
        ];
    }

    public function create(): void
    {
        $this->resetForm();
        $this->formModal = true;
    }

    public function edit(int $id): void
    {
        $venue = Venue::findOrFail($id);

        $this->editingId = $venue->id;
        $this->name = $venue->name;
        $this->address = $venue->address;
        $this->city = $venue->city;
        $this->capacity = $venue->capacity ? (string) $venue->capacity : '';

        $this->formModal = true;
    }

    public function save(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:150'],
            'address' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:1000000'],
        ], [
            'name.required' => 'Nama lokasi wajib diisi.',
            'address.required' => 'Alamat wajib diisi.',
            'city.required' => 'Kota wajib diisi.',
            'capacity.integer' => 'Kapasitas harus angka bulat.',
        ], [
            'name' => 'nama lokasi',
            'address' => 'alamat',
            'city' => 'kota',
            'capacity' => 'kapasitas',
        ]);

        $payload = [
            'name' => $this->name,
            'address' => $this->address,
            'city' => $this->city,
            'capacity' => $this->capacity === '' ? null : (int) $this->capacity,
        ];

        if ($this->editingId) {
            Venue::findOrFail($this->editingId)->update($payload);
            $message = 'Lokasi diperbarui.';
        } else {
            Venue::create($payload);
            $message = 'Lokasi ditambahkan.';
        }

        $this->formModal = false;
        $this->resetForm();

        $this->success($message, position: 'toast-top');
    }

    public function delete(int $id): void
    {
        $venue = Venue::withCount('events')->findOrFail($id);

        if ($venue->events_count > 0) {
            $this->error('Lokasi yang sudah dipakai event tidak bisa dihapus.', position: 'toast-top');

            return;
        }

        $venue->delete();

        $this->success('Lokasi dihapus.', position: 'toast-top');
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'address', 'city', 'capacity']);
        $this->clearValidation();
    }

    public function render(): View
    {
        return view('pages.admin.venues.index')
            ->layout('layouts.admin')
            ->title('Lokasi');
    }
};

?>

<div>
    <x-header title="Lokasi" subtitle="Daftar venue yang dipakai event" separator>
        <x-slot:middle class="!justify-end">
            <x-input placeholder="Cari lokasi..." wire:model.live.debounce.400ms="search" clearable icon="o-magnifying-glass" />
        </x-slot:middle>
        <x-slot:actions>
            <x-button label="Lokasi baru" icon="o-plus" class="btn-primary" wire:click="create" />
        </x-slot:actions>
    </x-header>

    <x-card shadow>
        <x-table :headers="$headers" :rows="$venues" no-hover>
            @scope('cell_name', $venue)
                <div class="flex items-center gap-3">
                    <div class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary">
                        <x-icon name="o-map-pin" class="size-4" />
                    </div>
                    <div class="min-w-0">
                        <p class="truncate font-semibold">{{ $venue->name }}</p>
                        <p class="truncate text-xs text-base-content/50">{{ $venue->address }}</p>
                    </div>
                </div>
            @endscope

            @scope('cell_capacity', $venue)
                {{ $venue->capacity ? Format::number($venue->capacity).' orang' : '-' }}
            @endscope

            @scope('cell_events_count', $venue)
                <span class="badge badge-sm badge-ghost">{{ $venue->events_count }}</span>
            @endscope

            @scope('actions', $venue)
                <div class="flex justify-end gap-1">
                    <x-button icon="o-pencil-square" wire:click="edit({{ $venue->id }})" class="btn-ghost btn-sm" />
                    <x-button icon="o-trash" wire:click="delete({{ $venue->id }})"
                              wire:confirm="Hapus lokasi &quot;{{ $venue->name }}&quot;?"
                              class="btn-ghost btn-sm text-error" />
                </div>
            @endscope
        </x-table>
    </x-card>

    <x-modal wire:model="formModal" :title="$editingId ? 'Ubah lokasi' : 'Lokasi baru'" box-class="w-11/12 lg:w-1/2">
        <form wire:submit="save" class="space-y-4">
            <x-input label="Nama lokasi" wire:model.blur="name" placeholder="Contoh: Istora Senayan" :error-field="'name'" required />
            <x-textarea label="Alamat" wire:model.blur="address" rows="2" placeholder="Jalan, nomor, lantai" :error-field="'address'" required />

            <div class="grid gap-4 sm:grid-cols-2">
                <x-input label="Kota" wire:model.blur="city" placeholder="Jakarta Pusat" :error-field="'city'" required />
                <x-input label="Kapasitas" wire:model.blur="capacity" placeholder="7000" :error-field="'capacity'"
                         hint="Opsional, jumlah orang" />
            </div>

            <x-slot:actions>
                <x-button label="Batal" wire:click="$set('formModal', false)" />
                <x-button type="submit" label="Simpan" class="btn-primary" wire:loading.attr="disabled" wire:loading.class="btn-loading" />
            </x-slot:actions>
        </form>
    </x-modal>
</div>
