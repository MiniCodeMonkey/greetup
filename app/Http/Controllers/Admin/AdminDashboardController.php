<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Group;
use App\Models\Report;
use App\Models\Setting;
use App\Models\User;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function index(): View
    {
        $stats = [
            'totalUsers' => User::query()->count(),
            'totalGroups' => Group::query()->count(),
            'totalEvents' => Event::query()->count(),
            'eventsThisMonth' => Event::query()
                ->where('created_at', '>=', now()->startOfMonth())
                ->count(),
            'newUsersThisWeek' => User::query()
                ->where('created_at', '>=', now()->startOfWeek())
                ->count(),
        ];

        $recentReports = Report::query()
            ->pending()
            ->with(['reporter', 'reportable'])
            ->latest()
            ->take(10)
            ->get();

        $recentGroups = Group::query()
            ->with('organizer')
            ->latest()
            ->take(10)
            ->get();

        $seoTitle = 'Admin: Dashboard — '.Setting::get('site_name', config('app.name', 'Greetup'));

        return view('admin.dashboard', compact('stats', 'recentReports', 'recentGroups', 'seoTitle'));
    }
}
