<x-mail::message>
# Notification Digest

Hi {{ $user->name }},

You have **{{ $items->count() }}** {{ class_basename($notificationType) }} notifications:

<x-mail::table>
| # | Message | Time |
|---|---------|------|
@foreach ($items as $index => $item)
| {{ $index + 1 }} | {{ $item->data['message'] ?? 'Notification' }} | {{ $item->created_at->format('M j, g:i A') }} |
@endforeach
</x-mail::table>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
