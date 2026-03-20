<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminGroupController;
use App\Http\Controllers\Admin\AdminInterestController;
use App\Http\Controllers\Admin\AdminReportController;
use App\Http\Controllers\Admin\AdminSettingsController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\BlockController;
use App\Http\Controllers\Discussions\DiscussionController;
use App\Http\Controllers\Events\AttendeeManagementController;
use App\Http\Controllers\Events\EventController;
use App\Http\Controllers\Groups\GroupAnalyticsController;
use App\Http\Controllers\Groups\GroupController;
use App\Http\Controllers\Groups\GroupJoinRequestController;
use App\Http\Controllers\Groups\GroupMemberManagementController;
use App\Http\Controllers\Groups\GroupSettingsController;
use App\Http\Controllers\Groups\LeadershipTeamController;
use App\Http\Controllers\Groups\OwnershipTransferController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\Messages\ConversationController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\Settings\SettingsController;
use App\Livewire\DashboardPage;
use App\Livewire\ExplorePage;
use App\Livewire\GlobalSearch;
use App\Livewire\GroupSearchPage;
use App\Models\Conversation;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect('/dashboard');
    }

    return redirect('/explore');
});

Route::livewire('/explore', ExplorePage::class)->name('explore');
Route::livewire('/search', GlobalSearch::class)->name('search');

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

        Route::livewire('dashboard', DashboardPage::class)->name('dashboard');

        Route::get('settings', [SettingsController::class, 'index'])->name('settings');
        Route::put('settings/profile', [SettingsController::class, 'updateProfile'])->name('settings.profile.update');
        Route::put('settings/account', [SettingsController::class, 'updateAccount'])->name('settings.account.update');
        Route::put('settings/privacy', [SettingsController::class, 'updatePrivacy'])->name('settings.privacy.update');
        Route::put('settings/notifications', [SettingsController::class, 'updateNotifications'])->name('settings.notifications.update');
        Route::get('settings/data-export', [SettingsController::class, 'exportData'])->name('settings.data-export');
        Route::delete('settings/account', [SettingsController::class, 'deleteAccount'])->name('settings.account.delete');

        Route::post('members/{user}/block', [BlockController::class, 'store'])->name('members.block');
        Route::delete('members/{user}/block', [BlockController::class, 'destroy'])->name('members.unblock');

        Route::post('reports', [ReportController::class, 'store'])->name('reports.store')->middleware('throttle:report-submission');

        Route::get('messages', [ConversationController::class, 'index'])->name('messages.index');
        Route::post('messages', [ConversationController::class, 'store'])->name('messages.store')->middleware('throttle:dm');
        Route::get('messages/{conversation}', fn (Conversation $conversation) => view('messages.show', ['conversation' => $conversation]))->name('messages.show');

        Route::get('groups/create', [GroupController::class, 'create'])->name('groups.create');
        Route::post('groups', [GroupController::class, 'store'])->name('groups.store');
        Route::get('groups/{group:slug}/discussions/create', [DiscussionController::class, 'create'])->name('discussions.create');
        Route::post('groups/{group:slug}/discussions', [DiscussionController::class, 'store'])->name('discussions.store');
        Route::get('groups/{group:slug}/discussions/{discussion:slug}', [DiscussionController::class, 'show'])->name('discussions.show');

        Route::post('groups/{group:slug}/join', [GroupController::class, 'join'])->name('groups.join');
        Route::post('groups/{group:slug}/leave', [GroupController::class, 'leave'])->name('groups.leave');
        Route::post('groups/{group:slug}/toggle-mute', [GroupController::class, 'toggleMute'])->name('groups.toggle-mute');
        Route::post('groups/{group:slug}/request-join', [GroupController::class, 'requestJoin'])->name('groups.request-join');
        Route::post('groups/{group:slug}/join-requests/{joinRequest}/approve', [GroupController::class, 'approveRequest'])->name('groups.join-requests.approve');
        Route::post('groups/{group:slug}/join-requests/{joinRequest}/deny', [GroupController::class, 'denyRequest'])->name('groups.join-requests.deny');

        Route::middleware('groupRole:event_organizer')->group(function () {
            Route::get('groups/{group:slug}/events/create', [EventController::class, 'create'])->name('events.create');
            Route::post('groups/{group:slug}/events', [EventController::class, 'store'])->name('events.store');
            Route::post('groups/{group:slug}/events/{event:slug}/cancel', [EventController::class, 'cancel'])->name('events.cancel');
        });

        Route::get('groups/{group:slug}/events/{event:slug}/edit', [EventController::class, 'edit'])->name('events.edit');
        Route::put('groups/{group:slug}/events/{event:slug}', [EventController::class, 'update'])->name('events.update');

        Route::get('groups/{group:slug}/events/{event:slug}/attendees', [AttendeeManagementController::class, 'index'])->name('events.attendees');
        Route::get('groups/{group:slug}/events/{event:slug}/attendees/export', [AttendeeManagementController::class, 'export'])->name('events.attendees.export');

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

        Route::middleware('role:admin')->group(function () {
            Route::get('admin', [AdminDashboardController::class, 'index'])->name('admin.dashboard');

            Route::get('admin/users', [AdminUserController::class, 'index'])->name('admin.users.index');
            Route::get('admin/users/{user}', [AdminUserController::class, 'show'])->name('admin.users.show');
            Route::post('admin/users/{user}/suspend', [AdminUserController::class, 'suspend'])->name('admin.users.suspend');
            Route::post('admin/users/{user}/unsuspend', [AdminUserController::class, 'unsuspend'])->name('admin.users.unsuspend');
            Route::delete('admin/users/{user}', [AdminUserController::class, 'destroy'])->name('admin.users.destroy');

            Route::get('admin/groups', [AdminGroupController::class, 'index'])->name('admin.groups.index');
            Route::get('admin/groups/{group}', [AdminGroupController::class, 'show'])->name('admin.groups.show');
            Route::delete('admin/groups/{group}', [AdminGroupController::class, 'destroy'])->name('admin.groups.destroy');

            Route::get('admin/reports', [AdminReportController::class, 'index'])->name('admin.reports.index');
            Route::post('admin/reports/{report}/review', [AdminReportController::class, 'review'])->name('admin.reports.review');
            Route::post('admin/reports/{report}/resolve', [AdminReportController::class, 'resolve'])->name('admin.reports.resolve');
            Route::post('admin/reports/{report}/dismiss', [AdminReportController::class, 'dismiss'])->name('admin.reports.dismiss');
            Route::post('admin/reports/{report}/suspend-user', [AdminReportController::class, 'suspendUser'])->name('admin.reports.suspend-user');
            Route::post('admin/reports/{report}/delete-content', [AdminReportController::class, 'deleteContent'])->name('admin.reports.delete-content');

            Route::get('admin/interests', [AdminInterestController::class, 'index'])->name('admin.interests.index');
            Route::get('admin/interests/create', [AdminInterestController::class, 'create'])->name('admin.interests.create');
            Route::post('admin/interests', [AdminInterestController::class, 'store'])->name('admin.interests.store');
            Route::get('admin/interests/{interest}/edit', [AdminInterestController::class, 'edit'])->name('admin.interests.edit');
            Route::put('admin/interests/{interest}', [AdminInterestController::class, 'update'])->name('admin.interests.update');
            Route::delete('admin/interests/{interest}', [AdminInterestController::class, 'destroy'])->name('admin.interests.destroy');
            Route::post('admin/interests/{interest}/merge', [AdminInterestController::class, 'merge'])->name('admin.interests.merge');

            Route::get('admin/settings', [AdminSettingsController::class, 'index'])->name('admin.settings');
            Route::put('admin/settings', [AdminSettingsController::class, 'update'])->name('admin.settings.update');
        });
    });
});

Route::livewire('/groups', GroupSearchPage::class)->name('groups.index');
Route::get('groups/{group:slug}', [GroupController::class, 'show'])->name('groups.show');
Route::get('groups/{group:slug}/events/{event:slug}', [EventController::class, 'show'])->name('events.show');
Route::get('groups/{group:slug}/events/{event:slug}/calendar', [EventController::class, 'calendar'])->name('events.calendar');
