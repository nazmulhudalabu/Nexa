<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AccountAndChatUiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
    }

    public function test_email_and_password_can_be_changed_together(): void
    {
        $user = User::factory()->create(['password' => 'old-password-123']);

        $response = $this->actingAs($user)->put(route('profile.update'), [
            'name' => $user->name,
            'email' => 'new-address@example.com',
            'current_password' => 'old-password-123',
            'password' => 'new-password-456',
            'password_confirmation' => 'new-password-456',
        ]);

        $response->assertRedirect(route('people.show', $user));
        $user->refresh();
        $this->assertSame('new-address@example.com', $user->email);
        $this->assertTrue(Hash::check('new-password-456', $user->password));
    }

    public function test_password_change_requires_the_correct_current_password(): void
    {
        $user = User::factory()->create(['password' => 'old-password-123']);

        $response = $this->actingAs($user)->from(route('profile.edit'))->put(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'current_password' => 'wrong-password',
            'password' => 'new-password-456',
            'password_confirmation' => 'new-password-456',
        ]);

        $response->assertRedirect(route('profile.edit'));
        $response->assertSessionHasErrors('current_password');
        $this->assertTrue(Hash::check('old-password-123', $user->fresh()->password));
    }

    public function test_email_must_be_unique(): void
    {
        $user = User::factory()->create();
        User::factory()->create(['email' => 'taken@example.com']);

        $response = $this->actingAs($user)->from(route('profile.edit'))->put(route('profile.update'), [
            'name' => $user->name,
            'email' => 'taken@example.com',
        ]);

        $response->assertRedirect(route('profile.edit'));
        $response->assertSessionHasErrors('email');
    }

    public function test_email_can_change_without_touching_the_password(): void
    {
        $user = User::factory()->create(['password' => 'current-secret-1']);

        $response = $this->actingAs($user)->put(route('profile.update'), [
            'name' => $user->name,
            'email' => 'moved@example.com',
        ]);

        $response->assertRedirect(route('people.show', $user));
        $this->assertSame('moved@example.com', $user->fresh()->email);
        $this->assertTrue(Hash::check('current-secret-1', $user->fresh()->password));
    }

    private function createConversationWithMessages(int $count): array
    {
        $sender = User::factory()->create();
        $recipient = User::factory()->create();
        $conversation = Conversation::create(['user_one_id' => $sender->id, 'user_two_id' => $recipient->id]);

        for ($i = 1; $i <= $count; $i++) {
            Message::create(['conversation_id' => $conversation->id, 'user_id' => $sender->id, 'body' => 'message '.$i]);
        }

        return [$sender, $conversation];
    }

    public function test_chat_page_shows_only_the_last_ten_messages(): void
    {
        [$sender, $conversation] = $this->createConversationWithMessages(25);

        $response = $this->actingAs($sender)->get(route('chat.show', $conversation));

        $response->assertOk()
            ->assertSee('Load earlier messages')
            ->assertSee('message 25')
            ->assertDontSee('message 5');
    }

    public function test_older_messages_can_be_loaded_in_pages(): void
    {
        [$sender, $conversation] = $this->createConversationWithMessages(25);
        $newestTen = Message::where('conversation_id', $conversation->id)->orderByDesc('id')->take(10)->pluck('id');
        $before = Message::whereIn('id', $newestTen)->min('id');

        $response = $this->actingAs($sender)->getJson(route('chat.older', ['conversation' => $conversation, 'before' => $before]));

        $response->assertOk()
            ->assertJsonFragment(['has_more' => true])
            ->assertSee('message 15')
            ->assertDontSee('message 16');
    }

    public function test_a_message_can_be_sent_with_an_attachment_only(): void
    {
        Storage::fake('public');
        [$sender, $conversation] = $this->createConversationWithMessages(0);

        $response = $this->actingAs($sender)->post(route('chat.send', $conversation), [
            'attachment' => UploadedFile::fake()->create('report.pdf', 20, 'application/pdf'),
        ]);

        $response->assertRedirect();
        $message = Message::first();
        $this->assertSame('report.pdf', $message->attachment_name);
        $this->assertNull($message->body);
        Storage::disk('public')->assertExists($message->attachment_path);
    }

    public function test_a_message_requires_text_or_an_attachment(): void
    {
        [$sender, $conversation] = $this->createConversationWithMessages(0);

        $response = $this->actingAs($sender)->from(route('chat.show', $conversation))->post(route('chat.send', $conversation), []);

        $response->assertRedirect(route('chat.show', $conversation));
        $response->assertSessionHasErrors('body');
        $this->assertDatabaseCount('messages', 0);
    }
}
