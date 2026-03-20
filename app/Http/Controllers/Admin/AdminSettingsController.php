<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSettingsRequest;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AdminSettingsController extends Controller
{
    public function index(): View
    {
        $settings = Setting::allCached();

        $seoTitle = 'Admin: Settings — '.config('app.name', 'Greetup');

        return view('admin.settings', compact('settings', 'seoTitle'));
    }

    public function update(UpdateSettingsRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $validated['registration_enabled'] = $request->boolean('registration_enabled') ? '1' : '0';
        $validated['require_email_verification'] = $request->boolean('require_email_verification') ? '1' : '0';
        $maxGroups = $validated['max_groups_per_user'] ?? null;
        $validated['max_groups_per_user'] = $maxGroups !== null ? (string) $maxGroups : null;

        foreach ($validated as $key => $value) {
            Setting::query()->updateOrCreate(
                ['key' => $key],
                ['value' => $value],
            );
        }

        Setting::clearCache();

        return redirect()->route('admin.settings')->with('success', 'Settings updated successfully.');
    }
}
