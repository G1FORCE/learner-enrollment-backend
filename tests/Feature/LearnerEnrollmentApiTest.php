<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LearnerEnrollmentApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'jwt.secret' => 'testing-secret-key-at-least-32-characters-long',
            'jwt.issuer' => 'http://localhost',
        ]);
    }

    public function test_api_health_check_succeeds(): void
    {
        $this->getJson('/api/test')
            ->assertOk()
            ->assertJson([
                'success' => true,
            ]);
    }

    public function test_registration_creates_a_learner_account(): void
    {
        $this->postJson('/api/register', [
            'name' => 'Test Learner',
            'email' => 'newlearner@example.com',
            'password' => 'StrongPass123!',
            'password_confirmation' => 'StrongPass123!',
        ])
            ->assertCreated()
            ->assertJsonPath('user.role', 'learner');

        $this->assertDatabaseHas('users', [
            'email' => 'newlearner@example.com',
            'role' => 'learner',
        ]);
    }

    public function test_unauthenticated_user_cannot_view_own_application(): void
    {
        $this->getJson('/api/my-application')
            ->assertUnauthorized();
    }

    public function test_learner_cannot_access_administrator_learner_list(): void
    {
        $learner = User::create([
            'name' => 'Test Learner',
            'email' => 'learner@example.com',
            'password' => 'StrongPass123!',
        ]);

        $token = app(\App\Services\JwtService::class)
            ->createToken($learner);

        $this->withToken($token)
            ->getJson('/api/learners')
            ->assertForbidden();
    }
}
