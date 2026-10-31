<?php

use App\Http\Controllers\CarController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\SignupController;  
use App\Http\Controllers\LoginController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');


Route::get('/car/search', [CarController::class, 'search'])->name('car.search');
Route::get('/s.html', [CarController::class, 'search'])->name('car.search.alt');
Route::middleware(['auth'])->group(function () {
    Route::get('/car/watchlist', [CarController::class, 'watchlist'])->name('car.watchlist');
    Route::post('/car/{car}/watchlist', [CarController::class, 'toggleWatchlist'])->name('car.watchlist.toggle');
    Route::resource('car', CarController::class)->except(['show']);
});

Route::get('/car/{car}', [CarController::class, 'show'])->name('car.show');

Route::get('/storage/{path}', function (string $path) {
    $fullPath = storage_path('app/public/' . $path);
    if (!file_exists($fullPath)) {
        abort(404);
    }
    return response()->file($fullPath);
})->where('path', '.*')->name('storage.local');

Route::get('/signup', [SignupController::class, 'create'])->name('signup');
Route::post('/signup', [SignupController::class, 'sign'])->name('sign');
Route::get('/login', [LoginController::class, 'create'])->name('login');
Route::post('/login', [LoginController::class, 'store'])->name('login.store');
Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
