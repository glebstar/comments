<?php

namespace Tests\Feature;

use App\Models\News;
use App\Models\User;
use App\Models\VideoPost;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentSystemTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_and_list_news(): void
    {
        $payload = [
            'title' => 'Breaking News',
            'description' => 'Some details about the news.',
        ];

        $create = $this->postJson('/api/news', $payload);
        $create->assertStatus(201)
            ->assertJsonPath('data.title', $payload['title'])
            ->assertJsonPath('data.description', $payload['description']);

        $this->assertDatabaseHas('news', [
            'title' => $payload['title'],
        ]);

        $newsA = News::factory()->create();
        $newsB = News::factory()->create();

        $index = $this->getJson('/api/news');
        $index->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.id', $newsB->id);
    }

    public function test_show_news_with_cursor_paginated_comments(): void
    {
        $news = News::factory()->create();
        $user = User::factory()->create();

        $commentOne = $news->comments()->create([
            'user_id' => $user->id,
            'body' => 'First comment',
        ]);
        $commentTwo = $news->comments()->create([
            'user_id' => $user->id,
            'body' => 'Second comment',
        ]);
        $commentThree = $news->comments()->create([
            'user_id' => $user->id,
            'body' => 'Third comment',
        ]);

        $reply = $commentOne->replies()->create([
            'user_id' => $user->id,
            'body' => 'Reply to first',
        ]);
        $nested = $reply->replies()->create([
            'user_id' => $user->id,
            'body' => 'Nested reply',
        ]);

        $response = $this->getJson("/api/news/{$news->id}?per_page=2");
        $response->assertOk()
            ->assertJsonPath('data.news.id', $news->id)
            ->assertJsonCount(2, 'data.comments')
            ->assertJsonPath('data.comments.0.id', $commentOne->id)
            ->assertJsonPath('data.comments.1.id', $commentTwo->id)
            ->assertJsonPath('data.comments.0.replies.0.id', $reply->id)
            ->assertJsonPath('data.comments.0.replies.0.replies.0.id', $nested->id);

        $nextCursor = $response->json('cursor.next');
        $this->assertNotNull($nextCursor);

        $pageTwo = $this->getJson("/api/news/{$news->id}?per_page=2&cursor={$nextCursor}");
        $pageTwo->assertOk()
            ->assertJsonCount(1, 'data.comments')
            ->assertJsonPath('data.comments.0.id', $commentThree->id);
    }

    public function test_create_and_list_video_posts(): void
    {
        $payload = [
            'title' => 'Video Post',
            'description' => 'Short description',
        ];

        $create = $this->postJson('/api/video-posts', $payload);
        $create->assertStatus(201)
            ->assertJsonPath('data.title', $payload['title'])
            ->assertJsonPath('data.description', $payload['description']);

        $this->assertDatabaseHas('video_posts', [
            'title' => $payload['title'],
        ]);

        $postA = VideoPost::factory()->create();
        $postB = VideoPost::factory()->create();

        $index = $this->getJson('/api/video-posts');
        $index->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.id', $postB->id);
    }

    public function test_show_video_post_with_cursor_paginated_comments(): void
    {
        $videoPost = VideoPost::factory()->create();
        $user = User::factory()->create();

        $commentOne = $videoPost->comments()->create([
            'user_id' => $user->id,
            'body' => 'First video comment',
        ]);
        $commentTwo = $videoPost->comments()->create([
            'user_id' => $user->id,
            'body' => 'Second video comment',
        ]);

        $response = $this->getJson("/api/video-posts/{$videoPost->id}?per_page=1");
        $response->assertOk()
            ->assertJsonPath('data.video_post.id', $videoPost->id)
            ->assertJsonCount(1, 'data.comments')
            ->assertJsonPath('data.comments.0.id', $commentOne->id);

        $nextCursor = $response->json('cursor.next');
        $this->assertNotNull($nextCursor);

        $pageTwo = $this->getJson("/api/video-posts/{$videoPost->id}?per_page=1&cursor={$nextCursor}");
        $pageTwo->assertOk()
            ->assertJsonCount(1, 'data.comments')
            ->assertJsonPath('data.comments.0.id', $commentTwo->id);
    }

    public function test_create_comment_for_news_and_video_post(): void
    {
        $user = User::factory()->create();
        $news = News::factory()->create();
        $videoPost = VideoPost::factory()->create();

        $newsResponse = $this->postJson("/api/news/{$news->id}/comments", [
            'user_id' => $user->id,
            'body' => 'Comment for news',
        ]);
        $newsResponse->assertStatus(201)
            ->assertJsonPath('data.body', 'Comment for news')
            ->assertJsonPath('data.commentable_type', News::class);

        $videoResponse = $this->postJson("/api/video-posts/{$videoPost->id}/comments", [
            'user_id' => $user->id,
            'body' => 'Comment for video',
        ]);
        $videoResponse->assertStatus(201)
            ->assertJsonPath('data.body', 'Comment for video')
            ->assertJsonPath('data.commentable_type', VideoPost::class);
    }

    public function test_create_reply_and_show_comment_with_replies(): void
    {
        $user = User::factory()->create();
        $news = News::factory()->create();

        $comment = $news->comments()->create([
            'user_id' => $user->id,
            'body' => 'Root comment',
        ]);

        $replyResponse = $this->postJson("/api/comments/{$comment->id}/replies", [
            'user_id' => $user->id,
            'body' => 'First reply',
        ]);
        $replyResponse->assertStatus(201)
            ->assertJsonPath('data.commentable_id', $comment->id)
            ->assertJsonPath('data.body', 'First reply');

        $show = $this->getJson("/api/comments/{$comment->id}");
        $show->assertOk()
            ->assertJsonPath('data.id', $comment->id)
            ->assertJsonPath('data.replies.0.body', 'First reply');
    }

    public function test_update_comment_requires_matching_user(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $news = News::factory()->create();

        $comment = $news->comments()->create([
            'user_id' => $owner->id,
            'body' => 'Original',
        ]);

        $forbidden = $this->patchJson("/api/comments/{$comment->id}", [
            'user_id' => $other->id,
            'body' => 'Updated',
        ]);
        $forbidden->assertStatus(403);

        $ok = $this->patchJson("/api/comments/{$comment->id}", [
            'user_id' => $owner->id,
            'body' => 'Updated',
        ]);
        $ok->assertOk()
            ->assertJsonPath('data.body', 'Updated');
    }

    public function test_delete_comment_requires_matching_user(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $news = News::factory()->create();

        $comment = $news->comments()->create([
            'user_id' => $owner->id,
            'body' => 'To be removed',
        ]);

        $forbidden = $this->deleteJson("/api/comments/{$comment->id}", [
            'user_id' => $other->id,
        ]);
        $forbidden->assertStatus(403);

        $ok = $this->deleteJson("/api/comments/{$comment->id}", [
            'user_id' => $owner->id,
        ]);
        $ok->assertStatus(204);

        $this->assertDatabaseMissing('comments', [
            'id' => $comment->id,
        ]);
    }
}
