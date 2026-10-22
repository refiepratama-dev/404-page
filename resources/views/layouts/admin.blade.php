<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($title) ? $title.' - Admin '.config('app.name') : 'Admin '.config('app.name') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-base-200 font-sans antialiased">

{{-- NAVBAR (mobile) --}}
<x-nav sticky class="lg:hidden">
    <x-slot:brand>
        <x-app-brand />
    </x-slot:brand>
    <x-slot:actions>
        <label for="admin-drawer" class="lg:hidden me-3">
            <x-icon name="o-bars-3" class="cursor-pointer" />
        </label>
    </x-slot:actions>
</x-nav>

<x-main>
    {{-- SIDEBAR --}}
    <x-slot:sidebar drawer="admin-drawer" collapsible class="bg-base-100 lg:bg-inherit">
        <x-app-brand class="px-5 pt-4" href="{{ route('admin.dashboard') }}" />

        <x-menu activate-by-route class="mt-6">
            <x-menu-title title="Kelola" />

            <x-menu-item title="Dashboard" icon="o-squares-2x2" link="{{ route('admin.dashboard') }}" />
            <x-menu-item title="Event" icon="o-calendar-days" link="{{ route('admin.events.index') }}" />
            <x-menu-item title="Pesanan" icon="o-shopping-bag" link="{{ route('admin.orders.index') }}" />
            <x-menu-item title="Lokasi" icon="o-map-pin" link="{{ route('admin.venues.index') }}" />

            <x-menu-separator />
            <x-menu-title title="Lainnya" />

            <x-menu-item title="Lihat situs publik" icon="o-arrow-up-right" link="{{ route('events.index') }}" no-wire-navigate />
        </x-menu>

        @auth
            <x-menu-separator />

            <x-list-item
                :item="auth()->user()"
                value="name"
                sub-value="email"
                no-separator
                no-hover
                class="-mx-2 !-my-2 rounded"
            >
                <x-slot:actions>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="btn btn-circle btn-ghost btn-xs" title="Keluar" aria-label="Keluar">
                            <x-icon name="o-arrow-right-on-rectangle" class="size-4" />
                        </button>
                    </form>
                </x-slot:actions>
            </x-list-item>
        @endauth
    </x-slot:sidebar>

    <x-slot:content>
        {{ $slot }}
    </x-slot:content>
</x-main>

<x-toast />

@livewireScripts

</body>
</html>
