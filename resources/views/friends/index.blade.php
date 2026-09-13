@extends('layouts.app', ['title' => 'Friends'])

@section('content')
<section class="feed-heading"><div><p class="eyebrow">Your circle</p><h1>Friends.</h1><p class="lede">Keep up with your people, discover new connections, and never miss a birthday.</p></div></section>

@if ($birthdays->isNotEmpty())
<section class="birthday-banner"><strong>Birthday today</strong><span>{{ $birthdays->pluck('name')->join(', ') }}{{ $birthdays->count() === 1 ? ' has' : ' have' }} a birthday. Send a little warmth their way.</span></section>
@endif

<section class="friend-section">
    <div class="section-heading"><h2>Your friends</h2><span class="post-count">{{ $friends->count() }}</span></div>
    <form class="search-bar friends-search" method="GET" action="{{ route('friends.index') }}"><label class="sr-only" for="friend-search">Search friends by name</label><span class="search-icon" aria-hidden="true">⌕</span><input id="friend-search" name="search" value="{{ $search }}" placeholder="Search your friends..."><button class="button button-accent" type="submit">Search</button></form>
    @if ($friends->isEmpty())<div class="empty-state"><h2>No friends here yet.</h2><p>Start with a suggestion below or find someone by name.</p><a class="button button-accent" href="{{ route('people.index') }}">Find people</a></div>@else
    <div class="people-grid">@foreach ($friends as $friend)<a class="person-card" href="{{ route('people.show', $friend) }}"><img src="{{ $friend->profile_photo_url }}" alt=""><span><strong>{{ $friend->name }}</strong><small>{{ $friend->profession ?: 'Friend' }}</small></span><b>→</b></a>@endforeach</div>
    @endif
</section>

@if ($requests->isNotEmpty() || $sent->isNotEmpty())
<section class="friend-section"><div class="section-heading"><h2>Friend requests</h2></div><div class="request-list">
    @foreach ($requests as $request)<div class="request-row"><a href="{{ route('people.show', $request->sender) }}"><img src="{{ $request->sender->profile_photo_url }}" alt=""><strong>{{ $request->sender->name }}</strong></a><div class="profile-actions"><form method="POST" action="{{ route('friends.accept', $request) }}">@csrf<button class="button button-accent" type="submit">Accept</button></form><form method="POST" action="{{ route('friends.reject', $request) }}">@csrf<button class="button button-quiet" type="submit">Reject</button></form></div></div>@endforeach
    @foreach ($sent as $request)@php($recipient = $request->user_one_id === auth()->id() ? $request->userTwo : $request->userOne)<div class="request-row"><a href="{{ route('people.show', $recipient) }}"><img src="{{ $recipient->profile_photo_url }}" alt=""><strong>{{ $recipient->name }}</strong></a><form method="POST" action="{{ route('friends.cancel', $request) }}">@csrf<button class="button button-quiet" type="submit">Cancel request</button></form></div>@endforeach
</div></section>
@endif

<section class="friend-section"><div class="section-heading"><h2>People you may know</h2></div><div class="people-grid">@forelse ($suggestions as $person)<div class="person-card"><a class="person-card-link" href="{{ route('people.show', $person) }}"><img src="{{ $person->profile_photo_url }}" alt=""><span><strong>{{ $person->name }}</strong><small>{{ $person->profession ?: 'Nexa member' }}</small></span></a><form method="POST" action="{{ route('friends.send', $person) }}">@csrf<button class="button button-accent" type="submit">Add</button></form></div>@empty<div class="empty-state"><p>No new suggestions right now.</p></div>@endforelse</div></section>
@endsection
