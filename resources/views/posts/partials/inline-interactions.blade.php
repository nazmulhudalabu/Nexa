@auth
    <div class="inline-interactions">
        <div class="inline-reaction-wrap">
            <button class="inline-action inline-reaction-toggle" type="button" aria-label="React to this post" title="React">♡ <span>{{ $post->reactions_count ?? $post->likes_count ?? $post->likes->count() }}</span></button>
            <div class="inline-reaction-menu" hidden>
                @foreach (['LIKE' => '👍', 'LOVE' => '❤️', 'CARE' => '🤗', 'HAHA' => '😂', 'WOW' => '😮', 'SAD' => '😢', 'ANGRY' => '😡'] as $reactionType => $emoji)
                    <form method="POST" action="{{ route('posts.reaction', [$post, 'type' => $reactionType]) }}">
                        @csrf
                        <button class="inline-reaction" type="submit" title="{{ $reactionType }}">{{ $emoji }}</button>
                    </form>
                @endforeach
            </div>
        </div>
        <a class="inline-action" href="#post-comments-{{ $post->id }}">◌ <span>{{ $post->comments_count ?? $post->comments->count() }}</span></a>
        <form method="POST" action="{{ route('posts.share', $post) }}">
            @csrf
            <input type="hidden" name="share_type" value="FEED">
            <button class="inline-action" type="submit">↗ Share</button>
        </form>
    </div>
    <form class="inline-comment-form" method="POST" action="{{ route('posts.comments', $post) }}">
        @csrf
        <input id="post-comments-{{ $post->id }}" name="body" type="text" maxlength="2000" placeholder="Write a comment..." required>
        <button class="inline-comment-submit" type="submit" aria-label="Post comment" title="Post comment">↵</button>
    </form>
@else
    <div class="inline-interactions inline-interactions-guest">
        <span>{{ $post->reactions_count ?? $post->likes_count ?? $post->likes->count() }} reactions</span>
        <span>{{ $post->comments_count ?? $post->comments->count() }} comments</span>
        <a class="read-link" href="{{ route('login') }}">Sign in to interact</a>
    </div>
@endauth
