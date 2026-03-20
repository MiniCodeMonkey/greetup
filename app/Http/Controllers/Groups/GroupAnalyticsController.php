<?php

namespace App\Http\Controllers\Groups;

use App\Enums\AttendanceResult;
use App\Enums\EventStatus;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Feedback;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\Rsvp;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class GroupAnalyticsController extends Controller
{
    /**
     * Show the group analytics page.
     */
    public function index(Group $group): View
    {
        $eventIds = $group->events()->pluck('id');

        $memberGrowth = $this->getMemberGrowth($group);
        $eventCountOverTime = $this->getEventCountOverTime($group);
        $averageAttendanceRate = $this->getAverageAttendanceRate($eventIds);
        $mostActiveMembers = $this->getMostActiveMembers($eventIds);
        $averageEventRating = $this->getAverageEventRating($eventIds);

        return view('groups.manage.analytics', [
            'group' => $group,
            'memberGrowth' => $memberGrowth,
            'eventCountOverTime' => $eventCountOverTime,
            'averageAttendanceRate' => $averageAttendanceRate,
            'mostActiveMembers' => $mostActiveMembers,
            'averageEventRating' => $averageEventRating,
        ]);
    }

    /**
     * Get new members per month.
     *
     * @return Collection<int, object{period: string, count: int}>
     */
    private function getMemberGrowth(Group $group): Collection
    {
        return GroupMember::query()
            ->where('group_id', $group->id)
            ->whereNotNull('joined_at')
            ->select(
                DB::raw("DATE_FORMAT(joined_at, '%Y-%m') as period"),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('period')
            ->orderBy('period')
            ->get();
    }

    /**
     * Get event count per month.
     *
     * @return Collection<int, object{period: string, count: int}>
     */
    private function getEventCountOverTime(Group $group): Collection
    {
        return Event::query()
            ->where('group_id', $group->id)
            ->whereIn('status', [EventStatus::Published, EventStatus::Past])
            ->select(
                DB::raw("DATE_FORMAT(starts_at, '%Y-%m') as period"),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('period')
            ->orderBy('period')
            ->get();
    }

    /**
     * Get average attendance rate (attended vs no-show).
     *
     * @param  Collection<int, int>  $eventIds
     */
    private function getAverageAttendanceRate(Collection $eventIds): float
    {
        if ($eventIds->isEmpty()) {
            return 0;
        }

        $stats = Rsvp::query()
            ->whereIn('event_id', $eventIds)
            ->whereNotNull('attended')
            ->select(
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN attended = \''.AttendanceResult::Attended->value.'\' THEN 1 ELSE 0 END) as attended_count')
            )
            ->first();

        if (! $stats || $stats->total == 0) {
            return 0;
        }

        return round(($stats->attended_count / $stats->total) * 100, 1);
    }

    /**
     * Get most active members by attendance count.
     *
     * @param  Collection<int, int>  $eventIds
     * @return Collection<int, object{user_id: int, name: string, attendance_count: int}>
     */
    private function getMostActiveMembers(Collection $eventIds): Collection
    {
        if ($eventIds->isEmpty()) {
            return collect();
        }

        return Rsvp::query()
            ->whereIn('event_id', $eventIds)
            ->where('attended', AttendanceResult::Attended)
            ->join('users', 'rsvps.user_id', '=', 'users.id')
            ->select('users.id as user_id', 'users.name', DB::raw('COUNT(*) as attendance_count'))
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('attendance_count')
            ->limit(10)
            ->get();
    }

    /**
     * Get average event rating from feedback.
     *
     * @param  Collection<int, int>  $eventIds
     */
    private function getAverageEventRating(Collection $eventIds): float
    {
        if ($eventIds->isEmpty()) {
            return 0;
        }

        $avg = Feedback::query()
            ->whereIn('event_id', $eventIds)
            ->avg('rating');

        return round($avg ?? 0, 1);
    }
}
