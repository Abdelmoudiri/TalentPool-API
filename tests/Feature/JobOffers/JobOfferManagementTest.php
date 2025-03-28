<?php

namespace Tests\Feature\JobOffers;

use Tests\TestCase;
use App\Models\User;
use App\Models\JobOffer;
use Illuminate\Foundation\Testing\WithFaker;

class JobOfferManagementTest extends TestCase
{
    use WithFaker;

    /**
     * Test that a recruiter can create a job offer.
     */
    public function test_recruiter_can_create_job_offer(): void
    {
        $userData = $this->createRecruiterAndGetToken();
        $token = $userData['token'];

        $jobOfferData = [
            'title' => $this->faker->jobTitle,
            'description' => $this->faker->paragraph,
            'company_name' => $this->faker->company,
            'location' => $this->faker->city,
            'contract_type' => $this->faker->randomElement(['CDI', 'CDD', 'Freelance']),
            'salary_min' => $this->faker->numberBetween(30000, 50000),
            'salary_max' => $this->faker->numberBetween(50001, 100000),
            'is_active' => true,
            'expires_at' => now()->addMonths(1)->format('Y-m-d'),
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/job-offers', $jobOfferData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'job_offer' => [
                    'id', 'title', 'description', 'company_name', 'location',
                    'contract_type', 'salary_min', 'salary_max', 'is_active', 'expires_at',
                    'user_id', 'created_at', 'updated_at'
                ],
            ]);

        $this->assertDatabaseHas('job_offers', [
            'title' => $jobOfferData['title'],
            'user_id' => $userData['user']->id,
        ]);
    }

    /**
     * Test that a candidate cannot create a job offer.
     */
    public function test_candidate_cannot_create_job_offer(): void
    {
        $userData = $this->createCandidateAndGetToken();
        $token = $userData['token'];

        $jobOfferData = [
            'title' => $this->faker->jobTitle,
            'description' => $this->faker->paragraph,
            'company_name' => $this->faker->company,
            'contract_type' => 'CDI',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/job-offers', $jobOfferData);

        $response->assertStatus(403);
    }

    /**
     * Test that a recruiter can update their own job offer.
     */
    public function test_recruiter_can_update_own_job_offer(): void
    {
        $userData = $this->createRecruiterAndGetToken();
        $user = $userData['user'];
        $token = $userData['token'];

        $jobOffer = JobOffer::factory()->create([
            'user_id' => $user->id,
        ]);

        $updatedData = [
            'title' => 'Updated Job Title',
            'description' => 'Updated job description with new details',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->putJson("/api/job-offers/{$jobOffer->id}", $updatedData);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Job offer updated successfully',
            ]);

        $this->assertDatabaseHas('job_offers', [
            'id' => $jobOffer->id,
            'title' => 'Updated Job Title',
            'description' => 'Updated job description with new details',
        ]);
    }

    /**
     * Test that a recruiter cannot update another recruiter's job offer.
     */
    public function test_recruiter_cannot_update_others_job_offer(): void
    {
        // Create first recruiter with job offer
        $recruiter1 = User::factory()->create(['role' => 'recruiter']);
        $jobOffer = JobOffer::factory()->create([
            'user_id' => $recruiter1->id,
        ]);

        // Create second recruiter to attempt update
        $userData = $this->createRecruiterAndGetToken();
        $token = $userData['token'];

        $updatedData = [
            'title' => 'Unauthorized Update Attempt',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->putJson("/api/job-offers/{$jobOffer->id}", $updatedData);

        $response->assertStatus(403);

        $this->assertDatabaseMissing('job_offers', [
            'id' => $jobOffer->id,
            'title' => 'Unauthorized Update Attempt',
        ]);
    }

    /**
     * Test that a recruiter can delete their own job offer.
     */
    public function test_recruiter_can_delete_own_job_offer(): void
    {
        $userData = $this->createRecruiterAndGetToken();
        $user = $userData['user'];
        $token = $userData['token'];

        $jobOffer = JobOffer::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->deleteJson("/api/job-offers/{$jobOffer->id}");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Job offer deleted successfully',
            ]);

        $this->assertDatabaseMissing('job_offers', [
            'id' => $jobOffer->id,
        ]);
    }

    /**
     * Test that a candidate can view active job offers.
     */
    public function test_candidate_can_view_active_job_offers(): void
    {
        // Create some job offers
        JobOffer::factory(3)->create(['is_active' => true]);
        JobOffer::factory(2)->create(['is_active' => false]);

        $userData = $this->createCandidateAndGetToken();
        $token = $userData['token'];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/job-offers');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'job_offers' => [
                    '*' => [
                        'id', 'title', 'description', 'company_name', 'is_active'
                    ]
                ]
            ]);

        // Should only return active job offers for candidates
        $this->assertEquals(3, count($response->json('job_offers')));
    }

    /**
     * Test that a recruiter can view their own job offers (both active and inactive).
     */
    public function test_recruiter_can_view_own_job_offers(): void
    {
        $userData = $this->createRecruiterAndGetToken();
        $user = $userData['user'];
        $token = $userData['token'];

        // Create job offers for this recruiter
        JobOffer::factory(2)->create([
            'user_id' => $user->id,
            'is_active' => true
        ]);
        
        JobOffer::factory(1)->create([
            'user_id' => $user->id,
            'is_active' => false
        ]);

        // Create job offers for other recruiters
        JobOffer::factory(3)->create(['is_active' => true]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/job-offers');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'job_offers' => [
                    '*' => [
                        'id', 'title', 'description', 'company_name', 'is_active'
                    ]
                ]
            ]);

        // Should only return this recruiter's job offers (both active and inactive)
        $this->assertEquals(3, count($response->json('job_offers')));
    }

    /**
     * Test that a recruiter can get statistics for their job offers.
     */
    public function test_recruiter_can_get_job_offer_statistics(): void
    {
        $userData = $this->createRecruiterAndGetToken();
        $user = $userData['user'];
        $token = $userData['token'];

        // Create job offers for this recruiter
        JobOffer::factory(3)->create([
            'user_id' => $user->id,
            'is_active' => true
        ]);
        
        JobOffer::factory(1)->create([
            'user_id' => $user->id,
            'is_active' => false
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/job-offers/statistics');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'statistics' => [
                    'total_offers',
                    'active_offers',
                    'expired_offers',
                ]
            ]);

        $this->assertEquals(4, $response->json('statistics.total_offers'));
        $this->assertEquals(3, $response->json('statistics.active_offers'));
    }
}