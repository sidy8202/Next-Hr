<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\DashboardController;
use App\Livewire\Departments\DepartmentIndex;
use App\Livewire\Departments\DepartmentShow;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');


// Invités (Non authentifiés)
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:login');
});

// Authentifiés
Route::middleware(['auth', 'ready'])->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/password/change', [PasswordController::class, 'edit'])->name('password.change');
    Route::put('/password/change', [PasswordController::class, 'update'])->name('password.update');

    Route::get('/platform',[DashboardController::class, 'index'])->name('platform');

    Route::middleware('company')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::post('/welcome/dismiss', [DashboardController::class, 'dismissWelcome'])->name('welcome.dismiss');
        Route::livewire('/departements', DepartmentIndex::class)->name('departments.index');
        Route::livewire('/departements/{department}', DepartmentShow::class)->name('departments.show');
    });
});
