<?php

use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Settings\SettingsController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('guest')->group(function () {
    Route::get('register', [RegisterController::class, 'create'])->name('register');
    Route::post('register', [RegisterController::class, 'store'])->middleware('throttle:registration');

    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store']);

    Route::get('forgot-password', [ForgotPasswordController::class, 'create'])->name('password.request');
    Route::post('forgot-password', [ForgotPasswordController::class, 'store'])->name('password.email');

    Route::get('reset-password/{token}', [ResetPasswordController::class, 'create'])->name('password.reset');
    Route::post('reset-password', [ResetPasswordController::class, 'store'])->name('password.update');
});

Route::middleware('auth')->group(function () {
    // Routes accessible to suspended users
    Route::post('logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('suspended', function () {
        return view('auth.suspended');
    })->name('suspended');

    // All other authenticated routes require non-suspended account
    Route::middleware('notSuspended')->group(function () {
        Route::get('email/verify', function () {
            return view('auth.verify-email');
        })->name('verification.notice');

        Route::get('email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
            $request->fulfill();

            return redirect('/dashboard');
        })->middleware('signed')->name('verification.verify');

        Route::post('email/verification-notification', function (Request $request) {
            $request->user()->sendEmailVerificationNotification();

            return back()->with('status', 'verification-link-sent');
        })->middleware('throttle:6,1')->name('verification.send');

        Route::get('dashboard', function () {
            return view('welcome');
        })->name('dashboard');

        Route::get('settings', [SettingsController::class, 'index'])->name('settings');
        Route::put('settings/profile', [SettingsController::class, 'updateProfile'])->name('settings.profile.update');
        Route::put('settings/account', [SettingsController::class, 'updateAccount'])->name('settings.account.update');
        Route::put('settings/privacy', [SettingsController::class, 'updatePrivacy'])->name('settings.privacy.update');
        Route::put('settings/notifications', [SettingsController::class, 'updateNotifications'])->name('settings.notifications.update');
        Route::delete('settings/account', [SettingsController::class, 'deleteAccount'])->name('settings.account.delete');
    });
});
