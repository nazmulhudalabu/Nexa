<?php

namespace App\Http\Controllers;

use App\Models\Friendship;
use App\Models\Story;
use App\Models\StoryReaction;
use App\Models\StoryReply;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class StoryController extends Controller
{
    public function create(): View { return view('stories.create'); }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['type' => ['required', 'in:TEXT,IMAGE,VIDEO,MUSIC,POLL'], 'privacy' => ['required', 'in:PUBLIC,FRIENDS,CUSTOM,HIDE_FROM_USERS,CLOSE_FRIENDS'], 'text' => ['nullable', 'string', 'max:2000'], 'media_url' => ['nullable', 'url', 'max:2000'], 'music' => ['nullable', 'string', 'max:255'], 'poll_options' => ['nullable', 'array', 'max:5'], 'poll_options.*' => ['string', 'max:120'], 'custom_user_ids' => ['nullable', 'array'], 'custom_user_ids.*' => ['integer', 'exists:users,id'], 'hidden_user_ids' => ['nullable', 'array'], 'hidden_user_ids.*' => ['integer', 'exists:users,id']]);
        $data['user_id'] = $request->user()->id; $data['expires_at'] = now()->addDay();
        Story::create($data);
        return to_route('posts.index')->with('success', 'Story shared for 24 hours.');
    }

    public function show(Request $request, Story $story): View
    {
        $this->assertVisible($request, $story);
        if ($request->user()) DB::table('story_viewers')->updateOrInsert(['story_id' => $story->id, 'user_id' => $request->user()->id], ['viewed_at' => now()]);
        $story->load(['user', 'replies', 'reactions']);
        return view('stories.show', compact('story'));
    }

    public function reply(Request $request, Story $story): RedirectResponse
    {
        $this->assertVisible($request, $story); $data = $request->validate(['body' => ['required', 'string', 'max:1000']]);
        StoryReply::create(['story_id' => $story->id, 'user_id' => $request->user()->id, 'body' => $data['body']]); return back()->with('success', 'Reply sent.');
    }

    public function react(Request $request, Story $story, string $type): RedirectResponse
    {
        $this->assertVisible($request, $story); abort_unless(in_array($type, ['LIKE', 'LOVE', 'HAHA', 'WOW', 'SAD'], true), 422);
        $reaction = StoryReaction::firstOrNew(['story_id' => $story->id, 'user_id' => $request->user()->id]);
        $reaction->reaction_type === $type && $reaction->exists ? $reaction->delete() : $reaction->fill(['reaction_type' => $type])->save(); return back();
    }

    public function share(Request $request, Story $story): RedirectResponse { $this->assertVisible($request, $story); return back()->with('shared_story_url', route('stories.show', $story)); }

    public function mute(Request $request, Story $story): RedirectResponse
    {
        DB::table('story_mutes')->updateOrInsert(['user_id' => $request->user()->id, 'story_owner_id' => $story->user_id], ['created_at' => now(), 'updated_at' => now()]); return to_route('posts.index')->with('success', 'Stories muted for this person.');
    }

    public function report(Request $request, Story $story): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:100'], 'details' => ['nullable', 'string', 'max:1000']]);
        DB::table('story_reports')->insert(['story_id' => $story->id, 'user_id' => $request->user()->id, 'reason' => $data['reason'], 'details' => $data['details'] ?? null, 'created_at' => now(), 'updated_at' => now()]); return back()->with('success', 'Story reported.');
    }

    private function assertVisible(Request $request, Story $story): void { abort_if($story->expires_at->isPast() || ! $this->canView($story, $request->user()), 404); }

    private function canView(Story $story, ?object $viewer): bool
    {
        if ($story->user_id === $viewer?->id || $story->privacy === 'PUBLIC') return true;
        if (! $viewer || DB::table('story_mutes')->where(['user_id' => $viewer->id, 'story_owner_id' => $story->user_id])->exists()) return false;
        if ($story->privacy === 'CUSTOM') return in_array($viewer->id, $story->custom_user_ids ?? [], true);
        if ($story->privacy === 'HIDE_FROM_USERS') return ! in_array($viewer->id, $story->hidden_user_ids ?? [], true);
        if ($story->privacy === 'CLOSE_FRIENDS') return false;
        [$one, $two] = Friendship::pair($viewer->id, $story->user_id);
        return $story->privacy === 'FRIENDS' && Friendship::where(['user_one_id' => $one, 'user_two_id' => $two, 'status' => Friendship::FRIENDS])->exists();
    }
}
