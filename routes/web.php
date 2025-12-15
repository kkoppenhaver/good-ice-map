<?php

use App\Http\Controllers\LocationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RatingController;
use Illuminate\Support\Facades\Route;

// Home page with map
Route::get('/', [LocationController::class, 'index'])->name('home');

// Public API endpoint for map markers (no auth required)
Route::get('/api/locations', [LocationController::class, 'api'])->name('api.locations');

// Protected API endpoints (require authentication but no CSRF for AJAX)
Route::middleware(['auth'])->group(function () {
    Route::post('/api/expand-url', [LocationController::class, 'expandUrl'])->name('api.expand-url');
    Route::post('/api/search-place', [LocationController::class, 'searchPlace'])->name('api.search-place');
    Route::post('/api/fetch-place-details', [LocationController::class, 'fetchPlaceDetails'])->name('api.fetch-place-details');
});

// Location routes
Route::resource('locations', LocationController::class)->except(['index', 'edit', 'update', 'destroy']);

// Rating routes
Route::post('/locations/{location}/rate', [RatingController::class, 'store'])->name('locations.rate');

// Dashboard
Route::get('/dashboard', function () {
    $locations = auth()->user()->locations()->withCount('ratings')->latest()->get();
    return view('dashboard', compact('locations'));
})->middleware(['auth', 'verified'])->name('dashboard');

// Profile routes
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
