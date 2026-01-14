<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CommentResource;
use App\Models\Comment;
use App\Models\News;
use App\Models\VideoPost;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function storeForNews(Request $request, News $news)
    {
        $data = $this->validatedCommentData($request);

        $comment = $news->comments()->create($data);
        $comment->load(['user', 'repliesRecursive']);

        return response()->json([
            'data' => new CommentResource($comment),
        ], 201);
    }

    public function storeForVideoPost(Request $request, VideoPost $videoPost)
    {
        $data = $this->validatedCommentData($request);

        $comment = $videoPost->comments()->create($data);
        $comment->load(['user', 'repliesRecursive']);

        return response()->json([
            'data' => new CommentResource($comment),
        ], 201);
    }

    public function storeForComment(Request $request, Comment $comment)
    {
        $data = $this->validatedCommentData($request);

        $reply = $comment->replies()->create($data);
        $reply->load(['user', 'repliesRecursive']);

        return response()->json([
            'data' => new CommentResource($reply),
        ], 201);
    }

    public function show(Comment $comment)
    {
        $comment->load(['user', 'repliesRecursive']);

        return response()->json([
            'data' => new CommentResource($comment),
        ]);
    }

    public function update(Request $request, Comment $comment)
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'body' => ['required', 'string'],
        ]);

        if ((int) $data['user_id'] !== $comment->user_id) {
            return response()->json([
                'message' => 'User mismatch.',
            ], 403);
        }

        $comment->update([
            'body' => $data['body'],
        ]);

        $comment->load(['user', 'repliesRecursive']);

        return response()->json([
            'data' => new CommentResource($comment),
        ]);
    }

    public function destroy(Request $request, Comment $comment)
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        if ((int) $data['user_id'] !== $comment->user_id) {
            return response()->json([
                'message' => 'User mismatch.',
            ], 403);
        }

        $comment->delete();

        return response()->json([], 204);
    }

    private function validatedCommentData(Request $request): array
    {
        return $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'body' => ['required', 'string'],
        ]);
    }
}
