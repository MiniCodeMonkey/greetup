<?php

use App\Models\Comment;
use App\Models\Event;
use App\Models\Feedback;
use App\Models\Group;
use App\Models\Rsvp;
use App\Models\User;
use App\Notifications\EventCancelled;
use App\Notifications\EventCommentLiked;
use App\Notifications\EventCommentReply;
use App\Notifications\EventUpdated;
use App\Notifications\NewEvent;
use App\Notifications\NewEventComment;
use App\Notifications\NewEventFeedback;
use App\Notifications\PromotedFromWaitlist;
use App\Notifications\RsvpConfirmation;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
    $this->service = new NotificationService;
});

it('dispatches NewEvent notification via web and email', function (): void {
    NotificationFacade::fake();
    $user = User::factory()->create();
    $group = Group::factory()->create();
    $event = Event::factory()->published()->create(['group_id' => $group->id]);

    $notification = new NewEvent($event, $group);
    $this->service->dispatch($user, $notification, ['group_id' => $group->id]);

    NotificationFacade::assertSentTo($user, NewEvent::class, function (NewEvent $n) use ($event, $group): bool {
        $channels = $n->via($user = new stdClass);
        $array = $n->toArray(new stdClass);

        return in_array('mail', $channels)
            && in_array('database', $channels)
            && $array['event_id'] === $event->id
            && $array['group_id'] === $group->id
            && isset($array['link']);
    });
});

it('dispatches EventUpdated notification via web and email', function (): void {
    NotificationFacade::fake();
    $user = User::factory()->create();
    $group = Group::factory()->create();
    $event = Event::factory()->published()->create(['group_id' => $group->id]);

    $notification = new EventUpdated($event, $group);
    $this->service->dispatch($user, $notification, ['group_id' => $group->id]);

    NotificationFacade::assertSentTo($user, EventUpdated::class, function (EventUpdated $n): bool {
        $channels = $n->via(new stdClass);

        return in_array('mail', $channels) && in_array('database', $channels);
    });
});

it('dispatches EventCancelled notification via web and email', function (): void {
    NotificationFacade::fake();
    $user = User::factory()->create();
    $group = Group::factory()->create();
    $event = Event::factory()->published()->create(['group_id' => $group->id]);

    $notification = new EventCancelled($event, $group);
    $this->service->dispatch($user, $notification, ['group_id' => $group->id]);

    NotificationFacade::assertSentTo($user, EventCancelled::class, function (EventCancelled $n): bool {
        $channels = $n->via(new stdClass);

        return in_array('mail', $channels) && in_array('database', $channels);
    });
});

it('dispatches RsvpConfirmation notification via web and email', function (): void {
    NotificationFacade::fake();
    $user = User::factory()->create();
    $group = Group::factory()->create();
    $event = Event::factory()->published()->create(['group_id' => $group->id]);
    $rsvp = Rsvp::factory()->going()->create([
        'event_id' => $event->id,
        'user_id' => $user->id,
    ]);

    $notification = new RsvpConfirmation($event, $rsvp);
    $this->service->dispatch($user, $notification);

    NotificationFacade::assertSentTo($user, RsvpConfirmation::class, function (RsvpConfirmation $n): bool {
        $channels = $n->via(new stdClass);

        return in_array('mail', $channels) && in_array('database', $channels);
    });
});

it('dispatches PromotedFromWaitlist notification via web and email', function (): void {
    NotificationFacade::fake();
    $user = User::factory()->create();
    $group = Group::factory()->create();
    $event = Event::factory()->published()->create(['group_id' => $group->id]);
    $rsvp = Rsvp::factory()->going()->create([
        'event_id' => $event->id,
        'user_id' => $user->id,
    ]);

    $notification = new PromotedFromWaitlist($event, $rsvp);
    $this->service->dispatch($user, $notification);

    NotificationFacade::assertSentTo($user, PromotedFromWaitlist::class, function (PromotedFromWaitlist $n): bool {
        $channels = $n->via(new stdClass);

        return in_array('mail', $channels) && in_array('database', $channels);
    });
});

