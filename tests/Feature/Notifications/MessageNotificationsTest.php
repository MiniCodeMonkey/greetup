<?php

use App\Models\Block;
use App\Models\Conversation;
use App\Models\DirectMessage;
use App\Models\User;
use App\Notifications\NewDirectMessage;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
    $this->service = new NotificationService;
});

it('dispatches NewDirectMessage notification via web and email', function (): void {
    NotificationFacade::fake();
    $recipient = User::factory()->create();
    $sender = User::factory()->create();
    $conversation = Conversation::factory()->create();
    $message = DirectMessage::factory()->create([
        'conversation_id' => $conversation->id,
        'user_id' => $sender->id,
    ]);

    $notification = new NewDirectMessage($message);
    $this->service->dispatch($recipient, $notification, ['sender_id' => $sender->id]);

    NotificationFacade::assertSentTo($recipient, NewDirectMessage::class, function (NewDirectMessage $n): bool {
        $channels = $n->via(new stdClass);

        return in_array('mail', $channels) && in_array('database', $channels);
    });
});

it('includes correct link in toArray for direct message notifications', function (): void {
    $sender = User::factory()->create();
    $conversation = Conversation::factory()->create();
    $message = DirectMessage::factory()->create([
        'conversation_id' => $conversation->id,
        'user_id' => $sender->id,
    ]);

    $notification = new NewDirectMessage($message);
    $array = $notification->toArray(new stdClass);

    expect($array['link'])->toBe("/messages/{$conversation->id}");
    expect($array['conversation_id'])->toBe($conversation->id);
    expect($array['user_id'])->toBe($sender->id);
});

it('includes correct mail content for direct message notifications', function (): void {
    $sender = User::factory()->create();
    $conversation = Conversation::factory()->create();
    $message = DirectMessage::factory()->create([
        'conversation_id' => $conversation->id,
        'user_id' => $sender->id,
    ]);

    $notification = new NewDirectMessage($message);
    $mail = $notification->toMail(new stdClass);

    expect($mail->subject)->toBe("New message from {$sender->name}");
    expect($mail->actionUrl)->toContain("/messages/{$conversation->id}");
});

it('does not dispatch NewDirectMessage to blocked sender', function (): void {
    NotificationFacade::fake();
    $recipient = User::factory()->create();
    $sender = User::factory()->create();
    $conversation = Conversation::factory()->create();
    $message = DirectMessage::factory()->create([
        'conversation_id' => $conversation->id,
        'user_id' => $sender->id,
    ]);

    // Block the sender
    Block::create([
        'blocker_id' => $recipient->id,
        'blocked_id' => $sender->id,
    ]);

    $notification = new NewDirectMessage($message);
    $result = $this->service->dispatch($recipient, $notification, ['sender_id' => $sender->id]);

    expect($result)->toBeFalse();
    NotificationFacade::assertNothingSent();
});
