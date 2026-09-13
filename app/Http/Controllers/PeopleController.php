<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Friendship;
use App\Models\Follower;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PeopleController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search'));
        $people = User::query()
            ->when($search !== '', fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->withCount('posts')
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();
        $friendStatuses = collect();
        if ($request->user()) {
            $friendStatuses = Friendship::where(fn ($query) => $query->where('user_one_id', $request->user()->id)->orWhere('user_two_id', $request->user()->id))
                ->get()
                ->mapWithKeys(function (Friendship $friendship) use ($request): array {
                    $otherUserId = $friendship->user_one_id === $request->user()->id ? $friendship->user_two_id : $friendship->user_one_id;
                    return [$otherUserId => $friendship];
                });
        }

        return view('people.index', compact('people', 'search', 'friendStatuses'));
    }

    public function show(User $user): View
    {
        $profile = $user->profile()->firstOrCreate(['user_id' => $user->id], ['username' => Str::slug($user->name).'-'.$user->id]);
        $user->loadCount('posts')
            ->load(['profilePosts' => fn ($query) => $query->withCount(['likes', 'comments'])->with('media')->take(12), 'profilePhotos', 'coverPhotos']);
        $user->followers_count = $user->followers()->count();
        $user->following_count = $user->following()->count();
        $user->friends_count = Friendship::where('status', Friendship::FRIENDS)
            ->where(fn ($query) => $query->where('user_one_id', $user->id)->orWhere('user_two_id', $user->id))->count();
        $viewer = request()->user();
        $connection = collect();
        if ($viewer && $viewer->id !== $user->id && Follower::where(['follower_id' => $viewer->id, 'following_id' => $user->id])->exists()) {
            $connection->put('follow', true);
        }
        $friendship = null;
        $mutualFriends = collect();
        if ($viewer && $viewer->id !== $user->id) {
            [$userOneId, $userTwoId] = Friendship::pair($viewer->id, $user->id);
            $friendship = Friendship::where(['user_one_id' => $userOneId, 'user_two_id' => $userTwoId])->first();
            $viewerFriendIds = Friendship::where('status', Friendship::FRIENDS)->where(fn ($query) => $query->where('user_one_id', $viewer->id)->orWhere('user_two_id', $viewer->id))->get()->map(fn (Friendship $item) => $item->user_one_id === $viewer->id ? $item->user_two_id : $item->user_one_id);
            $profileFriendIds = Friendship::where('status', Friendship::FRIENDS)->where(fn ($query) => $query->where('user_one_id', $user->id)->orWhere('user_two_id', $user->id))->get()->map(fn (Friendship $item) => $item->user_one_id === $user->id ? $item->user_two_id : $item->user_one_id);
            $mutualFriends = User::whereIn('id', $viewerFriendIds->intersect($profileFriendIds))->limit(5)->get();
            if ($friendship?->status === Friendship::FRIENDS) {
                $connection->put('friend', $friendship);
            }
        }
        $details = DB::table('user_bios')->where('user_id', $user->id)->first();
        $workplace = DB::table('user_workplaces')->where('user_id', $user->id)->value('workplace');
        $education = DB::table('user_education')->where('user_id', $user->id)->value('education');
        $visibility = $profile->visibility ?: [];
        $isOwner = $viewer?->id === $user->id;
        $canView = fn (string $field): bool => $isOwner || ($visibility[$field] ?? true);
        return view('people.show', compact('user', 'profile', 'details', 'workplace', 'education', 'connection', 'friendship', 'mutualFriends', 'canView', 'isOwner'));
    }
}