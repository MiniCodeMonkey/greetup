<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateAccountRequest;
use App\Http\Requests\Settings\UpdateNotificationPreferencesRequest;
use App\Http\Requests\Settings\UpdatePrivacyRequest;
use App\Http\Requests\Settings\UpdateProfileRequest;
use App\Models\NotificationPreference;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Tags\Tag;

class SettingsController extends Controller
{
    /**
     * All configurable notification types with their labels, categories, and default channels.
     *
     * @var array<string, array{label: string, category: string, channels: list<string>}>
     */
    public const NOTIFICATION_TYPES = [
        'App\Notifications\WelcomeToGroup' => ['label' => 'Welcome to Group', 'category' => 'Groups', 'channels' => ['web', 'email']],
        'App\Notifications\JoinRequestReceived' => ['label' => 'Join Request Received', 'category' => 'Groups', 'channels' => ['web', 'email']],
        'App\Notifications\JoinRequestApproved' => ['label' => 'Join Request Approved', 'category' => 'Groups', 'channels' => ['web', 'email']],
        'App\Notifications\JoinRequestDenied' => ['label' => 'Join Request Denied', 'category' => 'Groups', 'channels' => ['web', 'email']],
        'App\Notifications\MemberRemoved' => ['label' => 'Member Removed', 'category' => 'Groups', 'channels' => ['web', 'email']],
        'App\Notifications\MemberBanned' => ['label' => 'Member Banned', 'category' => 'Groups', 'channels' => ['web', 'email']],
        'App\Notifications\RoleChanged' => ['label' => 'Role Changed', 'category' => 'Groups', 'channels' => ['web', 'email']],
        'App\Notifications\OwnershipTransferred' => ['label' => 'Ownership Transferred', 'category' => 'Groups', 'channels' => ['web', 'email']],
        'App\Notifications\GroupDeleted' => ['label' => 'Group Deleted', 'category' => 'Groups', 'channels' => ['email']],
        'App\Notifications\NewEvent' => ['label' => 'New Event', 'category' => 'Events', 'channels' => ['web', 'email']],
        'App\Notifications\EventUpdated' => ['label' => 'Event Updated', 'category' => 'Events', 'channels' => ['web', 'email']],
        'App\Notifications\EventCancelled' => ['label' => 'Event Cancelled', 'category' => 'Events', 'channels' => ['web', 'email']],
        'App\Notifications\RsvpConfirmation' => ['label' => 'RSVP Confirmation', 'category' => 'Events', 'channels' => ['web', 'email']],
        'App\Notifications\PromotedFromWaitlist' => ['label' => 'Promoted from Waitlist', 'category' => 'Events', 'channels' => ['web', 'email']],
        'App\Notifications\NewEventComment' => ['label' => 'New Event Comment', 'category' => 'Comments', 'channels' => ['web']],
        'App\Notifications\EventCommentReply' => ['label' => 'Event Comment Reply', 'category' => 'Comments', 'channels' => ['web', 'email']],
        'App\Notifications\EventCommentLiked' => ['label' => 'Event Comment Liked', 'category' => 'Comments', 'channels' => ['web']],
        'App\Notifications\NewEventFeedback' => ['label' => 'New Event Feedback', 'category' => 'Comments', 'channels' => ['web']],
        'App\Notifications\NewDiscussion' => ['label' => 'New Discussion', 'category' => 'Discussions', 'channels' => ['web']],
        'App\Notifications\NewDiscussionReply' => ['label' => 'New Discussion Reply', 'category' => 'Discussions', 'channels' => ['web', 'email']],
        'App\Notifications\NewDirectMessage' => ['label' => 'New Direct Message', 'category' => 'Messages', 'channels' => ['web', 'email']],
        'App\Notifications\ReportReceived' => ['label' => 'Report Received', 'category' => 'Admin', 'channels' => ['web', 'email']],
    ];

    /**
     * Display the settings page.
     */
    public function index(Request $request): View
    {
        $section = $request->query('section', 'profile');
        $user = $request->user();

        $interestTags = Tag::getWithType('interest')->pluck('name')->sort()->values();

        $notificationPreferences = [];
        if ($section === 'notifications') {
            $existing = $user->notificationPreferences()
                ->get()
                ->groupBy('type')
                ->map(fn ($prefs) => $prefs->keyBy(fn ($p) => $p->channel->value));

            foreach (self::NOTIFICATION_TYPES as $type => $config) {
                $notificationPreferences[$type] = [
                    'label' => $config['label'],
                    'category' => $config['category'],
                    'channels' => $config['channels'],
                    'email' => in_array('email', $config['channels'])
                        ? ($existing->has($type) && $existing[$type]->has('email')
                            ? $existing[$type]['email']->enabled
                            : true)
                        : null,
                    'web' => in_array('web', $config['channels'])
                        ? ($existing->has($type) && $existing[$type]->has('web')
                            ? $existing[$type]['web']->enabled
                            : true)
                        : null,
                ];
            }
        }

        return view('settings.index', [
            'section' => $section,
            'user' => $user,
            'interestTags' => $interestTags,
            'notificationPreferences' => $notificationPreferences,
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
     * Update the user's notification preferences.
     */
    public function updateNotifications(UpdateNotificationPreferencesRequest $request): RedirectResponse
    {
        $user = $request->user();
        $preferences = $request->validated()['preferences'];

        foreach ($preferences as $type => $channels) {
            if (! array_key_exists($type, self::NOTIFICATION_TYPES)) {
                continue;
            }

            $config = self::NOTIFICATION_TYPES[$type];

            foreach (['email', 'web'] as $channel) {
                if (! in_array($channel, $config['channels'])) {
                    continue;
                }

                $enabled = (bool) ($channels[$channel] ?? true);

                NotificationPreference::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'type' => $type,
                        'channel' => $channel,
                    ],
                    [
                        'enabled' => $enabled,
                    ],
                );
            }
        }

        return redirect()->route('settings', ['section' => 'notifications'])
            ->with('status', 'Notification preferences updated successfully.');
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
