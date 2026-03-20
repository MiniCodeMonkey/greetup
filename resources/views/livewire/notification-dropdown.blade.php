<div class="relative" x-data="{ open: $wire.entangle('isOpen') }">
    {{-- Bell button --}}
    <button
        type="button"
        class="relative rounded-full p-2 text-neutral-500 hover:text-neutral-700"
        aria-label="Notifications"
        wire:click="toggle"
    >
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
        </svg>
        @if ($unreadCount > 0)
            <span class="absolute -right-0.5 -top-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-coral-500 px-1 text-[10px] font-medium text-white" data-testid="unread-count">{{ $unreadCount > 99 ? '99+' : $unreadCount }}</span>
        @endif
    </button>

    {{-- Dropdown --}}
    <div
        x-show="open"
        x-cloak
        @click.outside="open = false"
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="absolute right-0 top-full z-50 mt-2 w-80 rounded-lg bg-white shadow-lg"
        style="border: 0.5px solid var(--color-neutral-200)"
    >
        {{-- Header --}}
        <div class="flex items-center justify-between px-4 py-3" style="border-bottom: 0.5px solid var(--color-neutral-200)">
            <h3 class="text-sm font-medium text-neutral-900">Notifications</h3>
            @if ($unreadCount > 0)
                <button
                    type="button"
                    wire:click="markAllAsRead"
                    class="text-xs font-medium text-green-500 hover:text-green-700"
                    data-testid="mark-all-read"
                >
                    Mark all as read
                </button>
            @endif
        </div>

        {{-- Notification list --}}
        <div class="max-h-80 overflow-y-auto" data-testid="notification-list">
            @forelse ($notifications as $notification)
                <a
                    href="{{ $notification->data['link'] ?? '#' }}"
                    wire:click.prevent="markAsRead('{{ $notification->id }}')"
                    x-on:click="if ($event.target.closest('a').href !== '#') { setTimeout(() => window.location = '{{ $notification->data['link'] ?? '#' }}', 100) }"
                    class="block px-4 py-3 hover:bg-neutral-50 {{ $notification->read_at ? '' : 'bg-green-50' }}"
                    style="border-bottom: 0.5px solid var(--color-neutral-200)"
                    data-testid="notification-item"
                >
                    <div class="flex items-start gap-3">
                        {{-- Icon --}}
                        <div class="mt-0.5 shrink-0">
                            @php
                                $type = class_basename($notification->type);
                                $icon = match(true) {
                                    str_contains($type, 'Event') => 'calendar',
                                    str_contains($type, 'Group'), str_contains($type, 'Member'), str_contains($type, 'JoinRequest'), str_contains($type, 'Role'), str_contains($type, 'Ownership'), str_contains($type, 'Welcome') => 'users',
                                    str_contains($type, 'Discussion'), str_contains($type, 'Comment') => 'chat',
                                    str_contains($type, 'Message') => 'envelope',
                                    str_contains($type, 'Rsvp'), str_contains($type, 'Waitlist') => 'ticket',
                                    str_contains($type, 'Report'), str_contains($type, 'Suspended') => 'shield',
                                    default => 'bell',
                                };
                            @endphp
                            @if ($icon === 'calendar')
                                <svg class="h-4 w-4 text-violet-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 9v9.75" /></svg>
                            @elseif ($icon === 'users')
                                <svg class="h-4 w-4 text-green-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" /></svg>
                            @elseif ($icon === 'chat')
                                <svg class="h-4 w-4 text-blue-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.847 2.1-1.98 2.193-.34.027-.68.052-1.02.072v3.091l-3-3c-1.354 0-2.694-.055-4.02-.163a2.115 2.115 0 0 1-.825-.242m9.345-8.334a2.126 2.126 0 0 0-.476-.095 48.64 48.64 0 0 0-8.048 0c-1.131.094-1.976 1.057-1.976 2.192v4.286c0 .837.46 1.58 1.155 1.951m9.345-8.334V6.637c0-1.621-1.152-3.026-2.76-3.235A48.455 48.455 0 0 0 11.25 3c-2.115 0-4.198.137-6.24.402-1.608.209-2.76 1.614-2.76 3.235v6.226c0 1.621 1.152 3.026 2.76 3.235.577.075 1.157.14 1.74.194V21l4.155-4.155" /></svg>
                            @elseif ($icon === 'envelope')
                                <svg class="h-4 w-4 text-coral-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" /></svg>
                            @elseif ($icon === 'ticket')
                                <svg class="h-4 w-4 text-gold-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 6v.75m0 3v.75m0 3v.75m0 3V18m-9-5.25h5.25M7.5 15h3M3.375 5.25c-.621 0-1.125.504-1.125 1.125v3.026a2.999 2.999 0 0 1 0 5.198v3.026c0 .621.504 1.125 1.125 1.125h17.25c.621 0 1.125-.504 1.125-1.125v-3.026a2.999 2.999 0 0 1 0-5.198V6.375c0-.621-.504-1.125-1.125-1.125H3.375Z" /></svg>
                            @elseif ($icon === 'shield')
                                <svg class="h-4 w-4 text-red-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0-10.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.75c0 5.592 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.25-8.25-3.286Zm0 13.036h.008v.008H12v-.008Z" /></svg>
                            @else
                                <svg class="h-4 w-4 text-neutral-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" /></svg>
                            @endif
                        </div>

                        {{-- Content --}}
                        <div class="min-w-0 flex-1">
                            <p class="text-sm text-neutral-700">{{ $notification->data['message'] ?? 'New notification' }}</p>
                            <p class="mt-1 text-xs text-neutral-400">{{ $notification->created_at->diffForHumans() }}</p>
                        </div>

                        {{-- Unread dot --}}
                        @unless ($notification->read_at)
                            <div class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-green-500"></div>
                        @endunless
                    </div>
                </a>
            @empty
                <div class="px-4 py-6 text-center">
                    <p class="text-sm text-neutral-500">No notifications yet</p>
                </div>
            @endforelse
        </div>

        {{-- Load more --}}
        @if ($hasMore)
            <div class="px-4 py-3 text-center" style="border-top: 0.5px solid var(--color-neutral-200)">
                <button
                    type="button"
                    wire:click="loadMore"
                    class="text-sm font-medium text-green-500 hover:text-green-700"
                    data-testid="load-more"
                >
                    Load more
                </button>
            </div>
        @endif
    </div>
</div>
