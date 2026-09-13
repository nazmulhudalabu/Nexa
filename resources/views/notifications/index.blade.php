@extends('layouts.app', ['title' => 'Notifications'])

@section('content')
<section class="feed-heading"><div><p class="eyebrow">Stay in the loop</p><h1>Notifications.</h1><p class="lede">Friend requests, reactions, conversations, and other activity around your Nexa account.</p></div>@if ($notifications->contains(fn ($notification) => is_null($notification->read_at)))<form method="POST" action="{{ route('notifications.read-all') }}">@csrf<button class="button button-quiet" type="submit">Mark all read</button></form>@endif</section>
<section class="notification-list">@forelse ($notifications as $notification)<a class="notification-row {{ $notification->read_at ? '' : 'notification-unread' }}" href="{{ route('notifications.read', $notification) }}"><img src="{{ $notification->data['actor_photo'] ?? asset('images/avatar.png') }}" alt=""><span><strong>{{ $notification->data['actor_name'] ?? 'Nexa' }}</strong> {{ $notification->data['message'] ?? 'You have new activity.' }}<small>{{ $notification->created_at->diffForHumans() }}</small></span>@if (!$notification->read_at)<b aria-label="Unread">•</b>@endif</a>@empty<div class="empty-state"><span class="empty-icon">○</span><h2>You're all caught up.</h2><p>New activity will appear here.</p></div>@endforelse</section>
<div class="pagination">{{ $notifications->links() }}</div>
@endsection
