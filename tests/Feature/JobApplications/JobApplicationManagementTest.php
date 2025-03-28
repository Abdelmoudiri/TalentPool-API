<?php

namespace Tests\Feature\JobApplications;

use Tests\TestCase;
use App\Models\User;
use App\Models\JobOffer;
use App\Models\JobApplication;
use Illuminate\Http\UploadedFile;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Storage;

class JobApplicationManagementTest extends TestCase
{
    use WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    /**
     * Test a candidate can apply for a job.
     */
    public function test_candidate_can_apply_for_job(): void
    {
        $userData = $this->createCandidateAndGetToken();
        $token = $userData['token'];
        $candidate = $userData['user'];

        // Create a recruiter and job offer
        $recruiter = User::factory()->create(['role' => 'recruiter']);
        $jobOffer = JobOffer::factory()->create([
            'user_id' => $recruiter->id,
            'is_active' => true,
        ]);

        $applicationData = [
            'cover_letter' => $this->faker->paragraph,
            'cv_file' => UploadedFile::fake()->create('resume.pdf', 500),
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson("/api/job-offers/{$jobOffer->id}/apply", $applicationData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'application' => [
                    'id', 'user_id', 'job_offer_id', 'status', 'cover_letter', 'cv_path',
                    'created_at', 'updated_at'
                ],
            ]);

        $this->assertDatabaseHas('job_applications', [
            'user_id' => $candidate->id,
            'job_offer_id' => $jobOffer->id,
            'status' => 'pending',
        ]);

        // Verify file was stored
        $storedFilePath = $response->json('application.cv_path');
        $this->assertNotNull($storedFilePath);
        Storage::disk('public')->assertExists(str_replace('storage/', '', $storedFilePath));
    }

