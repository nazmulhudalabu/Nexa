<?php

namespace App\Http\Controllers;

use App\Models\ProfileBlock;
use App\Models\ProfileReport;
use App\Models\Friendship;
use App\Models\Follower;
use App\Models\SocialConnection;
use App\Models\User;
use App\Notifications\ActivityNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProfileInteractionController extends Controller
{
    public function follow(Request $request, User $user): RedirectResponse { return $this->toggle($request, $user, 'follow'); }
    public function friend(Request $request, User $user): RedirectResponse { return $this->toggle($request, $user, 'friend'); }

    public function block(Request $request, User $user): RedirectResponse
    {
        abort_if($request->user()->is($user), 422, 'You cannot block yourself.');
        ProfileBlock::firstOrCreate(['user_id' => $request->user()->id, 'blocked_user_id' => $user->id]);
        [$userOneId, $userTwoId] = Friendship::pair($request->user()->id, $user->id);
        Friendship::updateOrCreate(
            ['user_one_id' => $userOneId, 'user_two_id' => $userTwoId],
            ['sender_id' => $request->user()->id, 'status' => Friendship::BLOCKED],
        );
        Follower::where('follower_id', $request->user()->id)->where('following_id', $user->id)->delete();
        SocialConnection::where('user_id', $request->user()->id)->where('target_user_id', $user->id)->delete();
        return back()->with('success', 'This profile has been blocked.');
    }

    public function report(Request $request, User $user): RedirectResponse
    {
        abort_if($request->user()->is($user), 422, 'You cannot report yourself.');
        $data = $request->validate(['reason' => ['required', 'string', 'max:100'], 'details' => ['nullable', 'string', 'max:1000']]);
        ProfileReport::create(['user_id' => $request->user()->id, 'reported_user_id' => $user->id] + $data);
        return back()->with('success', 'Thanks. Your report has been submitted.');
    }

    public function share(Request $request, User $user): RedirectResponse
    {
        return back()->with('shared_profile_url', route('people.show', $user));
    }

    private function toggle(Request $request, User $user, string $type): RedirectResponse
    {
        abort_if($request->user()->is($user), 422, 'You cannot connect with yourself.');
        $connection = $type === 'follow'
            ? Follower::where('follower_id', $request->user()->id)->where('following_id', $user->id)->first()
            : SocialConnection::where('user_id', $request->user()->id)->where('target_user_id', $user->id)->where('type', $type)->first();
        if ($connection) {
            $connection->delete();
        } else {
            if ($type === 'follow') {
                Follower::create(['follower_id' => $request->user()->id, 'following_id' => $user->id]);
            } else {
                SocialConnection::create(['user_id' => $request->user()->id, 'target_user_id' => $user->id, 'type' => $type, 'status' => 'accepted']);
            }
            if ($type === 'follow') {
                $user->notify(new ActivityNotification('follow', $request->user(), 'started following you.', route('people.show', $request->user())));
            }
        }
        return back();
    }
}