it('dispatches NewEventComment notification via web only', function (): void {
    NotificationFacade::fake();
    $user = User::factory()->create();
    $group = Group::factory()->create();
    $event = Event::factory()->published()->create(['group_id' => $group->id]);
    $commenter = User::factory()->create();
    $comment = Comment::factory()->create([
        'event_id' => $event->id,
        'user_id' => $commenter->id,
    ]);

    $notification = new NewEventComment($comment, $event);
    $this->service->dispatch($user, $notification, ['group_id' => $group->id, 'sender_id' => $commenter->id]);

    NotificationFacade::assertSentTo($user, NewEventComment::class, function (NewEventComment $n): bool {
        $channels = $n->via(new stdClass);

        return $channels === ['database'];
    });
});

it('dispatches EventCommentReply notification via web and email', function (): void {
    NotificationFacade::fake();
    $user = User::factory()->create();
    $group = Group::factory()->create();
    $event = Event::factory()->published()->create(['group_id' => $group->id]);
    $replier = User::factory()->create();
    $reply = Comment::factory()->create([
        'event_id' => $event->id,
        'user_id' => $replier->id,
    ]);

    $notification = new EventCommentReply($reply, $event);
    $this->service->dispatch($user, $notification, ['group_id' => $group->id, 'sender_id' => $replier->id]);

    NotificationFacade::assertSentTo($user, EventCommentReply::class, function (EventCommentReply $n): bool {
        $channels = $n->via(new stdClass);

        return in_array('mail', $channels) && in_array('database', $channels);
    });
});

it('dispatches EventCommentLiked notification via web only', function (): void {
    NotificationFacade::fake();
    $user = User::factory()->create();
    $group = Group::factory()->create();
    $event = Event::factory()->published()->create(['group_id' => $group->id]);
    $liker = User::factory()->create();
    $comment = Comment::factory()->create([
        'event_id' => $event->id,
        'user_id' => $user->id,
    ]);

    $notification = new EventCommentLiked($comment, $liker);
    $this->service->dispatch($user, $notification, ['group_id' => $group->id, 'sender_id' => $liker->id]);

    NotificationFacade::assertSentTo($user, EventCommentLiked::class, function (EventCommentLiked $n): bool {
        $channels = $n->via(new stdClass);

        return $channels === ['database'];
    });
});

it('dispatches NewEventFeedback notification via web only', function (): void {
    NotificationFacade::fake();
    $user = User::factory()->create();
    $group = Group::factory()->create();
    $event = Event::factory()->published()->create(['group_id' => $group->id]);
    $feedbackUser = User::factory()->create();
    $feedback = Feedback::factory()->create([
        'event_id' => $event->id,
        'user_id' => $feedbackUser->id,
    ]);

    $notification = new NewEventFeedback($feedback, $event);
    $this->service->dispatch($user, $notification, ['group_id' => $group->id, 'sender_id' => $feedbackUser->id]);

    NotificationFacade::assertSentTo($user, NewEventFeedback::class, function (NewEventFeedback $n): bool {
        $channels = $n->via(new stdClass);

        return $channels === ['database'];
    });
});

it('includes correct link in toArray for event notifications', function (): void {
    $group = Group::factory()->create();
    $event = Event::factory()->published()->create(['group_id' => $group->id]);

    $notification = new NewEvent($event, $group);
    $array = $notification->toArray(new stdClass);

    expect($array['link'])->toBe("/groups/{$group->slug}/events/{$event->slug}");
});

it('includes correct mail content for event notifications', function (): void {
    $group = Group::factory()->create();
    $event = Event::factory()->published()->create(['group_id' => $group->id]);

    $notification = new NewEvent($event, $group);
    $mail = $notification->toMail(new stdClass);

    expect($mail->subject)->toBe("New event: {$event->name}");
    expect($mail->actionUrl)->toContain("/groups/{$group->slug}/events/{$event->slug}");
});
