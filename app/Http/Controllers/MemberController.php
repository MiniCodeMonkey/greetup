<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class MemberController extends Controller
{
    public function show(User $user): View
    {
        abort_unless(Gate::allows('view', $user), Response::HTTP_FORBIDDEN);

        $viewer = auth()->user();

        $commonGroups = collect();
        if ($viewer && $viewer->id !== $user->id) {
            $commonGroups = $user->groups()
                ->whereIn('groups.id', $viewer->groups()->select('groups.id'))
                ->get();
        }

        $interests = $user->tagsWithType('interest');

        $siteName = Setting::get('site_name', config('app.name', 'Greetup'));
        $seoTitle = $user->name.' — '.$siteName;
        $seoDescription = $user->bio
            ? str()->limit(strip_tags($user->bio), 160)
            : $user->name.' is a member of '.$siteName.'.';

        $seoImage = $user->getFirstMediaUrl('avatar', 'profile-page') ?: null;

        return view('members.show', [
            'member' => $user,
            'commonGroups' => $commonGroups,
            'interests' => $interests,
            'seoTitle' => $seoTitle,
            'seoDescription' => $seoDescription,
            'seoImage' => $seoImage,
        ]);
    }
}