    /**
     * Test a candidate cannot apply twice for the same job.
     */
    public function test_candidate_cannot_apply_twice_for_same_job(): void
    {
        $userData = $this->createCandidateAndGetToken();
        $token = $userData['token'];
        $candidate = $userData['user'];

        // Create a recruiter and job offer
        $recruiter = User::factory()->create(['role' => 'recruiter']);
        $jobOffer = JobOffer::factory()->create([
            'user_id' => $recruiter->id,
            'is_active' => true,
        ]);

        // Create an existing application
        JobApplication::factory()->create([
            'user_id' => $candidate->id,
            'job_offer_id' => $jobOffer->id,
            'status' => 'pending',
        ]);

        $applicationData = [
            'cover_letter' => $this->faker->paragraph,
            'cv_file' => UploadedFile::fake()->create('resume.pdf', 500),
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson("/api/job-offers/{$jobOffer->id}/apply", $applicationData);

        $response->assertStatus(422);
    }

    /**
     * Test that a recruiter cannot apply for a job.
     */
    public function test_recruiter_cannot_apply_for_job(): void
    {
        $userData = $this->createRecruiterAndGetToken();
        $token = $userData['token'];

        // Create another recruiter and job offer
        $otherRecruiter = User::factory()->create(['role' => 'recruiter']);
        $jobOffer = JobOffer::factory()->create([
            'user_id' => $otherRecruiter->id,
            'is_active' => true,
        ]);

        $applicationData = [
            'cover_letter' => $this->faker->paragraph,
            'cv_file' => UploadedFile::fake()->create('resume.pdf', 500),
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson("/api/job-offers/{$jobOffer->id}/apply", $applicationData);

        $response->assertStatus(403);
    }

    /**
     * Test a candidate can withdraw their application.
     */
    public function test_candidate_can_withdraw_their_application(): void
    {
        $userData = $this->createCandidateAndGetToken();
        $token = $userData['token'];
        $candidate = $userData['user'];

        // Create a recruiter and job offer
        $recruiter = User::factory()->create(['role' => 'recruiter']);
        $jobOffer = JobOffer::factory()->create([
            'user_id' => $recruiter->id,
            'is_active' => true,
        ]);

        // Create an application
        $application = JobApplication::factory()->create([
            'user_id' => $candidate->id,
            'job_offer_id' => $jobOffer->id,
            'status' => 'pending',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->deleteJson("/api/applications/{$application->id}");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Application withdrawn successfully',
            ]);

        $this->assertDatabaseMissing('job_applications', [
            'id' => $application->id,
        ]);
    }

    /**
     * Test a candidate cannot withdraw another candidate's application.
     */
    public function test_candidate_cannot_withdraw_others_application(): void
    {
        // Create a candidate with an application
        $candidate1 = User::factory()->create(['role' => 'candidate']);
        $recruiter = User::factory()->create(['role' => 'recruiter']);
        $jobOffer = JobOffer::factory()->create([
            'user_id' => $recruiter->id,
            'is_active' => true,
        ]);

        $application = JobApplication::factory()->create([
            'user_id' => $candidate1->id,
            'job_offer_id' => $jobOffer->id,
            'status' => 'pending',
        ]);

        // Create a second candidate to attempt withdrawal
        $userData = $this->createCandidateAndGetToken();
        $token = $userData['token'];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->deleteJson("/api/applications/{$application->id}");

        $response->assertStatus(403);

        // Verify application still exists
        $this->assertDatabaseHas('job_applications', [
            'id' => $application->id,
        ]);
    }

    /**
     * Test a recruiter can view applications for their job offer.
     */
    public function test_recruiter_can_view_applications_for_their_job_offer(): void
    {
        $userData = $this->createRecruiterAndGetToken();
        $token = $userData['token'];
        $recruiter = $userData['user'];

        // Create a job offer for this recruiter
        $jobOffer = JobOffer::factory()->create([
            'user_id' => $recruiter->id,
        ]);

        // Create multiple candidates and applications
        for ($i = 0; $i < 3; $i++) {
            $candidate = User::factory()->create(['role' => 'candidate']);
            JobApplication::factory()->create([
                'user_id' => $candidate->id,
                'job_offer_id' => $jobOffer->id,
            ]);
        }

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson("/api/job-offers/{$jobOffer->id}/applications");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'applications' => [
                    '*' => [
                        'id', 'user_id', 'job_offer_id', 'status', 'cover_letter',
                        'created_at', 'updated_at', 'candidate' => [
                            'id', 'name', 'email'
                        ]
                    ]
                ]
            ]);

        // Should return 3 applications
        $this->assertEquals(3, count($response->json('applications')));
    }

    /**
     * Test a recruiter cannot view applications for another recruiter's job offer.
     */
    public function test_recruiter_cannot_view_applications_for_others_job_offer(): void
    {
        // Create a recruiter with a job offer and applications
        $recruiter1 = User::factory()->create(['role' => 'recruiter']);
        $jobOffer = JobOffer::factory()->create([
            'user_id' => $recruiter1->id,
        ]);

        $candidate = User::factory()->create(['role' => 'candidate']);
        JobApplication::factory()->create([
            'user_id' => $candidate->id,
            'job_offer_id' => $jobOffer->id,
        ]);

        // Create a second recruiter to attempt viewing
        $userData = $this->createRecruiterAndGetToken();
        $token = $userData['token'];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson("/api/job-offers/{$jobOffer->id}/applications");

        $response->assertStatus(403);
    }

    /**
     * Test a recruiter can update application status.
     */
    public function test_recruiter_can_update_application_status(): void
    {
        $userData = $this->createRecruiterAndGetToken();
        $token = $userData['token'];
        $recruiter = $userData['user'];

        // Create a job offer for this recruiter
        $jobOffer = JobOffer::factory()->create([
            'user_id' => $recruiter->id,
        ]);

        // Create a candidate and application
        $candidate = User::factory()->create(['role' => 'candidate']);
        $application = JobApplication::factory()->create([
            'user_id' => $candidate->id,
            'job_offer_id' => $jobOffer->id,
            'status' => 'pending',
        ]);

        $updateData = [
            'status' => 'accepted',
            'recruiter_notes' => 'Great candidate, perfect fit for the position.',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->putJson("/api/applications/{$application->id}/status", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Application status updated successfully',
                'application' => [
                    'status' => 'accepted',
                    'recruiter_notes' => 'Great candidate, perfect fit for the position.',
                ]
            ]);

        $this->assertDatabaseHas('job_applications', [
            'id' => $application->id,
            'status' => 'accepted',
            'recruiter_notes' => 'Great candidate, perfect fit for the position.',
        ]);
    }

