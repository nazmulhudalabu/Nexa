<?php

namespace App\Http\Controllers;

use App\Models\Friendship;
use App\Models\Follower;
use App\Models\User;
use App\Notifications\ActivityNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FriendController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $search = trim((string) $request->query('search'));
        $friendships = Friendship::query()
            ->where('status', Friendship::FRIENDS)
            ->where(fn ($query) => $query->where('user_one_id', $user->id)->orWhere('user_two_id', $user->id))
            ->with(['userOne.profile', 'userTwo.profile'])
            ->get();
        $friendIds = $friendships->map(fn (Friendship $friendship) => $friendship->user_one_id === $user->id ? $friendship->user_two_id : $friendship->user_one_id);
        $friends = User::query()->whereIn('id', $friendIds)
            ->when($search !== '', fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->orderBy('name')->get();
        $requests = Friendship::query()->where('status', Friendship::REQUEST_SENT)->where('sender_id', '!=', $user->id)->where(fn ($query) => $query->where('user_one_id', $user->id)->orWhere('user_two_id', $user->id))->with('sender')->latest()->get();
        $sent = Friendship::query()->where('sender_id', $user->id)->where('status', Friendship::REQUEST_SENT)->with(['userOne', 'userTwo'])->latest()->get();
        $connectedIds = Friendship::query()->where(fn ($query) => $query->where('user_one_id', $user->id)->orWhere('user_two_id', $user->id))->pluck('user_one_id')->merge(Friendship::query()->where(fn ($query) => $query->where('user_one_id', $user->id)->orWhere('user_two_id', $user->id))->pluck('user_two_id'))->unique();
        $suggestions = User::query()->where('id', '!=', $user->id)->whereNotIn('id', $connectedIds)->orderBy('name')->limit(6)->get();
        $birthdays = User::query()->whereIn('id', $friendIds)->whereHas('profile', fn ($query) => $query->whereNotNull('birthday'))->with('profile')->get()->filter(fn (User $friend) => $friend->profile->birthday?->format('m-d') === now()->format('m-d'))->values();

        return view('friends.index', compact('friends', 'requests', 'sent', 'suggestions', 'birthdays', 'search'));
    }

    public function send(Request $request, User $user): RedirectResponse
    {
        $this->assertNotSelf($request, $user);
        [$userOneId, $userTwoId] = Friendship::pair($request->user()->id, $user->id);
        $friendship = Friendship::firstOrNew(['user_one_id' => $userOneId, 'user_two_id' => $userTwoId]);
        abort_if($friendship->exists && $friendship->status === Friendship::BLOCKED, 422, 'This friendship is blocked.');
        if ($friendship->exists && $friendship->status === Friendship::FRIENDS) {
            $friendship->delete();
            return back()->with('success', 'Friend removed.');
        }
        if ($friendship->exists && $friendship->status === Friendship::REQUEST_SENT && $friendship->sender_id !== $request->user()->id) {
            $friendship->update(['status' => Friendship::FRIENDS]);
            $friendship->sender->notify(new ActivityNotification('friend_accepted', $request->user(), 'accepted your friend request.', route('people.show', $request->user())));
            return back()->with('success', 'Friend request accepted.');
        }
        if (! $friendship->exists) {
            $friendship->fill(['sender_id' => $request->user()->id, 'status' => Friendship::REQUEST_SENT])->save();
            Follower::firstOrCreate(['follower_id' => $request->user()->id, 'following_id' => $user->id]);
            $user->notify(new ActivityNotification('friend_request', $request->user(), 'sent you a friend request.', route('friends.index')));
        }
        return back()->with('success', 'Friend request sent.');
    }

    public function accept(Request $request, Friendship $friendship): RedirectResponse
    {
        $this->assertIncoming($request, $friendship);
        $friendship->update(['status' => Friendship::FRIENDS]);
        $friendship->sender->notify(new ActivityNotification('friend_accepted', $request->user(), 'accepted your friend request.', route('people.show', $request->user())));
        return back()->with('success', 'You are now friends.');
    }

    public function reject(Request $request, Friendship $friendship): RedirectResponse
    {
        $this->assertIncoming($request, $friendship);
        $friendship->delete();
        return back()->with('success', 'Friend request rejected.');
    }

    public function cancel(Request $request, Friendship $friendship): RedirectResponse
    {
        abort_unless($friendship->sender_id === $request->user()->id && $friendship->status === Friendship::REQUEST_SENT, 403);
        $friendship->delete();
        return back()->with('success', 'Friend request cancelled.');
    }

    public function remove(Request $request, User $user): RedirectResponse
    {
        $this->assertNotSelf($request, $user);
        [$userOneId, $userTwoId] = Friendship::pair($request->user()->id, $user->id);
        Friendship::where(['user_one_id' => $userOneId, 'user_two_id' => $userTwoId])->where('status', Friendship::FRIENDS)->delete();
        return back()->with('success', 'Friend removed.');
    }

    private function assertIncoming(Request $request, Friendship $friendship): void
    {
        abort_unless($friendship->sender_id !== $request->user()->id && $friendship->status === Friendship::REQUEST_SENT, 403);
    }

    private function assertNotSelf(Request $request, User $user): void
    {
        abort_if($request->user()->is($user), 422, 'You cannot connect with yourself.');
    }
}
