<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Category;
use App\Models\PostMedia;
use App\Models\Friendship;
use App\Models\Follower;
use App\Models\User;
use App\Models\Story;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PostController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search'));

        $priorityUserIds = [];
        $stories = collect();
        $posts = Post::query()
            ->where(fn ($query) => $this->visibleToViewer($query, Auth::user()))
            ->with(['media'])
            ->withCount(['likes', 'reactions', 'comments'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when(Auth::check(), function ($query) use (&$priorityUserIds): void {
                $userId = (int) Auth::id();
                $friendIds = Friendship::where('status', Friendship::FRIENDS)
                    ->where(fn ($friendQuery) => $friendQuery->where('user_one_id', $userId)->orWhere('user_two_id', $userId))
                    ->get()
                    ->map(fn (Friendship $friendship) => $friendship->user_one_id === $userId ? $friendship->user_two_id : $friendship->user_one_id)
                    ->all();
                $followingIds = Follower::where('follower_id', $userId)->pluck('following_id')->all();
                $priorityUserIds = array_values(array_unique(array_merge([$userId], $friendIds, $followingIds)));
                $friendList = implode(',', array_map('intval', $friendIds)) ?: '0';
                $followingList = implode(',', array_map('intval', $followingIds)) ?: '0';
                $query->orderByRaw("CASE WHEN user_id = {$userId} THEN 0 WHEN user_id IN ({$friendList}) THEN 1 WHEN user_id IN ({$followingList}) THEN 2 ELSE 3 END ASC")
                    ->orderByRaw('(likes_count + comments_count) DESC');
            })
            ->latest()
            ->paginate(9)
            ->withQueryString();

        if (Auth::check()) {
            $viewerId = Auth::id();
            $friendIds = Friendship::where('status', Friendship::FRIENDS)->where(fn ($query) => $query->where('user_one_id', $viewerId)->orWhere('user_two_id', $viewerId))->get()->map(fn (Friendship $friendship) => $friendship->user_one_id === $viewerId ? $friendship->user_two_id : $friendship->user_one_id);
            $stories = Story::with('user')->whereIn('user_id', $priorityUserIds)->where('user_id', '!=', $viewerId)->where('expires_at', '>', now())->whereNotExists(fn ($query) => $query->selectRaw('1')->from('story_mutes')->whereColumn('story_mutes.story_owner_id', 'stories.user_id')->where('story_mutes.user_id', $viewerId))->where(function ($query) use ($viewerId, $friendIds): void {
                $query->where('privacy', 'PUBLIC')->orWhere('user_id', $viewerId)->orWhere(fn ($friends) => $friends->where('privacy', 'FRIENDS')->whereIn('user_id', $friendIds));
            })->latest()->get()->unique('user_id')->values();
        }

        return view('posts.index', compact('posts', 'search', 'stories'));
    }

    public function create(): View
    {
        return view('posts.create', ['categories' => Category::orderBy('name')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatePost($request, true);
        $validated['user_id'] = Auth::id();
        $validated['share_token'] = Str::random(32);

        $post = Post::create($validated);
        $post->tags()->sync($this->tagIds($request->input('tags', '')));
        $coverPath = $this->storeMedia($request, $post);
        if ($coverPath !== null) {
            $post->update(['image' => $coverPath]);
        }

        return to_route('posts.index')->with('success', 'Post created successfully.');
    }

    public function show(Post $post): View
    {
        abort_unless($this->canViewPost($post, Auth::user()), 404);
        return view('posts.show', ['post' => $post->load(['comments.user', 'comments.replies.user', 'likes', 'reactions', 'tags', 'category', 'media'])]);
    }

    public function shared(string $token): View
    {
        $post = Post::where('share_token', $token)->firstOrFail();
        abort_unless($this->canViewPost($post, Auth::user()), 404);
        $post->load(['comments.user', 'tags', 'category', 'media']);
        return view('posts.shared', compact('post'));
    }

    public function edit(Post $post): View
    {
        $this->authorizeOwner($post);
        return view('posts.edit', ['post' => $post, 'categories' => Category::orderBy('name')->get()]);
    }

    public function update(Request $request, Post $post): RedirectResponse
    {
        $this->authorizeOwner($post);
        $validated = $this->validatePost($request);

        if ($request->hasFile('image')) {
            $this->deleteImage($post->image);
            $validated['image'] = $request->file('image')->store('posts', 'public');
        }

        $post->update($validated);
        $post->tags()->sync($this->tagIds($request->input('tags', '')));

        return to_route('posts.show', $post)->with('success', 'Post updated successfully.');
    }

    public function destroy(Post $post): RedirectResponse
    {
        $this->authorizeOwner($post);
        $this->deleteImage($post->image);
        foreach ($post->media as $mediaFile) {
            $this->deleteImage($mediaFile->path);
        }
        $post->delete();

        return to_route('posts.index')->with('success', 'Post deleted successfully.');
    }

    private function validatePost(Request $request, bool $creating = false): array
    {
        $textPost = $creating && $request->input('post_type') === 'TEXT';
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['required', 'string', 'max:5000'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,webp', 'max:20480'],
            'post_type' => ['nullable', 'in:TEXT,MEDIA'],
            'media' => [$creating && ! $textPost ? 'required' : 'nullable', 'array', $creating && ! $textPost ? 'min:1' : 'sometimes', 'max:12'],
            'media.*' => ['file', 'mimes:jpeg,jpg,png,webp,mp4,mov,webm', 'max:20480'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'tags' => ['nullable', 'string', 'max:500'],
            'visibility' => ['nullable', 'in:PUBLIC,FRIENDS,ONLY_ME'],
            'location' => ['nullable', 'string', 'max:120'],
            'feeling' => ['nullable', 'string', 'max:120'],
            'link_url' => ['nullable', 'url', 'max:1000'],
            'link_title' => ['nullable', 'string', 'max:255'],
            'link_description' => ['nullable', 'string', 'max:1000'],
            'link_thumbnail' => ['nullable', 'url', 'max:1000'],
        ]);
    }

    private function visibleToViewer($query, ?\App\Models\User $viewer): void
    {
        $query->where(function ($visibility) use ($viewer): void {
            $visibility->where('visibility', 'PUBLIC')->orWhereNull('visibility');
            if ($viewer) {
                $visibility->orWhere('user_id', $viewer->id);
                $friendIds = Friendship::where('status', Friendship::FRIENDS)->where(fn ($friends) => $friends->where('user_one_id', $viewer->id)->orWhere('user_two_id', $viewer->id))->get()->map(fn (Friendship $friend) => $friend->user_one_id === $viewer->id ? $friend->user_two_id : $friend->user_one_id);
                $visibility->orWhere(fn ($friends) => $friends->where('visibility', 'FRIENDS')->whereIn('user_id', $friendIds));
            }
        });
    }

    private function canViewPost(Post $post, ?\App\Models\User $viewer): bool
    {
        if ($post->user_id === $viewer?->id || $post->visibility === 'PUBLIC' || $post->visibility === null) return true;
        if (! $viewer) return false;
        if ($post->visibility === 'ONLY_ME') return false;
        return $post->visibility === 'FRIENDS' && Friendship::where('status', Friendship::FRIENDS)->where(fn ($query) => $query->where(['user_one_id' => min($viewer->id, $post->user_id), 'user_two_id' => max($viewer->id, $post->user_id)]))->exists();
    }

    private function tagIds(?string $tags): array
    {
        return collect(explode(',', $tags ?? ''))->map(fn (string $tag): string => trim($tag))->filter()->unique()->map(function (string $tag): int {
            return \App\Models\Tag::firstOrCreate(['slug' => Str::slug($tag)], ['name' => $tag])->id;
        })->all();
    }

    private function deleteImage(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    private function storeMedia(Request $request, Post $post): ?string
    {
        $coverPath = null;
        foreach ($request->file('media', []) as $file) {
            $path = $file->store('post-media', 'public');
            $post->media()->create([
                'path' => $path,
                'type' => str_starts_with((string) $file->getMimeType(), 'video/') ? 'video' : 'image',
                'original_name' => $file->getClientOriginalName(),
            ]);
            if ($coverPath === null && str_starts_with((string) $file->getMimeType(), 'image/')) {
                $coverPath = $path;
            }
        }
        return $coverPath;
    }

    private function authorizeOwner(Post $post): void
    {
        abort_if($post->user_id !== null && (int) $post->user_id !== (int) Auth::id(), 403);
    }
}
