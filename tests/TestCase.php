<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;
    
    /**
     * Create a candidate user and get JWT token.
     *
     * @return array
     */
    protected function createCandidateAndGetToken(): array
    {
        $user = User::factory()->create([
            'role' => 'candidate',
        ]);
        
        $token = JWTAuth::fromUser($user);
        
        return [
            'user' => $user,
            'token' => $token,
        ];
    }
    
    /**
     * Create a recruiter user and get JWT token.
     *
     * @return array
     */
    protected function createRecruiterAndGetToken(): array
    {
        $user = User::factory()->create([
            'role' => 'recruiter',
        ]);
        
        $token = JWTAuth::fromUser($user);
        
        return [
            'user' => $user,
            'token' => $token,
        ];
    }
    
    /**
     * Create an admin user and get JWT token.
     *
     * @return array
     */
    protected function createAdminAndGetToken(): array
    {
        $user = User::factory()->create([
            'role' => 'admin',
        ]);
        
        $token = JWTAuth::fromUser($user);
        
        return [
            'user' => $user,
            'token' => $token,
        ];
    }
}
