<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Post;
use App\Models\Reaction;
use App\Models\Share;
use App\Notifications\ActivityNotification;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InteractionController extends Controller
{
    public function comment(Request $request, Post $post): RedirectResponse
    {
        $data = $request->validate(['body' => ['required', 'string', 'max:2000'], 'parent_id' => ['nullable', 'exists:comments,id']]);
        $post->comments()->create(['user_id' => Auth::id(), 'parent_id' => $data['parent_id'] ?? null, 'body' => $data['body']]);
        if ($post->user_id && (int) $post->user_id !== (int) Auth::id()) {
            $post->user->notify(new ActivityNotification('comment', Auth::user(), 'commented on your post.', route('posts.show', $post).'#comments'));
        }
        return back()->with('success', 'Comment added.');
    }

    public function like(Post $post): RedirectResponse
    {
        $userId = (int) Auth::id();
        $like = $post->likes()->where('user_id', $userId)->first();
        if ($like) {
            $like->delete();
            Reaction::where(['post_id' => $post->id, 'user_id' => $userId, 'reaction_type' => 'LIKE'])->delete();
        } else {
            try {
                $post->likes()->create(['user_id' => $userId]);
                Reaction::updateOrCreate(['post_id' => $post->id, 'user_id' => $userId], ['reaction_type' => 'LIKE']);
                if ($post->user_id && (int) $post->user_id !== $userId) {
                    $post->user->notify(new ActivityNotification('like', Auth::user(), 'liked your post.', route('posts.show', $post)));
                }
            } catch (UniqueConstraintViolationException) {
                // A concurrent request already created the like.
            }
        }
        return back();
    }

    public function reaction(Request $request, Post $post, string $type): RedirectResponse
    {
        abort_unless(in_array($type, ['LIKE', 'LOVE', 'CARE', 'HAHA', 'WOW', 'SAD', 'ANGRY'], true), 422);
        $reaction = Reaction::where(['post_id' => $post->id, 'user_id' => Auth::id()])->first();
        if ($reaction?->reaction_type === $type) {
            $reaction->delete();
            $post->likes()->where('user_id', Auth::id())->delete();
        } elseif ($reaction) {
            $reaction->update(['reaction_type' => $type]);
            if ($type === 'LIKE') {
                $post->likes()->firstOrCreate(['user_id' => Auth::id()]);
            } else {
                $post->likes()->where('user_id', Auth::id())->delete();
            }
        } else {
            Reaction::create(['post_id' => $post->id, 'user_id' => Auth::id(), 'reaction_type' => $type]);
            if ($type === 'LIKE') {
                $post->likes()->firstOrCreate(['user_id' => Auth::id()]);
            }
        }
        return back();
    }

    public function share(Request $request, Post $post): RedirectResponse
    {
        $type = $request->validate(['share_type' => ['required', 'in:FEED,STORY,FRIEND']])['share_type'];
        Share::create(['post_id' => $post->id, 'user_id' => Auth::id(), 'share_type' => $type]);
        return back()->with('success', 'Post shared.');
    }
}