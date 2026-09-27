<?php

namespace Tests\Feature;

use App\Models\Story;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_custom_story_accepts_a_matching_json_user_id(): void
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();
        $story = Story::create([
            'user_id' => $owner->id,
            'type' => 'TEXT',
            'privacy' => 'CUSTOM',
            'custom_user_ids' => [(string) $viewer->id],
            'expires_at' => now()->addDay(),
        ]);

        $response = $this->actingAs($viewer)->get(route('stories.show', $story));

        $response->assertOk();
    }

    public function test_a_hidden_story_cannot_be_shared_or_reported(): void
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();
        $story = Story::create([
            'user_id' => $owner->id,
            'type' => 'TEXT',
            'privacy' => 'HIDE_FROM_USERS',
            'hidden_user_ids' => [$viewer->id],
            'expires_at' => now()->addDay(),
        ]);

        $this->actingAs($viewer)
            ->post(route('stories.share', $story))
            ->assertNotFound();

        $this->actingAs($viewer)
            ->post(route('stories.report', $story), ['reason' => 'Inappropriate'])
            ->assertNotFound();

        $this->assertDatabaseMissing('story_reports', ['story_id' => $story->id]);
    }
}
