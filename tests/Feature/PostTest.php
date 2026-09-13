<?php

namespace Tests\Feature;

use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;

class PostTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
        $this->withoutMiddleware(ValidateCsrfToken::class);
    }

    public function test_a_post_can_be_created(): void
    {
        Storage::fake('public');

        $response = $this->post(route('posts.store'), [
            'name' => 'A useful note',
            'description' => 'Keep the important context close to the idea.',
            'media' => [UploadedFile::fake()->create('cover.png', 10, 'image/png')],
        ]);

        $post = Post::first();

        $response->assertRedirect(route('posts.index'));
        $this->assertNotNull($post);
        $this->assertSame('A useful note', $post->name);
        Storage::disk('public')->assertExists($post->image);
    }

    public function test_a_post_can_be_updated_without_replacing_its_image(): void
    {
        Storage::fake('public');
        $image = UploadedFile::fake()->create('cover.png', 10, 'image/png');
        $path = $image->store('posts', 'public');
        $post = Post::create(['user_id' => auth()->id(), 'name' => 'Original', 'description' => 'Original body', 'image' => $path]);

        $response = $this->put(route('posts.update', $post), [
            'name' => 'Updated title',
            'description' => 'Updated body',
        ]);

        $response->assertRedirect(route('posts.show', $post));
        $this->assertDatabaseHas('posts', ['id' => $post->id, 'name' => 'Updated title']);
        Storage::disk('public')->assertExists($path);
    }

    public function test_a_post_can_be_deleted_with_its_image(): void
    {
        Storage::fake('public');
        $path = UploadedFile::fake()->create('cover.png', 10, 'image/png')->store('posts', 'public');
        $post = Post::create(['user_id' => auth()->id(), 'name' => 'To remove', 'description' => 'Body', 'image' => $path]);

        $response = $this->delete(route('posts.destroy', $post));

        $response->assertRedirect(route('posts.index'));
        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_a_post_requires_media_when_created(): void
    {
        $response = $this->from(route('posts.create'))->post(route('posts.store'), [
            'name' => 'Missing image',
            'description' => 'This should not be saved.',
        ]);

        $response->assertRedirect(route('posts.create'));
        $response->assertSessionHasErrors('media');
        $this->assertDatabaseCount('posts', 0);
    }
}