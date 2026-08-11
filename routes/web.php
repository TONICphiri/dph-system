<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/test-server-error', function () {
    throw new \Exception("Test 500 Error for DHP System");
});


Route::get('/test-error-page-403', function () {
    return view('errors.403');
});


Route::get('/test-error-page-404', function () {
    return view('errors.404');
});

Route::get('/test-error-page-419', function () {
    return view('errors.419');
});

Route::get('/test-error-page-429', function () {
    return view('errors.429');
});

Route::get('/test-error-page-500', function () {
    return view('errors.500');
});

Route::get('/test-error-page-503', function () {
    return view('errors.503');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
