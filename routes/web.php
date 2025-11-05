<?php

use App\Http\Controllers\LocationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RatingController;
use Illuminate\Support\Facades\Route;

// Home page with map
Route::get('/', [LocationController::class, 'index'])->name('home');

// API endpoint for map markers
Route::get('/api/locations', [LocationController::class, 'api'])->name('api.locations');

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
