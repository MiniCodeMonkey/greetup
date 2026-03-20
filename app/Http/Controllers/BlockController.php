<?php

namespace App\Http\Controllers;

use App\Models\Block;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

class BlockController extends Controller
{
    public function store(User $user): RedirectResponse
    {
        $blocker = request()->user();

        if ($blocker->id === $user->id) {
            return back()->withErrors(['block' => 'You cannot block yourself.']);
        }

        Block::firstOrCreate([
            'blocker_id' => $blocker->id,
            'blocked_id' => $user->id,
        ], [
            'created_at' => now(),
        ]);

        return back()->with('status', 'User blocked.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $blocker = request()->user();

        Block::where('blocker_id', $blocker->id)
            ->where('blocked_id', $user->id)
            ->delete();

        return back()->with('status', 'User unblocked.');
    }
}
