<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Models\User;
use App\Models\JobOffer;
use App\Models\JobApplication;
use App\Services\JobApplicationService;
use App\Repositories\Interfaces\JobApplicationRepositoryInterface;
use App\Repositories\Interfaces\JobOfferRepositoryInterface;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;

class JobApplicationServiceTest extends TestCase
{
    use WithFaker;

    protected $mockAppRepository;
    protected $mockJobRepository;
    protected JobApplicationService $service;
    protected User $recruiter;
    protected User $candidate;
    protected User $admin;
    protected JobOffer $jobOffer;

    protected function setUp(): void
    {
        parent::setUp();
        
        Storage::fake('public');
        Notification::fake();
        
        // Create users with different roles
        $this->recruiter = User::factory()->create(['role' => 'recruiter']);
        $this->candidate = User::factory()->create(['role' => 'candidate']);
        $this->admin = User::factory()->create(['role' => 'admin']);
        
        // Create a job offer
        $this->jobOffer = new JobOffer([
            'id' => 1,
            'title' => 'Software Developer',
            'user_id' => $this->recruiter->id,
            'is_active' => true
        ]);
        
        // Create mock repositories
        $this->mockAppRepository = Mockery::mock(JobApplicationRepositoryInterface::class);
        $this->mockJobRepository = Mockery::mock(JobOfferRepositoryInterface::class);
        
        // Create service with mock repositories
        $this->service = new JobApplicationService(
            $this->mockAppRepository,
            $this->mockJobRepository
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_allows_candidates_to_apply_for_jobs()
    {
        // Mock Auth facade to return candidate
        Auth::shouldReceive('user')->andReturn($this->candidate);
        
        // Mock file upload
        $file = UploadedFile::fake()->create('resume.pdf', 500);
        
        $applicationData = [
            'cover_letter' => 'I am very interested in this position',
            'cv_file' => $file,
        ];
        
        // Set repository expectations
        $this->mockJobRepository->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn($this->jobOffer);
        
        $this->mockAppRepository->shouldReceive('candidateHasApplied')
            ->once()
            ->with($this->candidate->id, 1)
            ->andReturn(false);
        
        // Mock successful application creation
        $this->mockAppRepository->shouldReceive('create')
            ->once()
            ->andReturn(new JobApplication([
                'id' => 1,
                'user_id' => $this->candidate->id,
                'job_offer_id' => 1,
                'status' => 'pending',
                'cover_letter' => 'I am very interested in this position',
                'cv_path' => 'storage/cvs/resume.pdf'
            ]));
        
        // Execute service method
        $result = $this->service->applyForJob(1, $applicationData);
        
        // Assertions
        $this->assertEquals(1, $result->id);
        $this->assertEquals($this->candidate->id, $result->user_id);
        $this->assertEquals('pending', $result->status);
    }

    /** @test */
    public function it_prevents_candidates_from_applying_twice()
    {
        // Mock Auth facade to return candidate
        Auth::shouldReceive('user')->andReturn($this->candidate);
        
        // Mock file upload
        $file = UploadedFile::fake()->create('resume.pdf', 500);
        
        $applicationData = [
            'cover_letter' => 'I am very interested in this position',
            'cv_file' => $file,
        ];
        
        // Set repository expectations
        $this->mockJobRepository->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn($this->jobOffer);
        
        $this->mockAppRepository->shouldReceive('candidateHasApplied')
            ->once()
            ->with($this->candidate->id, 1)
            ->andReturn(true);
        
        // The create method should not be called
        $this->mockAppRepository->shouldNotReceive('create');
        
        // Expect an validation exception
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        
        // Execute service method
        $this->service->applyForJob(1, $applicationData);
    }

    /** @test */
    public function it_prevents_recruiters_from_applying_for_jobs()
    {
        // Mock Auth facade to return recruiter
        Auth::shouldReceive('user')->andReturn($this->recruiter);
        
        // Mock file upload
        $file = UploadedFile::fake()->create('resume.pdf', 500);
        
        $applicationData = [
            'cover_letter' => 'I am very interested in this position',
            'cv_file' => $file,
        ];
        
        // The repository methods should not be called
        $this->mockJobRepository->shouldNotReceive('findById');
        $this->mockAppRepository->shouldNotReceive('create');
        
        // Expect an authorization exception
        $this->expectException(AuthorizationException::class);
        
        // Execute service method
        $this->service->applyForJob(1, $applicationData);
    }

    /** @test */
    public function it_allows_candidates_to_withdraw_their_own_applications()
    {
        // Create application owned by the candidate
        $application = new JobApplication([
            'id' => 1,
            'user_id' => $this->candidate->id,
            'job_offer_id' => 1,
        ]);
        
        // Mock Auth facade to return candidate
        Auth::shouldReceive('user')->andReturn($this->candidate);
        
        // Set repository expectations
        $this->mockAppRepository->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn($application);
        
        $this->mockAppRepository->shouldReceive('delete')
            ->once()
            ->with(1)
            ->andReturn(true);
        
        // Execute service method
        $result = $this->service->withdrawApplication(1);
        
        // Assertions
        $this->assertTrue($result);
    }

    /** @test */
    public function it_prevents_candidates_from_withdrawing_others_applications()
    {
        // Create application owned by another candidate
        $anotherCandidate = User::factory()->create(['role' => 'candidate']);
        $application = new JobApplication([
            'id' => 1,
            'user_id' => $anotherCandidate->id,
            'job_offer_id' => 1,
        ]);
        
        // Mock Auth facade to return our test candidate
        Auth::shouldReceive('user')->andReturn($this->candidate);
        
        // Set repository expectations
        $this->mockAppRepository->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn($application);
        
        // The delete method should not be called
        $this->mockAppRepository->shouldNotReceive('delete');
        
        // Expect an authorization exception
        $this->expectException(AuthorizationException::class);
        
        // Execute service method
        $this->service->withdrawApplication(1);
    }

    /** @test */
    public function it_allows_recruiters_to_update_application_status_for_their_job_offers()
    {
        // Create application for a job offer owned by the recruiter
        $jobOffer = new JobOffer(['id' => 1, 'user_id' => $this->recruiter->id]);
        $application = new JobApplication([
            'id' => 1,
            'user_id' => $this->candidate->id,
            'job_offer_id' => 1,
            'status' => 'pending',
        ]);
        $application->jobOffer = $jobOffer;
        
        // Mock Auth facade to return recruiter
        Auth::shouldReceive('user')->andReturn($this->recruiter);
        
        // Set repository expectations
        $this->mockAppRepository->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn($application);
        
        $updatedApplication = clone $application;
        $updatedApplication->status = 'accepted';
        $updatedApplication->recruiter_notes = 'Great candidate';
        
        $this->mockAppRepository->shouldReceive('updateStatus')
            ->once()
            ->with(1, 'accepted', 'Great candidate')
            ->andReturn($updatedApplication);
        
        // Execute service method
        $result = $this->service->updateApplicationStatus(1, [
            'status' => 'accepted',
            'recruiter_notes' => 'Great candidate'
        ]);
        
        // Assertions
        $this->assertEquals('accepted', $result->status);
        $this->assertEquals('Great candidate', $result->recruiter_notes);
    }

    /** @test */
    public function it_prevents_recruiters_from_updating_applications_for_others_job_offers()
    {
        // Create application for a job offer owned by another recruiter
        $anotherRecruiter = User::factory()->create(['role' => 'recruiter']);
        $jobOffer = new JobOffer(['id' => 1, 'user_id' => $anotherRecruiter->id]);
        $application = new JobApplication([
            'id' => 1,
            'user_id' => $this->candidate->id,
            'job_offer_id' => 1,
            'status' => 'pending',
        ]);
        $application->jobOffer = $jobOffer;
        
        // Mock Auth facade to return our test recruiter
        Auth::shouldReceive('user')->andReturn($this->recruiter);
        
        // Set repository expectations
        $this->mockAppRepository->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn($application);
        
        // The updateStatus method should not be called
        $this->mockAppRepository->shouldNotReceive('updateStatus');
        
        // Expect an authorization exception
        $this->expectException(AuthorizationException::class);
        
        // Execute service method
        $this->service->updateApplicationStatus(1, [
            'status' => 'accepted',
            'recruiter_notes' => 'Great candidate'
        ]);
    }

    /** @test */
    public function it_prevents_candidates_from_updating_application_status()
    {
        // Mock Auth facade to return candidate
        Auth::shouldReceive('user')->andReturn($this->candidate);
        
        // The findById method should not be called
        $this->mockAppRepository->shouldNotReceive('findById');
        
        // Expect an authorization exception
        $this->expectException(AuthorizationException::class);
        
        // Execute service method
        $this->service->updateApplicationStatus(1, [
            'status' => 'accepted',
            'recruiter_notes' => 'Great candidate'
        ]);
    }

    /** @test */
    public function it_allows_recruiters_to_view_applications_for_their_job_offers()
    {
        // Create job offer owned by the recruiter
        $jobOffer = new JobOffer(['id' => 1, 'user_id' => $this->recruiter->id]);
        
        // Mock Auth facade to return recruiter
        Auth::shouldReceive('user')->andReturn($this->recruiter);
        
        // Set repository expectations
        $this->mockJobRepository->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn($jobOffer);
        
        $applications = collect([
            new JobApplication(['id' => 1, 'job_offer_id' => 1, 'user_id' => $this->candidate->id]),
            new JobApplication(['id' => 2, 'job_offer_id' => 1, 'user_id' => User::factory()->create()->id]),
        ]);
        
        $this->mockAppRepository->shouldReceive('getApplicationsWithCandidateDetails')
            ->once()
            ->with(1)
            ->andReturn($applications);
        
        // Execute service method
        $result = $this->service->getApplicationsForJobOffer(1);
        
        // Assertions
        $this->assertEquals(2, $result->count());
        $this->assertEquals(1, $result[0]->id);
    }

    /** @test */
    public function it_prevents_recruiters_from_viewing_applications_for_others_job_offers()
    {
        // Create job offer owned by another recruiter
        $anotherRecruiter = User::factory()->create(['role' => 'recruiter']);
        $jobOffer = new JobOffer(['id' => 1, 'user_id' => $anotherRecruiter->id]);
        
        // Mock Auth facade to return our test recruiter
        Auth::shouldReceive('user')->andReturn($this->recruiter);
        
        // Set repository expectations
        $this->mockJobRepository->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn($jobOffer);
        
        // The getApplicationsWithCandidateDetails method should not be called
        $this->mockAppRepository->shouldNotReceive('getApplicationsWithCandidateDetails');
        
        // Expect an authorization exception
        $this->expectException(AuthorizationException::class);
        
        // Execute service method
        $this->service->getApplicationsForJobOffer(1);
    }

    /** @test */
    public function it_allows_candidates_to_view_their_own_applications()
    {
        // Mock Auth facade to return candidate
        Auth::shouldReceive('user')->andReturn($this->candidate);
        
        // Set repository expectations
        $applications = collect([
            new JobApplication(['id' => 1, 'user_id' => $this->candidate->id, 'job_offer_id' => 1]),
            new JobApplication(['id' => 2, 'user_id' => $this->candidate->id, 'job_offer_id' => 2]),
        ]);
        
        $this->mockAppRepository->shouldReceive('getApplicationsWithJobOfferDetails')
            ->once()
            ->with($this->candidate->id)
            ->andReturn($applications);
        
        // Execute service method
        $result = $this->service->getCandidateApplications();
        
        // Assertions
        $this->assertEquals(2, $result->count());
        $this->assertEquals($this->candidate->id, $result[0]->user_id);
    }

    /** @test */
    public function it_generates_statistics_for_candidates()
    {
        // Mock Auth facade to return candidate
        Auth::shouldReceive('user')->andReturn($this->candidate);
        
        // Mock statistics data
        $stats = [
            'total_applications' => 5,
            'status_counts' => [
                'pending' => 2,
                'reviewing' => 1,
                'accepted' => 1,
                'rejected' => 1,
            ]
        ];
        
        // Set repository expectations
        $this->mockAppRepository->shouldReceive('getStatisticsForCandidate')
            ->once()
            ->with($this->candidate->id)
            ->andReturn($stats);
        
        // Execute service method
        $result = $this->service->getStatistics();
        
        // Assertions
        $this->assertEquals($stats, $result);
        $this->assertEquals(5, $result['total_applications']);
        $this->assertEquals(2, $result['status_counts']['pending']);
    }

    /** @test */
    public function it_generates_statistics_for_recruiters()
    {
        // Mock Auth facade to return recruiter
        Auth::shouldReceive('user')->andReturn($this->recruiter);
        
        // Mock statistics data
        $stats = [
            'total_applications' => 8,
            'status_counts' => [
                'pending' => 3,
                'reviewing' => 2,
                'accepted' => 2,
                'rejected' => 1,
            ],
            'applications_per_job' => [
                1 => 5,
                2 => 3,
            ]
        ];
        
        // Set repository expectations
        $this->mockAppRepository->shouldReceive('getStatisticsForRecruiter')
            ->once()
            ->with($this->recruiter->id)
            ->andReturn($stats);
        
        // Execute service method
        $result = $this->service->getStatistics();
        
        // Assertions
        $this->assertEquals($stats, $result);
        $this->assertEquals(8, $result['total_applications']);
        $this->assertEquals(5, $result['applications_per_job'][1]);
    }
}