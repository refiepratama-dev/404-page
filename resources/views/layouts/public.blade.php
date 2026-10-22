<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($title) ? $title.' - '.config('app.name') : config('app.name') }}</title>
    <meta name="description" content="@yield('description', 'Temukan dan beli tiket event pilihan di Indonesia. Konser, seminar, Signing, sampai festival.')">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-base-100 font-sans antialiased">

{{-- NAVBAR --}}
<header class="sticky top-0 z-30 border-b border-base-300/70 bg-base-100/85 backdrop-blur-md">
    <div class="navbar mx-auto max-w-7xl px-4 sm:px-6">
        <div class="navbar-start gap-2">
            <div class="dropdown lg:hidden">
                <div tabindex="0" role="button" class="btn btn-ghost btn-square" aria-label="Buka menu">
                    <x-icon name="o-bars-3" class="size-5" />
                </div>
                <ul tabindex="0" class="menu dropdown-content z-40 mt-3 w-56 rounded-box border border-base-300 bg-base-100 p-2 shadow-xl">
                    <li><a href="{{ route('events.index') }}" wire:navigate>Semua event</a></li>
                    @foreach (\App\Models\Event::CATEGORIES as $category)
                        <li>
                            <a href="{{ route('events.index', ['kategori' => $category]) }}" wire:navigate>
                                {{ $category }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>

            <x-app-brand />
        </div>

        <div class="navbar-center hidden lg:flex">
            <ul class="menu menu-horizontal gap-1 px-1 text-sm font-medium">
                <li>
                    <a href="{{ route('events.index') }}" wire:navigate
                       class="{{ request()->routeIs('events.*') ? 'menu-active' : '' }}">
                        Event
                    </a>
                </li>
                <li>
                    <a href="{{ route('events.index', ['urut' => 'populer']) }}" wire:navigate>Populer</a>
                </li>
                <li>
                    <a href="{{ route('events.index', ['urut' => 'termurah']) }}" wire:navigate>Termurah</a>
                </li>
            </ul>
        </div>

        <div class="navbar-end gap-1">
            <x-theme-toggle />

            @auth
                @if (auth()->user()->isAdmin())
                    <x-button label="Dashboard" icon="o-squares-2x2" link="{{ route('admin.dashboard') }}"
                              no-wire-navigate class="btn-ghost btn-sm hidden sm:flex" />
                @endif
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-circle btn-ghost btn-sm" title="Keluar" aria-label="Keluar">
                        <x-icon name="o-arrow-right-on-rectangle" class="size-5" />
                    </button>
                </form>
            @else
                <x-button label="Masuk" link="{{ route('login') }}" no-wire-navigate class="btn-ghost btn-sm" />
            @endauth
        </div>
    </div>
</header>

{{-- CONTENT --}}
<main>
    {{ $slot }}
</main>

{{-- FOOTER --}}
<footer class="mt-20 border-t border-base-300 bg-base-200/50">
    <div class="mx-auto grid max-w-7xl gap-10 px-4 py-12 sm:px-6 md:grid-cols-4">
        <div class="md:col-span-2">
            <x-app-brand />
            <p class="mt-4 max-w-sm text-sm leading-relaxed text-base-content/60">
                Platform ticketing untuk para event organizer di Indonesia. Kelola event,
                atur tipe tiket, dan pantau penjualan dari satu dashboard.
            </p>
        </div>

        <div>
            <h4 class="footer-title mb-3 text-sm font-bold">Kategori</h4>
            <ul class="space-y-2 text-sm text-base-content/60">
                @foreach (array_slice(\App\Models\Event::CATEGORIES, 0, 4) as $category)
                    <li>
                        <a href="{{ route('events.index', ['kategori' => $category]) }}" wire:navigate
                           class="link-hover">{{ $category }}</a>
                    </li>
                @endforeach
            </ul>
        </div>

        <div>
            <h4 class="footer-title mb-3 text-sm font-bold">Organizer</h4>
            <ul class="space-y-2 text-sm text-base-content/60">
                <li><a href="{{ route('admin.dashboard') }}" wire:navigate class="link-hover">Dashboard admin</a></li>
                <li><a href="{{ route('login') }}" wire:navigate class="link-hover">Masuk</a></li>
            </ul>
        </div>
    </div>

    <div class="border-t border-base-300 py-5">
        <p class="text-center text-xs text-base-content/50">
            &copy; {{ date('Y') }} {{ config('app.name') }} - Studi kasus Laravel + Livewire.
        </p>
    </div>
</footer>

<x-toast />

@livewireScripts

</body>
</html>
