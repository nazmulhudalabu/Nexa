@extends('layouts.app', ['title' => $post->name])

@section('content')
    <article class="single-post">
        <a class="back-link" href="{{ route('posts.index') }}">← Back to archive</a>
        <div class="single-post-grid">
            <div class="single-post-image">
                @if ($post->image)
                    <img src="{{ asset('storage/' . $post->image) }}" alt="{{ $post->name }}">
                @elseif ($cover = $post->media->firstWhere('type', 'image') ?? $post->media->first())
                    <img src="{{ $cover->url }}" alt="{{ $post->name }}">
                @endif
            </div>
            <div class="single-post-copy">
                <p class="post-date">{{ $post->created_at->format('F j, Y') }}</p>
                <h1>{{ $post->name }}</h1>
                <div class="tag-row">@if ($post->category)<span class="tag category-tag">{{ $post->category->name }}</span>@endif @foreach ($post->tags as $tag)<span class="tag">#{{ $tag->name }}</span>@endforeach</div>
                <div class="post-description">{!! nl2br(e($post->description)) !!}</div>@if ($post->location)<p class="post-context">Location: {{ $post->location }}</p>@endif @if ($post->feeling)<p class="post-context">{{ $post->feeling }}</p>@endif @if ($post->link_url)<p class="post-link-preview"><a href="{{ $post->link_url }}" target="_blank" rel="noreferrer"><strong>{{ $post->link_title ?: $post->link_url }}</strong>@if ($post->link_description)<small>{{ $post->link_description }}</small>@endif</a></p>@endif
                <div class="post-actions">
                    @auth
                        <form method="POST" action="{{ route('posts.like', $post) }}">@csrf<button class="button button-quiet" type="submit">{{ $post->reactions->contains(fn ($reaction) => $reaction->user_id === auth()->id() && $reaction->reaction_type === 'LIKE') ? 'Unlike' : 'Like' }} ({{ $post->reactions->count() }})</button></form>
                        <div class="reaction-actions">@foreach (['LOVE' => '❤️', 'CARE' => '🤗', 'HAHA' => '😂', 'WOW' => '😮', 'SAD' => '😢', 'ANGRY' => '😡'] as $reactionType => $emoji)<form method="POST" action="{{ route('posts.reaction', [$post, 'type' => $reactionType]) }}">@csrf<button class="button button-quiet" type="submit" title="{{ $reactionType }}">{{ $emoji }}</button></form>@endforeach</div>
                    @else
                        <span class="like-count">{{ $post->likes->count() }} likes</span>
                    @endauth
                    @if ($post->share_token)
                        <button class="button button-quiet" type="button" onclick="navigator.clipboard.writeText('{{ route('posts.shared', $post->share_token) }}').then(() => { this.textContent = 'Link copied'; });">Copy share link</button>
                    @endif
                    @auth<form method="POST" action="{{ route('posts.share', $post) }}">@csrf<input type="hidden" name="share_type" value="FEED"><button class="button button-quiet" type="submit">Share Now</button></form>@endauth
                    @if ((int) auth()->id() === $post->user_id || $post->user_id === null)
                        <a class="button button-accent" href="{{ route('posts.edit', $post) }}">Edit note</a>
                        <form method="POST" action="{{ route('posts.destroy', $post) }}" onsubmit="return confirm('Delete this note?');">
                            @csrf
                            @method('DELETE')
                            <button class="button button-quiet" type="submit">Delete</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
        @if ($post->media->isNotEmpty())
            <section class="post-media-section">
                <div class="section-heading"><h2>Photos and videos</h2><span class="post-count">{{ $post->media->count() }}</span></div>
                <div class="post-media-grid">
                    @foreach ($post->media as $media)
                        @if ($media->type === 'video')
                            <video controls preload="metadata"><source src="{{ $media->url }}"></video>
                        @else
                            <img src="{{ $media->url }}" alt="{{ $media->original_name }}">
                        @endif
                    @endforeach
                </div>
            </section>
        @endif
        <section class="comments"><h2>Conversation <span>({{ $post->comments->count() }})</span></h2>@auth<form class="comment-form" method="POST" action="{{ route('posts.comments', $post) }}">@csrf<textarea name="body" rows="3" placeholder="Add a thoughtful response..." required>{{ old('body') }}</textarea>@error('body')<span class="field-error">{{ $message }}</span>@enderror<button class="button button-dark" type="submit">Add comment</button></form>@else<p class="auth-note"><a class="clear-link" href="{{ route('login') }}">Log in</a> to join the conversation.</p>@endauth @forelse ($post->comments->whereNull('parent_id') as $comment)<div class="comment"><strong>{{ $comment->user->name }}</strong><small>{{ $comment->created_at->diffForHumans() }}</small><p>{{ $comment->body }}</p>@foreach ($comment->replies as $reply)<div class="comment-reply"><strong>{{ $reply->user->name }}</strong><small>{{ $reply->created_at->diffForHumans() }}</small><p>{{ $reply->body }}</p></div>@endforeach @auth<form class="reply-form" method="POST" action="{{ route('posts.comments', $post) }}">@csrf<input type="hidden" name="parent_id" value="{{ $comment->id }}"><input name="body" placeholder="Reply..." required><button class="button button-quiet" type="submit">Reply</button></form>@endauth</div>@empty<p class="auth-note">No comments yet.</p>@endforelse</section>
    </article>
@endsection