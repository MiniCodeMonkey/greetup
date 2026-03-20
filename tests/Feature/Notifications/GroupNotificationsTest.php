<?php

use App\Enums\GroupRole;
use App\Models\Discussion;
use App\Models\DiscussionReply;
use App\Models\Group;
use App\Models\GroupJoinRequest;
use App\Models\Report;
use App\Models\User;
use App\Notifications\AccountSuspended;
use App\Notifications\GroupDeleted;
use App\Notifications\JoinRequestApproved;
use App\Notifications\JoinRequestDenied;
use App\Notifications\JoinRequestReceived;
use App\Notifications\MemberBanned;
use App\Notifications\MemberRemoved;
use App\Notifications\NewDiscussion;
use App\Notifications\NewDiscussionReply;
use App\Notifications\OwnershipTransferred;
use App\Notifications\ReportReceived;
use App\Notifications\RoleChanged;
use App\Notifications\WelcomeToGroup;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
    $this->service = new NotificationService;
});

it('dispatches WelcomeToGroup notification via web and email', function (): void {
    NotificationFacade::fake();
    $user = User::factory()->create();
    $group = Group::factory()->create();

    $notification = new WelcomeToGroup($group);
    $this->service->dispatch($user, $notification, ['group_id' => $group->id]);

    NotificationFacade::assertSentTo($user, WelcomeToGroup::class, function (WelcomeToGroup $n): bool {
        $channels = $n->via(new stdClass);

        return in_array('mail', $channels) && in_array('database', $channels);
    });
});

it('dispatches JoinRequestReceived notification via web and email', function (): void {
    NotificationFacade::fake();
    $organizer = User::factory()->create();
    $requester = User::factory()->create();
    $group = Group::factory()->create();
    $joinRequest = GroupJoinRequest::create([
        'group_id' => $group->id,
        'user_id' => $requester->id,
    ]);

    $notification = new JoinRequestReceived($joinRequest);
    $this->service->dispatch($organizer, $notification, ['group_id' => $group->id, 'sender_id' => $requester->id]);

    NotificationFacade::assertSentTo($organizer, JoinRequestReceived::class, function (JoinRequestReceived $n): bool {
        $channels = $n->via(new stdClass);

        return in_array('mail', $channels) && in_array('database', $channels);
    });
});

it('dispatches JoinRequestApproved notification via web and email', function (): void {
    NotificationFacade::fake();
    $user = User::factory()->create();
    $group = Group::factory()->create();
    $joinRequest = GroupJoinRequest::create([
        'group_id' => $group->id,
        'user_id' => $user->id,
    ]);

    $notification = new JoinRequestApproved($joinRequest);
    $this->service->dispatch($user, $notification, ['group_id' => $group->id]);

    NotificationFacade::assertSentTo($user, JoinRequestApproved::class, function (JoinRequestApproved $n): bool {
        $channels = $n->via(new stdClass);

        return in_array('mail', $channels) && in_array('database', $channels);
    });
});

it('dispatches JoinRequestDenied notification via web and email', function (): void {
    NotificationFacade::fake();
    $user = User::factory()->create();
    $group = Group::factory()->create();
    $joinRequest = GroupJoinRequest::create([
        'group_id' => $group->id,
        'user_id' => $user->id,
    ]);

    $notification = new JoinRequestDenied($joinRequest);
    $this->service->dispatch($user, $notification, ['group_id' => $group->id]);

    NotificationFacade::assertSentTo($user, JoinRequestDenied::class, function (JoinRequestDenied $n): bool {
        $channels = $n->via(new stdClass);

        return in_array('mail', $channels) && in_array('database', $channels);
    });
});

it('dispatches MemberRemoved notification via web and email', function (): void {
    NotificationFacade::fake();
    $user = User::factory()->create();
    $group = Group::factory()->create();

    $notification = new MemberRemoved($group, 'Violated rules');
    $this->service->dispatch($user, $notification, ['group_id' => $group->id]);

    NotificationFacade::assertSentTo($user, MemberRemoved::class, function (MemberRemoved $n): bool {
        $channels = $n->via(new stdClass);

        return in_array('mail', $channels) && in_array('database', $channels);
    });
});

it('dispatches MemberBanned notification via web and email', function (): void {
    NotificationFacade::fake();
    $user = User::factory()->create();
    $group = Group::factory()->create();

    $notification = new MemberBanned($group, 'Spam');
    $this->service->dispatch($user, $notification, ['group_id' => $group->id]);

    NotificationFacade::assertSentTo($user, MemberBanned::class, function (MemberBanned $n): bool {
        $channels = $n->via(new stdClass);

        return in_array('mail', $channels) && in_array('database', $channels);
    });
});

