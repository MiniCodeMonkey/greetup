<?php

use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Groups\GroupAnalyticsController;
use App\Http\Controllers\Groups\GroupController;
use App\Http\Controllers\Groups\GroupJoinRequestController;
use App\Http\Controllers\Groups\GroupMemberManagementController;
use App\Http\Controllers\Groups\GroupSettingsController;
use App\Http\Controllers\Groups\LeadershipTeamController;
use App\Http\Controllers\Groups\OwnershipTransferController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\Settings\SettingsController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('members/{user}', [MemberController::class, 'show'])->name('members.show');

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
        Route::get('settings/data-export', [SettingsController::class, 'exportData'])->name('settings.data-export');
        Route::delete('settings/account', [SettingsController::class, 'deleteAccount'])->name('settings.account.delete');

        Route::get('groups/create', [GroupController::class, 'create'])->name('groups.create');
        Route::post('groups', [GroupController::class, 'store'])->name('groups.store');
        Route::post('groups/{group:slug}/join', [GroupController::class, 'join'])->name('groups.join');
        Route::post('groups/{group:slug}/leave', [GroupController::class, 'leave'])->name('groups.leave');
        Route::post('groups/{group:slug}/request-join', [GroupController::class, 'requestJoin'])->name('groups.request-join');
        Route::post('groups/{group:slug}/join-requests/{joinRequest}/approve', [GroupController::class, 'approveRequest'])->name('groups.join-requests.approve');
        Route::post('groups/{group:slug}/join-requests/{joinRequest}/deny', [GroupController::class, 'denyRequest'])->name('groups.join-requests.deny');

        Route::middleware('groupRole:assistant_organizer')->group(function () {
            Route::get('groups/{group:slug}/manage/members', [GroupMemberManagementController::class, 'index'])->name('groups.manage.members');
            Route::get('groups/{group:slug}/manage/members/export', [GroupMemberManagementController::class, 'export'])->name('groups.manage.members.export');
            Route::post('groups/{group:slug}/manage/members/{user}/remove', [GroupMemberManagementController::class, 'remove'])->name('groups.manage.members.remove');
            Route::post('groups/{group:slug}/manage/members/{user}/ban', [GroupMemberManagementController::class, 'ban'])->name('groups.manage.members.ban');
            Route::post('groups/{group:slug}/manage/members/{user}/unban', [GroupMemberManagementController::class, 'unban'])->name('groups.manage.members.unban');

            Route::get('groups/{group:slug}/manage/requests', [GroupJoinRequestController::class, 'index'])->name('groups.manage.requests');
            Route::post('groups/{group:slug}/manage/requests/{joinRequest}/approve', [GroupJoinRequestController::class, 'approve'])->name('groups.manage.requests.approve');
            Route::post('groups/{group:slug}/manage/requests/{joinRequest}/deny', [GroupJoinRequestController::class, 'deny'])->name('groups.manage.requests.deny');
        });

        Route::middleware('groupRole:co_organizer')->group(function () {
            Route::get('groups/{group:slug}/manage/settings', [GroupSettingsController::class, 'edit'])->name('groups.manage.settings');
            Route::put('groups/{group:slug}/manage/settings', [GroupSettingsController::class, 'update'])->name('groups.manage.settings.update');

            Route::get('groups/{group:slug}/manage/team', [LeadershipTeamController::class, 'index'])->name('groups.manage.team');
            Route::post('groups/{group:slug}/manage/team/{user}/role', [LeadershipTeamController::class, 'update'])->name('groups.manage.team.update-role');

            Route::get('groups/{group:slug}/manage/analytics', [GroupAnalyticsController::class, 'index'])->name('groups.manage.analytics');
        });

        Route::middleware('groupRole:organizer')->group(function () {
            Route::get('groups/{group:slug}/manage/transfer', [OwnershipTransferController::class, 'edit'])->name('groups.manage.transfer');
            Route::post('groups/{group:slug}/manage/transfer', [OwnershipTransferController::class, 'update'])->name('groups.manage.transfer.update');
            Route::delete('groups/{group:slug}', [GroupController::class, 'destroy'])->name('groups.destroy');
        });
    });
});

Route::get('groups/{group:slug}', [GroupController::class, 'show'])->name('groups.show');
