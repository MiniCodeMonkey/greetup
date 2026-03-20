<?php

namespace App\Livewire;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class NotificationDropdown extends Component
{
    public bool $isOpen = false;

    public int $unreadCount = 0;

    public int $perPage = 10;

    public int $page = 1;

    public Collection $notifications;

    public bool $hasMore = false;

    public function mount(): void
    {
        $this->notifications = new Collection;
        $this->loadUnreadCount();
        $this->loadNotifications();
    }

    public function toggle(): void
    {
        $this->isOpen = ! $this->isOpen;

        if ($this->isOpen) {
            $this->page = 1;
            $this->loadNotifications();
        }
    }

    public function loadMore(): void
    {
        $this->page++;
        $user = Auth::user();
        $newNotifications = $user->notifications()
            ->skip(($this->page - 1) * $this->perPage)
            ->take($this->perPage)
            ->get();

        $this->notifications = $this->notifications->merge($newNotifications);
        $this->hasMore = $newNotifications->count() === $this->perPage;
    }

    public function markAsRead(string $notificationId): ?string
    {
        $user = Auth::user();
        $notification = $user->notifications()->where('id', $notificationId)->first();

        if ($notification) {
            $notification->markAsRead();
            $this->loadUnreadCount();
            $this->loadNotifications();

            return $notification->data['link'] ?? null;
        }

        return null;
    }

    public function markAllAsRead(): void
    {
        Auth::user()->unreadNotifications->markAsRead();
        $this->unreadCount = 0;
        $this->loadNotifications();
    }

    #[On('echo-private:user.{userId}.notifications,NotificationSent')]
    public function onNotificationReceived(): void
    {
        $this->loadUnreadCount();

        if ($this->isOpen) {
            $this->page = 1;
            $this->loadNotifications();
        }
    }

    public function getUserIdProperty(): int
    {
        return Auth::id();
    }

    public function render(): View
    {
        return view('livewire.notification-dropdown');
    }

    private function loadUnreadCount(): void
    {
        $this->unreadCount = Auth::user()->unreadNotifications()->count();
    }

    private function loadNotifications(): void
    {
        $user = Auth::user();
        $this->notifications = $user->notifications()
            ->take($this->page * $this->perPage)
            ->get();

        $total = $user->notifications()->count();
        $this->hasMore = $total > ($this->page * $this->perPage);
    }
}
