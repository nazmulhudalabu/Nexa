@extends('layouts.app', ['title' => 'Home'])

@section('content')
    <section class="feed-heading">
        <div><p class="eyebrow">Your social space</p><h1>What’s happening.</h1><p class="lede">See what your community is sharing, thinking, and enjoying today.</p></div>
        <div class="online-pill"><span></span> Community live</div>
    </section>

    @auth
        @if ($stories->isNotEmpty())
            <section class="stories-strip"><div class="section-heading"><h2>Stories</h2><span class="post-count">{{ $stories->count() }}</span></div><div class="story-row">@foreach ($stories as $story)<a class="story-item" href="{{ route('stories.show', $story) }}"><img src="{{ $story->user->profile_photo_url }}" alt=""><strong>{{ Str::limit($story->user->name, 14) }}</strong></a>@endforeach</div></section>
        @endif
    @endauth

    @auth
        <a class="composer" href="{{ route('posts.create') }}"><span class="composer-avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span><span>Share something with your people...</span><strong>＋</strong></a>
    @else
        <div class="join-banner"><div><strong>Make your corner of Nexa.</strong><span>Join the conversation and share what matters to you.</span></div><a class="button button-dark" href="{{ route('register') }}">Create account</a></div>
    @endauth

    <form class="search-bar" method="GET" action="{{ route('posts.index') }}">
        <label class="sr-only" for="search">Search posts</label>
        <span class="search-icon" aria-hidden="true">⌕</span><input id="search" name="search" value="{{ $search }}" placeholder="Search Nexa">
        <button class="button button-accent" type="submit">Find</button>
        @if ($search !== '')
            <a class="clear-link" href="{{ route('posts.index') }}">Clear</a>
        @endif
    </form>

    @if ($posts->isEmpty())
        <div class="empty-state">
            <span class="empty-icon">○</span>
            <h2>{{ $search ? 'No posts matched that search.' : 'Your feed is waiting.' }}</h2>
            <p>{{ $search ? 'Try a different word or clear the filter.' : 'Be the first person to share something with the community.' }}</p>
            <a class="button button-accent" href="{{ $search ? route('posts.index') : route('posts.create') }}">{{ $search ? 'View all posts' : 'Share a post' }}</a>
        </div>
    @else
        <div class="post-grid">
            @foreach ($posts as $post)
                <article class="post-card social-card">
                    <a class="post-image" href="{{ route('posts.show', $post) }}">
                        @if ($post->image)<img src="{{ asset('storage/' . $post->image) }}" alt="">@elseif ($post->media->first())<img src="{{ $post->media->first()->url }}" alt="">@endif
                    </a>
                    <div class="post-card-body">
                        <div class="post-author"><a href="{{ $post->user ? route('people.show', $post->user) : '#' }}" class="author-avatar">{{ strtoupper(substr($post->user?->name ?? 'N', 0, 1)) }}</a><div><a href="{{ $post->user ? route('people.show', $post->user) : '#' }}"><strong>{{ $post->user?->name ?? 'Nexa member' }}</strong></a><p>{{ $post->created_at->diffForHumans() }}</p></div><span class="more-dot">•••</span></div>
                        <h2><a href="{{ route('posts.show', $post) }}">{{ $post->name }}</a></h2>
                        <p>{{ Str::limit($post->description, 130) }}</p>
                        @include('posts.partials.inline-interactions', ['post' => $post])
                    </div>
                </article>
            @endforeach
        </div>
        <div class="pagination">{{ $posts->links() }}</div>
    @endif
@endsection