<?php

use App\Http\Controllers\Admin\AvailabilityController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PhotoController;
use App\Http\Controllers\Admin\PropertyController as AdminPropertyController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Public\CheckoutController;
use App\Http\Controllers\Admin\ChannelLinkController;
use App\Http\Controllers\Admin\OwnerLeadController as AdminOwnerLeadController;
use App\Http\Controllers\Public\HomeController;
use App\Http\Controllers\Public\IcalController;
use App\Http\Controllers\Public\OwnerLeadController;
use App\Http\Controllers\Public\PropertyController;
use App\Http\Controllers\Public\StripeWebhookController;
use Illuminate\Support\Facades\Route;

/*
|--- Public site ---------------------------------------------------------
*/
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::post('/proprietari/richiesta', [OwnerLeadController::class, 'store'])->name('owners.lead');
Route::get('/immobili', [PropertyController::class, 'index'])->name('properties.index');
Route::get('/immobili/{property}', [PropertyController::class, 'show'])->name('properties.show');
Route::post('/immobili/{property}/preventivo', [PropertyController::class, 'quote'])->name('properties.quote');

// Direct booking + payment
Route::get('/immobili/{property}/checkout', [CheckoutController::class, 'show'])->name('checkout.show');
Route::post('/immobili/{property}/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
Route::get('/prenotazione/{booking}/conferma', [CheckoutController::class, 'success'])->name('checkout.success');
Route::get('/prenotazione/{booking}/annulla', [CheckoutController::class, 'cancel'])->name('checkout.cancel');
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handle'])->name('stripe.webhook');

// iCal feed consumed by the OTAs (Airbnb / Booking)
Route::get('/ical/{token}.ics', [IcalController::class, 'export'])->name('ical.export')->where('token', '[A-Za-z0-9]+');

/*
|--- Auth ----------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/admin/login', [LoginController::class, 'show'])->name('login');
    Route::post('/admin/login', [LoginController::class, 'login']);
});
Route::post('/admin/logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout');

/*
|--- Admin (channel manager backend) ------------------------------------
*/
Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/immobili', [AdminPropertyController::class, 'index'])->name('properties.index');
    Route::get('/immobili/nuovo', [AdminPropertyController::class, 'create'])->name('properties.create');
    Route::post('/immobili', [AdminPropertyController::class, 'store'])->name('properties.store');
    Route::get('/immobili/{property}/modifica', [AdminPropertyController::class, 'edit'])->name('properties.edit');
    Route::put('/immobili/{property}', [AdminPropertyController::class, 'update'])->name('properties.update');
    Route::delete('/immobili/{property}', [AdminPropertyController::class, 'destroy'])->name('properties.destroy');

    // Photos
    Route::post('/immobili/{property}/foto', [PhotoController::class, 'store'])->name('photos.store');
    Route::post('/immobili/{property}/foto/ordina', [PhotoController::class, 'reorder'])->name('photos.reorder');
    Route::delete('/foto/{photo}', [PhotoController::class, 'destroy'])->name('photos.destroy');
    Route::post('/foto/{photo}/copertina', [PhotoController::class, 'cover'])->name('photos.cover');

    // Availability / calendar
    Route::get('/immobili/{property}/calendario', [AvailabilityController::class, 'index'])->name('availability.index');
    Route::post('/immobili/{property}/calendario', [AvailabilityController::class, 'update'])->name('availability.update');

    // Channels (iCal sync)
    Route::post('/immobili/{property}/canali', [ChannelLinkController::class, 'update'])->name('channels.update');
    Route::post('/immobili/{property}/canali/sync', [ChannelLinkController::class, 'sync'])->name('channels.sync');

    // Owner leads (richieste dal sito aziendale)
    Route::get('/richieste', [AdminOwnerLeadController::class, 'index'])->name('leads.index');
    Route::patch('/richieste/{lead}', [AdminOwnerLeadController::class, 'update'])->name('leads.update');
    Route::delete('/richieste/{lead}', [AdminOwnerLeadController::class, 'destroy'])->name('leads.destroy');
});
