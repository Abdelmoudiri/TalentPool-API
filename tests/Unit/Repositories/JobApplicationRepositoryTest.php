<?php

namespace Tests\Unit\Repositories;

use Tests\TestCase;
use App\Models\User;
use App\Models\JobOffer;
use App\Models\JobApplication;
use App\Repositories\JobApplicationRepository;
use Illuminate\Foundation\Testing\WithFaker;

class JobApplicationRepositoryTest extends TestCase
{
    use WithFaker;

    protected JobApplicationRepository $repository;
    protected User $recruiter;
    protected User $candidate;
    protected JobOffer $jobOffer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new JobApplicationRepository();
        
        // Create test users and job offer
        $this->recruiter = User::factory()->create(['role' => 'recruiter']);
        $this->candidate = User::factory()->create(['role' => 'candidate']);
        $this->jobOffer = JobOffer::factory()->create([
            'user_id' => $this->recruiter->id,
            'is_active' => true,
        ]);
    }

    /** @test */
    public function it_can_create_a_job_application()
    {
        $applicationData = [
            'user_id' => $this->candidate->id,
            'job_offer_id' => $this->jobOffer->id,
            'cover_letter' => 'I am very interested in this position',
            'cv_path' => 'storage/cvs/sample_cv.pdf',
            'status' => 'pending'
        ];

        $application = $this->repository->create($applicationData);

        $this->assertNotNull($application->id);
        $this->assertEquals($this->candidate->id, $application->user_id);
        $this->assertEquals($this->jobOffer->id, $application->job_offer_id);
        $this->assertEquals('pending', $application->status);
        
        $this->assertDatabaseHas('job_applications', [
            'id' => $application->id,
            'user_id' => $this->candidate->id,
            'job_offer_id' => $this->jobOffer->id,
        ]);
    }

    /** @test */
    public function it_can_find_application_by_id()
    {
        $application = JobApplication::factory()->create([
            'user_id' => $this->candidate->id,
            'job_offer_id' => $this->jobOffer->id,
        ]);

        $found = $this->repository->findById($application->id);

        $this->assertEquals($application->id, $found->id);
        $this->assertEquals($this->candidate->id, $found->user_id);
        $this->assertEquals($this->jobOffer->id, $found->job_offer_id);
    }

    /** @test */
    public function it_can_get_applications_by_job_offer()
    {
        // Create multiple applications for the same job offer
        JobApplication::factory(3)->create([
            'job_offer_id' => $this->jobOffer->id,
        ]);

        $applications = $this->repository->getByJobOfferId($this->jobOffer->id);

        $this->assertCount(3, $applications);
        foreach ($applications as $application) {
            $this->assertEquals($this->jobOffer->id, $application->job_offer_id);
        }
    }

    /** @test */
    public function it_can_get_applications_by_candidate_id()
    {
        // Create multiple applications for the same candidate
        JobApplication::factory(2)->create([
            'user_id' => $this->candidate->id,
        ]);

        // Create applications for another candidate
        $anotherCandidate = User::factory()->create(['role' => 'candidate']);
        JobApplication::factory(1)->create([
            'user_id' => $anotherCandidate->id,
        ]);

        $applications = $this->repository->getByCandidateId($this->candidate->id);

        $this->assertCount(2, $applications);
        foreach ($applications as $application) {
            $this->assertEquals($this->candidate->id, $application->user_id);
        }
    }

    /** @test */
    public function it_can_update_application_status()
    {
        $application = JobApplication::factory()->create([
            'user_id' => $this->candidate->id,
            'job_offer_id' => $this->jobOffer->id,
            'status' => 'pending',
        ]);

        $updatedApplication = $this->repository->updateStatus(
            $application->id, 
            'reviewing',
            'Candidate has good experience'
        );

        $this->assertEquals('reviewing', $updatedApplication->status);
        $this->assertEquals('Candidate has good experience', $updatedApplication->recruiter_notes);
        
        $this->assertDatabaseHas('job_applications', [
            'id' => $application->id,
            'status' => 'reviewing',
            'recruiter_notes' => 'Candidate has good experience',
        ]);
    }

    /** @test */
    public function it_can_check_if_candidate_already_applied()
    {
        // Create an application
        $application = JobApplication::factory()->create([
            'user_id' => $this->candidate->id,
            'job_offer_id' => $this->jobOffer->id,
        ]);

        $hasApplied = $this->repository->candidateHasApplied(
            $this->candidate->id,
            $this->jobOffer->id
        );

        $this->assertTrue($hasApplied);

        // Check with a job offer they haven't applied to
        $anotherJobOffer = JobOffer::factory()->create();
        $hasApplied = $this->repository->candidateHasApplied(
            $this->candidate->id,
            $anotherJobOffer->id
        );

        $this->assertFalse($hasApplied);
    }

    /** @test */
    public function it_can_delete_an_application()
    {
        $application = JobApplication::factory()->create([
            'user_id' => $this->candidate->id,
            'job_offer_id' => $this->jobOffer->id,
        ]);

        $this->repository->delete($application->id);

        $this->assertDatabaseMissing('job_applications', [
            'id' => $application->id,
        ]);
    }

    /** @test */
    public function it_can_get_applications_with_candidate_details()
    {
        // Create applications with different candidates
        $application = JobApplication::factory()->create([
            'job_offer_id' => $this->jobOffer->id,
            'user_id' => $this->candidate->id,
        ]);

        $applications = $this->repository->getApplicationsWithCandidateDetails($this->jobOffer->id);

        $this->assertCount(1, $applications);
        $this->assertEquals($application->id, $applications[0]->id);
        $this->assertArrayHasKey('candidate', $applications[0]->toArray());
        $this->assertEquals($this->candidate->id, $applications[0]->candidate->id);
        $this->assertEquals($this->candidate->name, $applications[0]->candidate->name);
    }

    /** @test */
    public function it_can_get_applications_with_job_offer_details()
    {
        // Create applications for different job offers
        $application = JobApplication::factory()->create([
            'job_offer_id' => $this->jobOffer->id,
            'user_id' => $this->candidate->id,
        ]);

        $applications = $this->repository->getApplicationsWithJobOfferDetails($this->candidate->id);

        $this->assertCount(1, $applications);
        $this->assertEquals($application->id, $applications[0]->id);
        $this->assertArrayHasKey('job_offer', $applications[0]->toArray());
        $this->assertEquals($this->jobOffer->id, $applications[0]->job_offer->id);
        $this->assertEquals($this->jobOffer->title, $applications[0]->job_offer->title);
    }

    /** @test */
    public function it_can_get_application_statistics_for_candidate()
    {
        // Create applications with different statuses
        JobApplication::factory()->create([
            'user_id' => $this->candidate->id,
            'status' => 'pending',
        ]);
        
        JobApplication::factory()->create([
            'user_id' => $this->candidate->id,
            'status' => 'reviewing',
        ]);
        
        JobApplication::factory()->create([
            'user_id' => $this->candidate->id,
            'status' => 'accepted',
        ]);

        $stats = $this->repository->getStatisticsForCandidate($this->candidate->id);

        $this->assertEquals(3, $stats['total_applications']);
        $this->assertEquals(1, $stats['status_counts']['pending']);
        $this->assertEquals(1, $stats['status_counts']['reviewing']);
        $this->assertEquals(1, $stats['status_counts']['accepted']);
        $this->assertEquals(0, $stats['status_counts']['rejected']);
    }

    /** @test */
    public function it_can_get_application_statistics_for_recruiter()
    {
        // Create applications for job offers from this recruiter
        $jobOffer1 = JobOffer::factory()->create(['user_id' => $this->recruiter->id]);
        $jobOffer2 = JobOffer::factory()->create(['user_id' => $this->recruiter->id]);
        
        JobApplication::factory()->create([
            'job_offer_id' => $jobOffer1->id,
            'status' => 'pending',
        ]);
        
        JobApplication::factory()->create([
            'job_offer_id' => $jobOffer1->id,
            'status' => 'accepted',
        ]);
        
        JobApplication::factory()->create([
            'job_offer_id' => $jobOffer2->id,
            'status' => 'reviewing',
        ]);

        $stats = $this->repository->getStatisticsForRecruiter($this->recruiter->id);

        $this->assertEquals(3, $stats['total_applications']);
        $this->assertEquals(1, $stats['status_counts']['pending']);
        $this->assertEquals(1, $stats['status_counts']['reviewing']);
        $this->assertEquals(1, $stats['status_counts']['accepted']);
        $this->assertEquals(0, $stats['status_counts']['rejected']);
        $this->assertEquals(2, $stats['applications_per_job'][$jobOffer1->id]);
        $this->assertEquals(1, $stats['applications_per_job'][$jobOffer2->id]);
    }
}