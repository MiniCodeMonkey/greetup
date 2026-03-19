<?php

namespace App\Http\Middleware;

use App\Enums\GroupRole;
use App\Models\Group;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureGroupRole
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $minimumRole): Response
    {
        $group = $this->resolveGroup($request);

        if (! $group) {
            abort(404);
        }

        $membership = $request->user()
            ->groups()
            ->where('groups.id', $group->id)
            ->first();

        if (! $membership) {
            abort(403, 'You are not a member of this group.');
        }

        $pivotRole = $membership->pivot->role;
        $userRole = $pivotRole instanceof GroupRole ? $pivotRole : GroupRole::from($pivotRole);
        $requiredRole = GroupRole::from($minimumRole);

        if (! $userRole->isAtLeast($requiredRole)) {
            abort(403, 'You do not have the required role for this action.');
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
