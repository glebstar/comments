<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CommentResource;
use App\Models\VideoPost;
use Illuminate\Http\Request;

class VideoPostController extends Controller
{
    public function index()
    {
        return response()->json([
            'data' => VideoPost::query()->orderByDesc('id')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:255'],
        ]);

        $videoPost = VideoPost::create($data);

        return response()->json([
            'data' => $videoPost,
        ], 201);
    }

    public function show(Request $request, VideoPost $videoPost)
    {
        $perPage = (int) $request->query('per_page', 15);
        $perPage = max(1, min($perPage, 100));

        $comments = $videoPost->comments()
            ->with(['user', 'repliesRecursive'])
            ->orderBy('id')
            ->cursorPaginate($perPage);

        return response()->json([
            'data' => [
                'video_post' => $videoPost,
                'comments' => CommentResource::collection(collect($comments->items())),
            ],
            'cursor' => [
                'next' => $comments->nextCursor()?->encode(),
                'prev' => $comments->previousCursor()?->encode(),
            ],
        ]);
    }
}
