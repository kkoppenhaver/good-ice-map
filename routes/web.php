<?php

use App\Http\Controllers\AdminLocationController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RatingController;
use App\Http\Controllers\SlackInteractionController;
use App\Http\Middleware\VerifySlackSignature;
use Illuminate\Support\Facades\Route;

// Home page (landing for guests, map for authenticated users)
Route::get('/', [LocationController::class, 'index'])->name('home');

// Always-show-the-map route for guests (and authed users who want to bookmark it)
Route::get('/map', fn () => view('locations.index'))->name('map');

// Public API endpoint for map markers (no auth required)
Route::get('/api/locations', [LocationController::class, 'api'])->name('api.locations');

// Protected API endpoints (require authentication but no CSRF for AJAX)
Route::middleware(['auth'])->group(function () {
    Route::post('/api/parse-maps-link', [LocationController::class, 'parseMapsLink'])->name('api.parse-maps-link');
});

// Location routes
Route::resource('locations', LocationController::class)->except(['index']);

// Rating routes
Route::post('/locations/{location}/rate', [RatingController::class, 'store'])->name('locations.rate');

// Dashboard
Route::get('/dashboard', function () {
    $locations = auth()->user()->locations()
        ->whereIn('status', ['pending', 'approved'])
        ->withCount('ratings')
        ->latest()
        ->get();

    return view('dashboard', compact('locations'));
})->middleware(['auth', 'verified'])->name('dashboard');

// Slack interactivity webhook (signature-verified, no auth)
Route::post('/api/slack/interactions', [SlackInteractionController::class, 'handle'])
    ->middleware(VerifySlackSignature::class)
    ->name('slack.interactions');

// Admin moderation
Route::middleware(['auth', 'can:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/locations', [AdminLocationController::class, 'index'])->name('locations.index');
    Route::post('/locations/{location}/approve', [AdminLocationController::class, 'approve'])->name('locations.approve');
    Route::post('/locations/{location}/reject', [AdminLocationController::class, 'reject'])->name('locations.reject');
});

// Profile routes
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
