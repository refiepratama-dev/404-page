<?php

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

new class extends Component
{
    public string $email = '';

    public string $password = '';

    public bool $remember = false;

    public function login(): void
    {
        $this->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ], [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'password.required' => 'Password wajib diisi.',
        ], [
            'email' => 'email',
            'password' => 'password',
        ]);

        if (! Auth::attempt($this->only('email', 'password'), $this->remember)) {
            $this->addError('email', 'Email atau password tidak cocok.');

            return;
        }

        session()->regenerate();

        $this->redirectRoute(auth()->user()->isAdmin() ? 'admin.dashboard' : 'events.index', navigate: true);
    }

    public function render(): View
    {
        return view('pages.auth.login')
            ->layout('layouts.public')
            ->title('Masuk');
    }
};

?>

<div class="mx-auto max-w-md px-4 py-16 sm:px-6">
    <div class="card border border-base-300 bg-base-100 shadow-lg">
        <div class="card-body">
            <div class="mb-2 text-center">
                <div class="mx-auto flex size-12 items-center justify-center rounded-xl bg-primary/10 text-primary">
                    <x-icon name="o-arrow-right-on-rectangle" class="size-6" />
                </div>
                <h1 class="mt-4 text-xl font-black">Masuk ke akun</h1>
                <p class="mt-1 text-sm text-base-content/60">
                    Gunakan akun admin untuk mengelola event dan pesanan.
                </p>
            </div>

            <form wire:submit="login" class="mt-4 space-y-4">
                <x-input
                    label="Email"
                    type="email"
                    placeholder="admin@rakit.test"
                    icon="o-envelope"
                    wire:model.blur="email"
                    :error-field="'email'"
                    autocomplete="email"
                    required
                />

                <x-input
                    label="Password"
                    type="password"
                    placeholder="password"
                    icon="o-lock-closed"
                    wire:model="password"
                    :error-field="'password'"
                    autocomplete="current-password"
                    required
                />

                <label class="flex cursor-pointer items-center gap-2 text-sm">
                    <x-checkbox wire:model.live="remember" label="Ingat saya" />
                </label>

                <x-button
                    type="submit"
                    label="Masuk"
                    class="btn-primary btn-block"
                    wire:loading.attr="disabled"
                    wire:loading.class="btn-loading"
                />
            </form>

            <div class="divider my-2 text-xs text-base-content/50">akun demo</div>

            <div class="rounded-xl border border-dashed border-base-300 bg-base-200/50 p-4 text-xs">
                <p class="font-semibold">Admin</p>
                <p class="mt-0.5 font-mono text-base-content/70">admin@rakit.test / password</p>
                <p class="mt-2 font-semibold">Organizer</p>
                <p class="mt-0.5 font-mono text-base-content/70">panitia@rakit.test / password</p>
            </div>
        </div>
    </div>

    <p class="mt-6 text-center text-sm text-base-content/60">
        <a href="{{ route('events.index') }}" wire:navigate class="link link-hover">Kembali ke daftar event</a>
    </p>
</div>
