<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_sends_a_verification_notification(): void
    {
        $response = $this->post(route('register.store'), [
            'name' => 'New User',
            'email' => 'new-user@example.com',
            'phone' => '01700000000',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('verification.notice'));
        $this->assertDatabaseHas('users', [
            'email' => 'new-user@example.com',
            'status' => 'PENDING_VERIFICATION',
        ]);
    }
}