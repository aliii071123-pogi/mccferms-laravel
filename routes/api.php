<?php

use App\Http\Controllers\Api\MobileAuthController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EquipmentController;
use App\Http\Controllers\FacilityController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\ReservationController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/auth/exchange', [MobileAuthController::class, 'exchange'])
        ->middleware('throttle:10,1')
        ->name('api.auth.exchange');

    Route::middleware(['auth:sanctum', 'account.active'])->group(function () {
        Route::get('/me', [MobileAuthController::class, 'me'])->name('api.me');
        Route::get('/dashboard', [DashboardController::class, 'api'])->name('api.dashboard');
        Route::put('/profile', [MobileAuthController::class, 'updateProfile'])->name('api.profile.update');
        Route::delete('/auth/session', [MobileAuthController::class, 'logout'])->name('api.auth.logout');
        Route::get('/facilities', [FacilityController::class, 'apiIndex'])->name('api.facilities.index');
        Route::get('/equipment', [EquipmentController::class, 'apiIndex'])->name('api.equipment.index');
        Route::get('/organizations', [OrganizationController::class, 'apiIndex'])->name('api.organizations.index');
        Route::get('/calendar', [CalendarController::class, 'apiIndex'])->name('api.calendar.index');

        Route::middleware('student.profile')->group(function () {
            Route::get('/reservations', [ReservationController::class, 'index'])->name('api.reservations.index');
            Route::post('/reservations', [ReservationController::class, 'store'])->name('api.reservations.store');
            Route::get('/reservations/{reservation}', [ReservationController::class, 'show'])->name('api.reservations.show');
            Route::put('/reservations/{reservation}', [ReservationController::class, 'update'])->name('api.reservations.update');
            Route::delete('/reservations/{reservation}/cancel', [ReservationController::class, 'cancel'])->name('api.reservations.cancel');
        });

        Route::middleware('role:admin,super_admin')->group(function () {
            Route::put('/reservations/{reservation}/decision', [ReservationController::class, 'respond'])->name('api.reservations.respond');
            Route::delete('/reservations/{reservation}', [ReservationController::class, 'destroy'])->name('api.reservations.destroy');
        });

        Route::middleware('role:staff,admin,super_admin')->group(function () {
            Route::post('/facilities', [FacilityController::class, 'store'])->name('api.facilities.store');
            Route::put('/facilities/{facility}', [FacilityController::class, 'update'])->name('api.facilities.update');
            Route::delete('/facilities/{facility}', [FacilityController::class, 'destroy'])->name('api.facilities.destroy');
            Route::post('/equipment', [EquipmentController::class, 'store'])->name('api.equipment.store');
            Route::put('/equipment/{equipment}', [EquipmentController::class, 'update'])->name('api.equipment.update');
            Route::delete('/equipment/{equipment}', [EquipmentController::class, 'destroy'])->name('api.equipment.destroy');
        });
    });
});
