<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Like;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SocialProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
    }

    public function test_people_can_be_found_by_name(): void
    {
        User::factory()->create(['name' => 'Maya Chen']);
        User::factory()->create(['name' => 'Jon Bell']);

        $response = $this->get(route('people.index', ['search' => 'Maya']));

        $response->assertOk()->assertSee('Maya Chen')->assertDontSee('Jon Bell');
    }

    public function test_user_can_update_profile_details_and_photo(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put(route('profile.update'), [
            'name' => 'Updated Name',
            'email' => $user->email,
            'bio' => 'I make useful things.',
            'profession' => 'Product designer',
            'location' => 'Lisbon',
            'interests' => 'Design, music',
            'hobbies' => 'Cycling, books',
            'profile_photo' => UploadedFile::fake()->create('profile.png', 10, 'image/png'),
        ]);

        $response->assertRedirect(route('people.show', $user));
        $user->refresh();
        $this->assertSame('Product designer', $user->profession);
        $this->assertSame('Lisbon', $user->location);
        Storage::disk('public')->assertExists($user->profile_photo);
    }

    public function test_authenticated_user_can_react_and_comment_on_another_users_post(): void
    {
        $author = User::factory()->create(['name' => 'Post Author']);
        $viewer = User::factory()->create(['name' => 'Post Viewer']);
        $post = Post::create(['user_id' => $author->id, 'name' => 'Community post', 'description' => 'A shared idea', 'image' => 'posts/example.png']);

        $this->actingAs($viewer)->post(route('posts.like', $post))->assertRedirect();
        $this->actingAs($viewer)->post(route('posts.comments', $post), ['body' => 'This is a great idea.'])->assertRedirect();

        $this->assertDatabaseHas('likes', ['post_id' => $post->id, 'user_id' => $viewer->id]);
        $this->assertDatabaseHas('comments', ['post_id' => $post->id, 'user_id' => $viewer->id, 'body' => 'This is a great idea.']);
    }
}