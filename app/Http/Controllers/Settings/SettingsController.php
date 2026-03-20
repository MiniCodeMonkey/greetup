<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateAccountRequest;
use App\Http\Requests\Settings\UpdateProfileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    /**
     * Display the settings page.
     */
    public function index(Request $request): View
    {
        $section = $request->query('section', 'profile');

        return view('settings.index', [
            'section' => $section,
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function updateProfile(UpdateProfileRequest $request): RedirectResponse
    {
        $request->user()->update($request->validated());

        return redirect()->route('settings', ['section' => 'profile'])
            ->with('status', 'Profile updated successfully.');
    }

    /**
     * Update the user's account (email/password).
     */
    public function updateAccount(UpdateAccountRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();

        if (isset($data['email']) && $data['email'] !== $user->email) {
            $user->email = $data['email'];
            $user->email_verified_at = null;
            $user->save();
            $user->sendEmailVerificationNotification();

            return redirect()->route('settings', ['section' => 'account'])
                ->with('status', 'Email updated. Please verify your new email address.');
        }

        if (isset($data['password'])) {
            $user->update(['password' => $data['password']]);

            return redirect()->route('settings', ['section' => 'account'])
                ->with('status', 'Password updated successfully.');
        }

        return redirect()->route('settings', ['section' => 'account']);
    }
}
