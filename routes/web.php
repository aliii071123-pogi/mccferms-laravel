<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EquipmentController;
use App\Http\Controllers\FacilityController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReservationController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => Auth::check() ? to_route('dashboard') : to_route('login'));
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::get('/auth/microsoft', [AuthController::class, 'redirectToMicrosoft'])->name('microsoft.redirect');
Route::get('/auth/microsoft/callback', [AuthController::class, 'microsoftCallback'])->name('microsoft.callback');

Route::middleware(['auth', 'student.profile'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/profile/complete', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile/complete', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('/facilities', [FacilityController::class, 'index'])->name('facilities.index');
    Route::get('/equipment', [EquipmentController::class, 'index'])->name('equipment.index');

    Route::get('/reservations', [ReservationController::class, 'index'])->name('reservations.index');
    Route::get('/reservations/create', [ReservationController::class, 'create'])->name('reservations.create');
    Route::post('/reservations', [ReservationController::class, 'store'])->name('reservations.store');
    Route::get('/reservations/{reservation}', [ReservationController::class, 'show'])->name('reservations.show');
    Route::get('/reservations/{reservation}/edit', [ReservationController::class, 'edit'])->name('reservations.edit');
    Route::put('/reservations/{reservation}', [ReservationController::class, 'update'])->name('reservations.update');
    Route::delete('/reservations/{reservation}/cancel', [ReservationController::class, 'cancel'])->name('reservations.cancel');

    Route::middleware('role:staff,admin,super_admin')->group(function () {
        Route::post('/facilities', [FacilityController::class, 'store'])->name('facilities.store');
        Route::put('/facilities/{facility}', [FacilityController::class, 'update'])->name('facilities.update');
        Route::delete('/facilities/{facility}', [FacilityController::class, 'destroy'])->name('facilities.destroy');
        Route::post('/equipment', [EquipmentController::class, 'store'])->name('equipment.store');
        Route::put('/equipment/{equipment}', [EquipmentController::class, 'update'])->name('equipment.update');
        Route::delete('/equipment/{equipment}', [EquipmentController::class, 'destroy'])->name('equipment.destroy');
    });

    Route::middleware('role:admin,super_admin')->group(function () {
        Route::put('/reservations/{reservation}/decision', [ReservationController::class, 'respond'])->name('reservations.respond');
        Route::delete('/reservations/{reservation}', [ReservationController::class, 'destroy'])->name('reservations.destroy');
    });

    Route::middleware('role:super_admin')->group(function () {
        Route::get('/accounts', [AccountController::class, 'index'])->name('accounts.index');
        Route::post('/accounts', [AccountController::class, 'store'])->name('accounts.store');
        Route::put('/accounts/{user}', [AccountController::class, 'update'])->name('accounts.update');
        Route::delete('/accounts/{user}', [AccountController::class, 'destroy'])->name('accounts.destroy');
    });
});
