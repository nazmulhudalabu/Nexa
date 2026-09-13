<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ChatAndMediaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
    }

    public function test_a_user_can_start_a_conversation_and_save_message_history(): void
    {
        $sender = User::factory()->create();
        $recipient = User::factory()->create();

        $conversationResponse = $this->actingAs($sender)->get(route('chat.with', $recipient));
        $conversation = Conversation::first();

        $conversationResponse->assertRedirect(route('chat.show', $conversation));
        $this->actingAs($sender)->get(route('chat.show', $conversation))->assertOk()->assertSee('Chat with '.$recipient->name)->assertSee('You are '.$sender->name);
        $this->actingAs($sender)->post(route('chat.send', $conversation), ['body' => 'Hello from Nexa.'])->assertRedirect();

        $this->assertDatabaseHas('messages', ['conversation_id' => $conversation->id, 'user_id' => $sender->id, 'body' => 'Hello from Nexa.']);
        $this->actingAs($recipient)->get(route('chat.show', $conversation))->assertOk()->assertSee('Chat with '.$sender->name)->assertSee('You are '.$recipient->name)->assertSee('Hello from Nexa.');

        $this->actingAs($recipient)->post(route('chat.send', $conversation), ['body' => 'Reply from the other profile.'])->assertRedirect();
        $this->actingAs($sender)->get(route('chat.show', $conversation))->assertSee('Reply from the other profile.')->assertSee('You');

        $this->assertSame($recipient->id, $conversation->fresh()->otherUser($sender->id)->id);
        $this->assertSame($sender->id, $conversation->fresh()->otherUser($recipient->id)->id);
    }

    public function test_post_can_store_multiple_photo_and_video_files(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('posts.store'), [
            'name' => 'Weekend collection',
            'description' => 'A few moments from the weekend.',
            'media' => [UploadedFile::fake()->create('cover.png', 10, 'image/png')],
            'media' => [
                UploadedFile::fake()->create('photo.png', 10, 'image/png'),
                UploadedFile::fake()->create('clip.mp4', 10, 'video/mp4'),
            ],
        ]);

        $post = Post::first();
        $response->assertRedirect(route('posts.index'));
        $this->assertSame(2, $post->media()->count());
        $this->assertDatabaseHas('post_media', ['post_id' => $post->id, 'type' => 'video']);
    }
}