    /**
     * Test a candidate can view their own applications.
     */
    public function test_candidate_can_view_their_own_applications(): void
    {
        $userData = $this->createCandidateAndGetToken();
        $token = $userData['token'];
        $candidate = $userData['user'];

        // Create recruiters and job offers
        $recruiter1 = User::factory()->create(['role' => 'recruiter']);
        $jobOffer1 = JobOffer::factory()->create([
            'user_id' => $recruiter1->id,
            'is_active' => true,
        ]);

        $recruiter2 = User::factory()->create(['role' => 'recruiter']);
        $jobOffer2 = JobOffer::factory()->create([
            'user_id' => $recruiter2->id,
            'is_active' => true,
        ]);

        // Create applications for this candidate
        JobApplication::factory()->create([
            'user_id' => $candidate->id,
            'job_offer_id' => $jobOffer1->id,
            'status' => 'pending',
        ]);

        JobApplication::factory()->create([
            'user_id' => $candidate->id,
            'job_offer_id' => $jobOffer2->id,
            'status' => 'reviewing',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson("/api/applications");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'applications' => [
                    '*' => [
                        'id', 'user_id', 'job_offer_id', 'status', 'cover_letter',
                        'created_at', 'updated_at', 'job_offer' => [
                            'id', 'title', 'company_name'
                        ]
                    ]
                ]
            ]);

        // Should return 2 applications
        $this->assertEquals(2, count($response->json('applications')));
    }

    /**
     * Test a candidate can view application statistics.
     */
    public function test_candidate_can_view_application_statistics(): void
    {
        $userData = $this->createCandidateAndGetToken();
        $token = $userData['token'];
        $candidate = $userData['user'];

        // Create recruiters and job offers
        $recruiter = User::factory()->create(['role' => 'recruiter']);
        $jobOffer1 = JobOffer::factory()->create([
            'user_id' => $recruiter->id,
            'is_active' => true,
        ]);
        $jobOffer2 = JobOffer::factory()->create([
            'user_id' => $recruiter->id,
            'is_active' => true,
        ]);
        $jobOffer3 = JobOffer::factory()->create([
            'user_id' => $recruiter->id,
            'is_active' => true,
        ]);

        // Create applications with different statuses
        JobApplication::factory()->create([
            'user_id' => $candidate->id,
            'job_offer_id' => $jobOffer1->id,
            'status' => 'pending',
        ]);

        JobApplication::factory()->create([
            'user_id' => $candidate->id,
            'job_offer_id' => $jobOffer2->id,
            'status' => 'reviewing',
        ]);

        JobApplication::factory()->create([
            'user_id' => $candidate->id,
            'job_offer_id' => $jobOffer3->id,
            'status' => 'accepted',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson("/api/applications/statistics");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'statistics' => [
                    'total_applications',
                    'status_counts' => [
                        'pending', 'reviewing', 'accepted', 'rejected'
                    ]
                ]
            ]);

        $this->assertEquals(3, $response->json('statistics.total_applications'));
        $this->assertEquals(1, $response->json('statistics.status_counts.pending'));
        $this->assertEquals(1, $response->json('statistics.status_counts.reviewing'));
        $this->assertEquals(1, $response->json('statistics.status_counts.accepted'));
        $this->assertEquals(0, $response->json('statistics.status_counts.rejected'));
    }
}