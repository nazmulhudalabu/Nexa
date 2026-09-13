@extends('layouts.app', ['title' => 'Dashboard'])
@section('content')
<section class="intro-row"><div><p class="eyebrow">Your Nexa profile</p><h1>See what is resonating.</h1><p class="lede">A snapshot of your posts and the conversations you are part of.</p></div></section>
<div class="stats-grid"><div><strong>{{ $posts->count() }}</strong><span>Notes</span></div><div><strong>{{ $likes }}</strong><span>Likes</span></div><div><strong>{{ $comments }}</strong><span>Comments</span></div></div>
<section class="dashboard-list"><div class="section-heading"><h2>Your posts</h2><a class="read-link" href="{{ route('posts.create') }}">Share something →</a></div>
@forelse ($posts as $post)<a class="dashboard-post" href="{{ route('posts.show', $post) }}"><span><strong>{{ $post->name }}</strong><small>{{ $post->created_at->format('M j, Y') }}</small></span><span>{{ $post->likes_count }} likes · {{ $post->comments_count }} comments →</span></a>@empty<div class="empty-state"><h2>Your first note is waiting.</h2><a class="button button-accent" href="{{ route('posts.create') }}">Write a note</a></div>@endforelse</section>
@endsection