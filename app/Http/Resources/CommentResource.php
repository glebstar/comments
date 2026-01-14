<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $replies = $this->relationLoaded('repliesRecursive')
            ? $this->repliesRecursive->values()
            : collect();

        return [
            'id' => $this->id,
            'body' => $this->body,
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                ];
            }),
            'commentable_type' => $this->commentable_type,
            'commentable_id' => $this->commentable_id,
            'replies' => CommentResource::collection($replies),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
