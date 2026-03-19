<?php

namespace App\Http\Middleware;

use App\Models\Group;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureGroupMember
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $group = $this->resolveGroup($request);

        if (! $group) {
            abort(404);
        }

        $isMember = $request->user()
            ->groups()
            ->where('groups.id', $group->id)
            ->exists();

        if (! $isMember) {
            abort(403, 'You are not a member of this group.');
        }

        return $next($request);
    }

    /**
     * Resolve the group from the route parameter.
     */
    protected function resolveGroup(Request $request): ?Group
    {
        $group = $request->route('group');

        if ($group instanceof Group) {
            return $group;
        }

        if ($group) {
            return Group::find($group);
        }

        return null;
    }
}
