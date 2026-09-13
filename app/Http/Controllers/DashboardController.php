<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $posts = Post::where('user_id', Auth::id())->withCount(['likes', 'comments'])->latest()->get();
        return view('dashboard', ['posts' => $posts, 'likes' => $posts->sum('likes_count'), 'comments' => $posts->sum('comments_count')]);
    }
}