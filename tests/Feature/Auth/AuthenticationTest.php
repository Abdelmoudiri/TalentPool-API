<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\WithFaker;

class AuthenticationTest extends TestCase
{
    use WithFaker;

    /**
     * Test user registration as a candidate.
     */
    public function test_user_can_register_as_candidate(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'candidate',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'user' => ['id', 'name', 'email', 'role'],
                'token',
            ])
            ->assertJson([
                'user' => [
                    'role' => 'candidate',
                ],
            ]);
            
        $this->assertDatabaseHas('users', [
            'email' => $response->json('user.email'),
            'role' => 'candidate',
        ]);
    }

    /**
     * Test user registration as a recruiter.
     */
    public function test_user_can_register_as_recruiter(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'recruiter',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'user' => ['id', 'name', 'email', 'role'],
                'token',
            ])
            ->assertJson([
                'user' => [
                    'role' => 'recruiter',
                ],
            ]);
            
        $this->assertDatabaseHas('users', [
            'email' => $response->json('user.email'),
            'role' => 'recruiter',
        ]);
    }

    /**
     * Test user login.
     */
    public function test_user_can_login_with_correct_credentials(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'user',
                'token',
                'token_type',
                'expires_in',
            ]);
    }

    /**
     * Test login with invalid credentials.
     */
    public function test_user_cannot_login_with_incorrect_credentials(): void
    {
        $user = User::factory()->create();

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'error' => 'Invalid credentials',
            ]);
    }

    /**
     * Test user logout.
     */
    public function test_user_can_logout(): void
    {
        $userData = $this->createCandidateAndGetToken();
        $token = $userData['token'];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/auth/logout');

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Successfully logged out',
            ]);
    }

    /**
     * Test token refresh.
     */
    public function test_user_can_refresh_token(): void
    {
        $userData = $this->createCandidateAndGetToken();
        $token = $userData['token'];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/auth/refresh');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'token',
            ]);
    }

    /**
     * Test unauthorized access.
     */
    public function test_unauthenticated_user_cannot_access_protected_routes(): void
    {
        $response = $this->getJson('/api/user');

        $response->assertStatus(401);
    }

    /**
     * Test authenticated user can access protected routes.
     */
    public function test_authenticated_user_can_access_protected_routes(): void
    {
        $userData = $this->createCandidateAndGetToken();
        $token = $userData['token'];
        $user = $userData['user'];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/user');

        $response->assertStatus(200)
            ->assertJson([
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ]);
    }
}