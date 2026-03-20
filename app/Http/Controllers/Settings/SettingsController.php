<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateAccountRequest;
use App\Http\Requests\Settings\UpdatePrivacyRequest;
use App\Http\Requests\Settings\UpdateProfileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Tags\Tag;

class SettingsController extends Controller
{
    /**
     * Display the settings page.
     */
    public function index(Request $request): View
    {
        $section = $request->query('section', 'profile');
        $user = $request->user();

        $interestTags = Tag::getWithType('interest')->pluck('name')->sort()->values();

        return view('settings.index', [
            'section' => $section,
            'user' => $user,
            'interestTags' => $interestTags,
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function updateProfile(UpdateProfileRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        $user->update([
            'name' => $validated['name'],
            'bio' => $validated['bio'] ?? null,
            'location' => $validated['location'] ?? null,
            'timezone' => $validated['timezone'] ?? $user->timezone,
            'looking_for' => $validated['looking_for'] ?? [],
        ]);

        if ($request->hasFile('avatar')) {
            $user->addMediaFromRequest('avatar')
                ->toMediaCollection('avatar');
        }

        $user->syncTagsWithType($validated['interests'] ?? [], 'interest');

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

    /**
     * Update the user's privacy settings.
     */
    public function updatePrivacy(UpdatePrivacyRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        $user->update([
            'profile_visibility' => $validated['profile_visibility'],
        ]);

        return redirect()->route('settings', ['section' => 'privacy'])
            ->with('status', 'Privacy settings updated successfully.');
    }
}
