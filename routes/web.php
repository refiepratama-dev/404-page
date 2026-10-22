<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public
|--------------------------------------------------------------------------
*/

Route::livewire('/', 'pages::events.index')->name('home');
Route::livewire('/events', 'pages::events.index')->name('events.index');
Route::livewire('/events/{event}', 'pages::events.show')->name('events.show');
Route::livewire('/checkout', 'pages::checkout')->name('checkout');

/*
|--------------------------------------------------------------------------
| Auth
|--------------------------------------------------------------------------
*/

Route::livewire('/login', 'pages::auth.login')->name('login');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

/*
|--------------------------------------------------------------------------
| Admin
|--------------------------------------------------------------------------
*/

Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
    Route::livewire('/', 'pages::admin.dashboard')->name('dashboard');
    Route::livewire('/events', 'pages::admin.events.index')->name('events.index');
    Route::livewire('/events/{event}', 'pages::admin.events.show')->name('events.show');
    Route::livewire('/orders', 'pages::admin.orders.index')->name('orders.index');
    Route::livewire('/venues', 'pages::admin.venues.index')->name('venues.index');
});
