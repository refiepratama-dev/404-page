<?php

use App\Models\Event;
use App\Models\EventSession;
use App\Models\TicketType;
use App\Support\Format;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Mary\Traits\Toast;

new class extends Component
{
    use Toast;

    public Event $event;

    public bool $ticketModal = false;

    public ?int $editingTicketId = null;

    public string $ticketName = '';

    public string $ticketDescription = '';

    public string $ticketPrice = '';

    public string $ticketQuota = '';

    public string $ticketSalesStart = '';

    public string $ticketSalesEnd = '';

    public bool $sessionModal = false;

    public ?int $editingSessionId = null;

    public string $sessionTitle = '';

    public string $sessionSpeaker = '';

    public string $sessionTrack = '';

    public string $sessionStartsAt = '';

    public string $sessionEndsAt = '';

    public function mount(Event $event): void
    {
        $this->event = $event->load(['venue', 'ticketTypes', 'sessions']);
    }

    public function refreshEvent(): void
    {
        $this->event->refresh()->load(['venue', 'ticketTypes', 'sessions']);
    }

    public function toggleStatus(): void
    {
        $this->event->update([
            'status' => $this->event->isPublished() ? Event::STATUS_DRAFT : Event::STATUS_PUBLISHED,
        ]);

        $this->refreshEvent();

        $this->success(
            $this->event->isPublished() ? 'Event diterbitkan.' : 'Event dikembalikan ke draf.',
            position: 'toast-top'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Ticket types
    |--------------------------------------------------------------------------
    */

    public function createTicket(): void
    {
        $this->resetTicketForm();
        $this->ticketModal = true;
    }

    public function editTicket(int $id): void
    {
        $type = TicketType::where('event_id', $this->event->id)->findOrFail($id);

        $this->editingTicketId = $type->id;
        $this->ticketName = $type->name;
        $this->ticketDescription = (string) $type->description;
        $this->ticketPrice = (string) $type->price;
        $this->ticketQuota = (string) $type->quota;
        $this->ticketSalesStart = $type->sales_start?->format('Y-m-d\TH:i') ?? '';
        $this->ticketSalesEnd = $type->sales_end?->format('Y-m-d\TH:i') ?? '';

        $this->ticketModal = true;
    }

    public function saveTicket(): void
    {
        $this->validate([
            'ticketName' => ['required', 'string', 'max:80'],
            'ticketDescription' => ['nullable', 'string', 'max:255'],
            'ticketPrice' => ['required', 'integer', 'min:0', 'max:1000000000'],
            'ticketQuota' => ['required', 'integer', 'min:1', 'max:1000000'],
            'ticketSalesStart' => ['nullable', 'date'],
            'ticketSalesEnd' => ['nullable', 'date', 'after_or_equal:ticketSalesStart'],
        ], [
            'ticketName.required' => 'Nama tiket wajib diisi.',
            'ticketPrice.required' => 'Harga wajib diisi.',
            'ticketPrice.integer' => 'Harga harus angka bulat, tanpa titik atau koma.',
            'ticketPrice.min' => 'Harga tidak boleh negatif.',
            'ticketQuota.required' => 'Kuota wajib diisi.',
            'ticketQuota.min' => 'Kuota minimal 1.',
            'ticketSalesEnd.after_or_equal' => 'Akhir penjualan tidak boleh lebih awal dari mulai.',
        ], [
            'ticketName' => 'nama tiket',
            'ticketPrice' => 'harga',
            'ticketQuota' => 'kuota',
        ]);

        $payload = [
            'name' => $this->ticketName,
            'description' => $this->ticketDescription ?: null,
            'price' => (int) $this->ticketPrice,
            'quota' => (int) $this->ticketQuota,
            'sales_start' => $this->ticketSalesStart ?: null,
            'sales_end' => $this->ticketSalesEnd ?: null,
        ];

        if ($this->editingTicketId) {
            $type = TicketType::where('event_id', $this->event->id)->findOrFail($this->editingTicketId);

            if ($payload['quota'] < $type->sold) {
                $this->addError('ticketQuota', "Kuota tidak boleh lebih kecil dari tiket yang sudah terjual ({$type->sold}).");

                return;
            }

            $type->update($payload);
            $message = 'Tipe tiket diperbarui.';
        } else {
            $this->event->ticketTypes()->create($payload);
            $message = 'Tipe tiket ditambahkan.';
        }

        $this->ticketModal = false;
        $this->resetTicketForm();
        $this->refreshEvent();

        $this->success($message, position: 'toast-top');
    }

    public function deleteTicket(int $id): void
    {
        $type = TicketType::where('event_id', $this->event->id)->findOrFail($id);

        if ($type->orderItems()->exists()) {
            $this->error('Tipe tiket yang sudah pernah dipesan tidak bisa dihapus.', position: 'toast-top');

            return;
        }

        $type->delete();

        $this->refreshEvent();
        $this->success('Tipe tiket dihapus.', position: 'toast-top');
    }

    /*
    |--------------------------------------------------------------------------
    | Sessions
    |--------------------------------------------------------------------------
    */

    public function createSession(): void
    {
        $this->resetSessionForm();
        $this->sessionModal = true;
    }

    public function editSession(int $id): void
    {
        $session = EventSession::where('event_id', $this->event->id)->findOrFail($id);

        $this->editingSessionId = $session->id;
        $this->sessionTitle = $session->title;
        $this->sessionSpeaker = (string) $session->speaker;
        $this->sessionTrack = (string) $session->track;
        $this->sessionStartsAt = $session->starts_at->format('Y-m-d\TH:i');
        $this->sessionEndsAt = $session->ends_at->format('Y-m-d\TH:i');

        $this->sessionModal = true;
    }

    public function saveSession(): void
    {
        $this->validate([
            'sessionTitle' => ['required', 'string', 'max:150'],
            'sessionSpeaker' => ['nullable', 'string', 'max:120'],
            'sessionTrack' => ['nullable', 'string', 'max:80'],
            'sessionStartsAt' => ['required', 'date'],
            'sessionEndsAt' => ['required', 'date', 'after_or_equal:sessionStartsAt'],
        ], [
            'sessionTitle.required' => 'Judul sesi wajib diisi.',
            'sessionEndsAt.after_or_equal' => 'Waktu selesai sesi tidak boleh lebih awal dari waktu mulai.',
        ], [
            'sessionTitle' => 'judul sesi',
            'sessionStartsAt' => 'waktu mulai',
            'sessionEndsAt' => 'waktu selesai',
        ]);

        $payload = [
            'title' => $this->sessionTitle,
            'speaker' => $this->sessionSpeaker ?: null,
            'track' => $this->sessionTrack ?: null,
            'starts_at' => $this->sessionStartsAt,
            'ends_at' => $this->sessionEndsAt,
        ];

        if ($this->editingSessionId) {
            EventSession::where('event_id', $this->event->id)->findOrFail($this->editingSessionId)->update($payload);
            $message = 'Sesi diperbarui.';
        } else {
            $payload['sort_order'] = $this->event->sessions()->count();
            $this->event->sessions()->create($payload);
            $message = 'Sesi ditambahkan.';
        }

        $this->sessionModal = false;
        $this->resetSessionForm();
        $this->refreshEvent();

        $this->success($message, position: 'toast-top');
    }

    public function deleteSession(int $id): void
    {
        EventSession::where('event_id', $this->event->id)->findOrFail($id)->delete();

        $this->refreshEvent();
        $this->success('Sesi dihapus.', position: 'toast-top');
    }

    #[Computed]
    public function revenue(): int
    {
        return (int) $this->event->orders()->where('status', 'paid')->sum('total');
    }

    #[Computed]
    public function orderCount(): int
    {
        return $this->event->orders()->count();
    }

    private function resetTicketForm(): void
    {
        $this->reset(['editingTicketId', 'ticketName', 'ticketDescription', 'ticketPrice', 'ticketQuota', 'ticketSalesStart', 'ticketSalesEnd']);
        $this->clearValidation();
    }

    private function resetSessionForm(): void
    {
        $this->reset(['editingSessionId', 'sessionTitle', 'sessionSpeaker', 'sessionTrack', 'sessionStartsAt', 'sessionEndsAt']);
        $this->clearValidation();
    }

    public function render(): View
    {
        return view('pages.admin.events.show')
            ->layout('layouts.admin')
            ->title($this->event->title);
    }
};

?>

<div class="space-y-6">
    <x-header :title="$event->title" :subtitle="($event->venue?->name ?? 'Tanpa lokasi').' - '.Format::dateLong($event->starts_at)" separator>
        <x-slot:actions>
            <x-button label="Semua event" link="{{ route('admin.events.index') }}" wire:navigate class="btn-ghost btn-sm" />
            <x-button
                :label="$event->isPublished() ? 'Tarik jadi draf' : 'Terbitkan'"
                icon="o-eye"
                wire:click="toggleStatus"
                class="btn-sm"
            />
            @if ($event->isPublished())
                <x-button label="Lihat halaman publik" icon="o-arrow-up-right" link="{{ route('events.show', $event) }}"
                          no-wire-navigate class="btn-primary btn-sm" />
            @endif
        </x-slot:actions>
    </x-header>

    {{-- STATS --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat title="Tiket terjual" :value="Format::number($event->quota_sold)" icon="o-ticket" color="text-primary"
                :description="'dari '.Format::number($event->quota_total).' kuota'" />
        <x-stat title="Pendapatan" :value="Format::rupiah($this->revenue)" icon="o-banknotes" color="text-success"
                :description="$this->orderCount.' pesanan'" />
        <x-stat title="Harga mulai" :value="Format::rupiah($event->min_price)" icon="o-tag" color="text-info"
                :description="$event->ticketTypes->count().' tipe tiket'" />
        <x-stat title="Status" :value="$event->status_label" icon="o-flag" color="text-base-content/60"
                :description="Format::countdown($event->starts_at)" />
    </div>

    {{-- TICKET TYPES --}}
    <x-card shadow>
        <x-slot:title class="text-base font-bold">Tipe tiket</x-slot:title>
        <x-slot:subtitle class="text-xs">Stok berkurang otomatis setiap ada pesanan</x-slot:subtitle>
        <x-slot:actions>
            <x-button label="Tambah tipe" icon="o-plus" wire:click="createTicket" class="btn-primary btn-sm" />
        </x-slot:actions>

        <div class="overflow-x-auto">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th class="text-right">Harga</th>
                        <th class="text-right">Terjual</th>
                        <th class="text-right">Sisa</th>
                        <th class="w-40">Masa penjualan</th>
                        <th class="w-24 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($event->ticketTypes as $type)
                        <tr>
                            <td>
                                <p class="font-semibold">{{ $type->name }}</p>
                                @if ($type->description)
                                    <p class="max-w-xs truncate text-xs text-base-content/50">{{ $type->description }}</p>
                                @endif
                            </td>
                            <td class="text-right font-semibold">{{ Format::rupiah($type->price) }}</td>
                            <td class="text-right">{{ Format::number($type->sold) }}</td>
                            <td class="text-right">
                                @if ($type->isSoldOut())
                                    <span class="badge badge-error badge-sm badge-outline">Habis</span>
                                @else
                                    <span class="font-medium">{{ Format::number($type->remaining) }}</span>
                                @endif
                            </td>
                            <td class="text-xs text-base-content/60">
                                @if ($type->sales_start || $type->sales_end)
                                    {{ $type->sales_start?->format('d M Y H:i') ?? 'Sekarang' }}
                                    <br>
                                    s/d {{ $type->sales_end?->format('d M Y H:i') ?? 'Selesai' }}
                                @else
                                    <span class="text-success">Selalu terbuka</span>
                                @endif
                            </td>
                            <td>
                                <div class="flex justify-end gap-1">
                                    <x-button icon="o-pencil-square" wire:click="editTicket({{ $type->id }})" class="btn-ghost btn-sm" />
                                    <x-button icon="o-trash" wire:click="deleteTicket({{ $type->id }})"
                                              wire:confirm="Hapus tipe tiket &quot;{{ $type->name }}&quot;?"
                                              class="btn-ghost btn-sm text-error" />
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-10 text-center">
                                <x-icon name="o-ticket" class="mx-auto size-8 text-base-content/30" />
                                <p class="mt-2 text-sm font-semibold">Belum ada tipe tiket</p>
                                <p class="mt-1 text-xs text-base-content/50">Tambahkan minimal satu tipe tiket agar event bisa dijual.</p>
                                <x-button label="Tambah tipe tiket" icon="o-plus" wire:click="createTicket" class="btn-primary btn-sm mt-4" />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

    {{-- SESSIONS --}}
    <x-card shadow>
        <x-slot:title class="text-base font-bold">Susunan acara</x-slot:title>
        <x-slot:subtitle class="text-xs">Sesi, pembicara, dan jadwal</x-slot:subtitle>
        <x-slot:actions>
            <x-button label="Tambah sesi" icon="o-plus" wire:click="createSession" class="btn-primary btn-sm" />
        </x-slot:actions>

        <div class="space-y-2">
            @forelse ($event->sessions as $session)
                <div class="flex items-center gap-4 rounded-lg border border-base-300 p-3">
                    <div class="w-24 shrink-0 text-sm">
                        <p class="font-black">{{ Format::time($session->starts_at) }}</p>
                        <p class="text-xs text-base-content/50">s/d {{ Format::time($session->ends_at) }}</p>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="font-semibold">{{ $session->title }}</p>
                        <p class="text-xs text-base-content/50">
                            {{ $session->speaker ?: 'Pembicara belum ditentukan' }}
                            @if ($session->track) &middot; {{ $session->track }} @endif
                        </p>
                    </div>
                    <div class="flex shrink-0 gap-1">
                        <x-button icon="o-pencil-square" wire:click="editSession({{ $session->id }})" class="btn-ghost btn-sm" />
                        <x-button icon="o-trash" wire:click="deleteSession({{ $session->id }})"
                                  wire:confirm="Hapus sesi &quot;{{ $session->title }}&quot;?"
                                  class="btn-ghost btn-sm text-error" />
                    </div>
                </div>
            @empty
                <p class="py-8 text-center text-sm text-base-content/50">Belum ada susunan acara.</p>
            @endforelse
        </div>
    </x-card>

    {{-- TICKET MODAL --}}
    <x-modal wire:model="ticketModal" :title="$editingTicketId ? 'Ubah tipe tiket' : 'Tipe tiket baru'"
             box-class="w-11/12 lg:w-1/2">
        <form wire:submit="saveTicket" class="space-y-4">
            <x-input label="Nama tiket" wire:model.blur="ticketName" placeholder="Contoh: Early Bird" :error-field="'ticketName'" required />
            <x-textarea label="Keterangan" wire:model.blur="ticketDescription" rows="2" placeholder="Benefit singkat untuk tipe tiket ini."
                        :error-field="'ticketDescription'" />

            <div class="grid gap-4 sm:grid-cols-2">
                <x-input label="Harga (Rp)" wire:model.blur="ticketPrice" placeholder="250000" :error-field="'ticketPrice'"
                         hint="Tanpa titik pemisah" required />
                <x-input label="Kuota" wire:model.blur="ticketQuota" placeholder="500" :error-field="'ticketQuota'" required />
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <x-input label="Mulai penjualan" type="datetime-local" wire:model.live="ticketSalesStart" :error-field="'ticketSalesStart'" />
                <x-input label="Akhir penjualan" type="datetime-local" wire:model.live="ticketSalesEnd" :error-field="'ticketSalesEnd'" />
            </div>

            <x-slot:actions>
                <x-button label="Batal" wire:click="$set('ticketModal', false)" />
                <x-button type="submit" label="Simpan" class="btn-primary" wire:loading.attr="disabled" wire:loading.class="btn-loading" />
            </x-slot:actions>
        </form>
    </x-modal>

    {{-- SESSION MODAL --}}
    <x-modal wire:model="sessionModal" :title="$editingSessionId ? 'Ubah sesi' : 'Sesi baru'" box-class="w-11/12 lg:w-1/2">
        <form wire:submit="saveSession" class="space-y-4">
            <x-input label="Judul sesi" wire:model.blur="sessionTitle" placeholder="Contoh: Keynote" :error-field="'sessionTitle'" required />

            <div class="grid gap-4 sm:grid-cols-2">
                <x-input label="Pembicara" wire:model.blur="sessionSpeaker" placeholder="Nama pembicara" :error-field="'sessionSpeaker'" />
                <x-input label="Track / ruang" wire:model.blur="sessionTrack" placeholder="Main Stage" :error-field="'sessionTrack'" />
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <x-input label="Mulai" type="datetime-local" wire:model.live="sessionStartsAt" :error-field="'sessionStartsAt'" required />
                <x-input label="Selesai" type="datetime-local" wire:model.live="sessionEndsAt" :error-field="'sessionEndsAt'" required />
            </div>

            <x-slot:actions>
                <x-button label="Batal" wire:click="$set('sessionModal', false)" />
                <x-button type="submit" label="Simpan" class="btn-primary" wire:loading.attr="disabled" wire:loading.class="btn-loading" />
            </x-slot:actions>
        </form>
    </x-modal>
</div>
