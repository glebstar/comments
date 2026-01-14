<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CommentResource;
use App\Models\News;
use Illuminate\Http\Request;

class NewsController extends Controller
{
    public function index()
    {
        return response()->json([
            'data' => News::query()->orderByDesc('id')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
        ]);

        $news = News::create($data);

        return response()->json([
            'data' => $news,
        ], 201);
    }

    public function show(Request $request, News $news)
    {
        $perPage = (int) $request->query('per_page', 15);
        $perPage = max(1, min($perPage, 100));

        $comments = $news->comments()
            ->with(['user', 'repliesRecursive'])
            ->orderBy('id')
            ->cursorPaginate($perPage);

        return response()->json([
            'data' => [
                'news' => $news,
                'comments' => CommentResource::collection(collect($comments->items())),
            ],
            'cursor' => [
                'next' => $comments->nextCursor()?->encode(),
                'prev' => $comments->previousCursor()?->encode(),
            ],
        ]);
    }
}
