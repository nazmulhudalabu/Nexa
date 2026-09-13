<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PostApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $posts = Post::withCount(['likes', 'comments'])->latest()->paginate($request->integer('per_page', 15));
        return response()->json($posts);
    }

    public function show(Post $post): JsonResponse
    {
        return response()->json($post->load(['comments.user', 'likes', 'tags', 'category', 'media']));
    }
}