it('dispatches RoleChanged notification via web and email', function (): void {
    NotificationFacade::fake();
    $user = User::factory()->create();
    $group = Group::factory()->create();

    $notification = new RoleChanged($group, GroupRole::Member, GroupRole::CoOrganizer);
    $this->service->dispatch($user, $notification, ['group_id' => $group->id]);

    NotificationFacade::assertSentTo($user, RoleChanged::class, function (RoleChanged $n): bool {
        $channels = $n->via(new stdClass);

        return in_array('mail', $channels) && in_array('database', $channels);
    });
});

it('dispatches OwnershipTransferred notification via web and email', function (): void {
    NotificationFacade::fake();
    $user = User::factory()->create();
    $group = Group::factory()->create();

    $notification = new OwnershipTransferred($group);
    $this->service->dispatch($user, $notification, ['group_id' => $group->id]);

    NotificationFacade::assertSentTo($user, OwnershipTransferred::class, function (OwnershipTransferred $n): bool {
        $channels = $n->via(new stdClass);

        return in_array('mail', $channels) && in_array('database', $channels);
    });
});

it('dispatches GroupDeleted notification via email only', function (): void {
    NotificationFacade::fake();
    $user = User::factory()->create();
    $group = Group::factory()->create();

    $notification = new GroupDeleted($group);
    $this->service->dispatch($user, $notification);

    NotificationFacade::assertSentTo($user, GroupDeleted::class, function (GroupDeleted $n): bool {
        $channels = $n->via(new stdClass);

        return $channels === ['mail'];
    });
});

it('dispatches NewDiscussion notification via web only', function (): void {
    NotificationFacade::fake();
    $user = User::factory()->create();
    $group = Group::factory()->create();
    $author = User::factory()->create();
    $discussion = Discussion::factory()->create([
        'group_id' => $group->id,
        'user_id' => $author->id,
    ]);

    $notification = new NewDiscussion($discussion, $group);
    $this->service->dispatch($user, $notification, ['group_id' => $group->id, 'sender_id' => $author->id]);

    NotificationFacade::assertSentTo($user, NewDiscussion::class, function (NewDiscussion $n): bool {
        $channels = $n->via(new stdClass);

        return $channels === ['database'];
    });
});

it('dispatches NewDiscussionReply notification via web and email', function (): void {
    NotificationFacade::fake();
    $user = User::factory()->create();
    $group = Group::factory()->create();
    $replier = User::factory()->create();
    $discussion = Discussion::factory()->create([
        'group_id' => $group->id,
        'user_id' => $user->id,
    ]);
    $reply = DiscussionReply::factory()->create([
        'discussion_id' => $discussion->id,
        'user_id' => $replier->id,
    ]);

    $notification = new NewDiscussionReply($reply, $discussion);
    $this->service->dispatch($user, $notification, ['group_id' => $group->id, 'sender_id' => $replier->id]);

    NotificationFacade::assertSentTo($user, NewDiscussionReply::class, function (NewDiscussionReply $n): bool {
        $channels = $n->via(new stdClass);

        return in_array('mail', $channels) && in_array('database', $channels);
    });
});

it('dispatches ReportReceived notification via web and email', function (): void {
    NotificationFacade::fake();
    $admin = User::factory()->create();
    $reporter = User::factory()->create();
    $report = Report::factory()->create([
        'reporter_id' => $reporter->id,
    ]);

    $notification = new ReportReceived($report);
    $this->service->dispatch($admin, $notification, ['sender_id' => $reporter->id]);

    NotificationFacade::assertSentTo($admin, ReportReceived::class, function (ReportReceived $n): bool {
        $channels = $n->via(new stdClass);

        return in_array('mail', $channels) && in_array('database', $channels);
    });
});

it('dispatches AccountSuspended notification via email only', function (): void {
    NotificationFacade::fake();
    $user = User::factory()->create();

    $notification = new AccountSuspended('Terms of service violation');
    // AccountSuspended bypasses suspension check since it's critical
    $user->notifyNow($notification);

    NotificationFacade::assertSentTo($user, AccountSuspended::class, function (AccountSuspended $n): bool {
        $channels = $n->via(new stdClass);

        return $channels === ['mail'];
    });
});

it('includes correct link in toArray for group notifications', function (): void {
    $group = Group::factory()->create();

    $notification = new WelcomeToGroup($group);
    $array = $notification->toArray(new stdClass);

    expect($array['link'])->toBe("/groups/{$group->slug}");
});

it('includes correct mail content for group notifications', function (): void {
    $group = Group::factory()->create();

    $notification = new OwnershipTransferred($group);
    $mail = $notification->toMail(new stdClass);

    expect($mail->subject)->toBe("You are now the organizer of {$group->name}");
    expect($mail->actionUrl)->toContain("/groups/{$group->slug}");
});
