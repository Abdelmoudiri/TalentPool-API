<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Models\User;
use App\Models\JobOffer;
use App\Services\JobOfferService;
use App\Repositories\Interfaces\JobOfferRepositoryInterface;
use Illuminate\Foundation\Testing\WithFaker;
use Mockery;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;

class JobOfferServiceTest extends TestCase
{
    use WithFaker;

    protected $mockRepository;
    protected JobOfferService $service;
    protected User $recruiter;
    protected User $candidate;
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create users with different roles
        $this->recruiter = User::factory()->create(['role' => 'recruiter']);
        $this->candidate = User::factory()->create(['role' => 'candidate']);
        $this->admin = User::factory()->create(['role' => 'admin']);
        
        // Create mock repository
        $this->mockRepository = Mockery::mock(JobOfferRepositoryInterface::class);
        
        // Create service with mock repository
        $this->service = new JobOfferService($this->mockRepository);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_allows_recruiters_to_create_job_offers()
    {
        // Mock Auth facade to return recruiter
        Auth::shouldReceive('user')->andReturn($this->recruiter);
        
        $jobOfferData = [
            'title' => 'Software Engineer',
            'description' => 'Join our team',
            'company_name' => 'Tech Inc',
        ];
        
        // Set repository expectation
        $expectedData = array_merge($jobOfferData, ['user_id' => $this->recruiter->id]);
        $newJobOffer = new JobOffer($expectedData);
        $newJobOffer->id = 1;
        
        $this->mockRepository->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function ($data) use ($expectedData) {
                return $data['user_id'] == $expectedData['user_id'] && 
                       $data['title'] == $expectedData['title'];
            }))
            ->andReturn($newJobOffer);
        
        // Execute service method
        $result = $this->service->createJobOffer($jobOfferData);
        
        // Assertions
        $this->assertEquals(1, $result->id);
        $this->assertEquals('Software Engineer', $result->title);
        $this->assertEquals($this->recruiter->id, $result->user_id);
    }

    /** @test */
    public function it_prevents_candidates_from_creating_job_offers()
    {
        // Mock Auth facade to return candidate
        Auth::shouldReceive('user')->andReturn($this->candidate);
        
        $jobOfferData = [
            'title' => 'Software Engineer',
            'description' => 'Join our team',
            'company_name' => 'Tech Inc',
        ];
        
        // The repository should not be called
        $this->mockRepository->shouldNotReceive('create');
        
        // Expect an authorization exception
        $this->expectException(AuthorizationException::class);
        
        // Execute service method
        $this->service->createJobOffer($jobOfferData);
    }

    /** @test */
    public function it_allows_recruiters_to_update_their_own_job_offers()
    {
        // Create job offer owned by the recruiter
        $jobOffer = new JobOffer([
            'id' => 1,
            'title' => 'Original Title',
            'user_id' => $this->recruiter->id
        ]);
        
        // Mock Auth facade to return recruiter
        Auth::shouldReceive('user')->andReturn($this->recruiter);
        
        // Set repository expectations
        $this->mockRepository->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn($jobOffer);
        
        $this->mockRepository->shouldReceive('update')
            ->once()
            ->with(1, ['title' => 'Updated Title'])
            ->andReturn(new JobOffer([
                'id' => 1,
                'title' => 'Updated Title',
                'user_id' => $this->recruiter->id
            ]));
        
        // Execute service method
        $result = $this->service->updateJobOffer(1, ['title' => 'Updated Title']);
        
        // Assertions
        $this->assertEquals('Updated Title', $result->title);
    }

    /** @test */
    public function it_prevents_recruiters_from_updating_others_job_offers()
    {
        // Create job offer owned by another recruiter
        $anotherRecruiter = User::factory()->create(['role' => 'recruiter']);
        $jobOffer = new JobOffer([
            'id' => 1,
            'title' => 'Original Title',
            'user_id' => $anotherRecruiter->id
        ]);
        
        // Mock Auth facade to return our test recruiter
        Auth::shouldReceive('user')->andReturn($this->recruiter);
        
        // Set repository expectations
        $this->mockRepository->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn($jobOffer);
        
        // The update method should not be called
        $this->mockRepository->shouldNotReceive('update');
        
        // Expect an authorization exception
        $this->expectException(AuthorizationException::class);
        
        // Execute service method
        $this->service->updateJobOffer(1, ['title' => 'Updated Title']);
    }

    /** @test */
    public function it_allows_recruiters_to_delete_their_own_job_offers()
    {
        // Create job offer owned by the recruiter
        $jobOffer = new JobOffer([
            'id' => 1,
            'title' => 'Job Title',
            'user_id' => $this->recruiter->id
        ]);
        
        // Mock Auth facade to return recruiter
        Auth::shouldReceive('user')->andReturn($this->recruiter);
        
        // Set repository expectations
        $this->mockRepository->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn($jobOffer);
        
        $this->mockRepository->shouldReceive('delete')
            ->once()
            ->with(1)
            ->andReturn(true);
        
        // Execute service method
        $result = $this->service->deleteJobOffer(1);
        
        // Assertions
        $this->assertTrue($result);
    }

    /** @test */
    public function it_returns_different_job_offers_based_on_user_role()
    {
        // Create mock job offers
        $recruiterJobOffers = collect([
            new JobOffer(['id' => 1, 'title' => 'Job 1', 'user_id' => $this->recruiter->id]),
            new JobOffer(['id' => 2, 'title' => 'Job 2', 'user_id' => $this->recruiter->id]),
        ]);
        
        $activeJobOffers = collect([
            new JobOffer(['id' => 1, 'title' => 'Job 1', 'is_active' => true]),
            new JobOffer(['id' => 3, 'title' => 'Job 3', 'is_active' => true]),
        ]);
        
        $allJobOffers = collect([
            new JobOffer(['id' => 1, 'title' => 'Job 1']),
            new JobOffer(['id' => 2, 'title' => 'Job 2']),
            new JobOffer(['id' => 3, 'title' => 'Job 3']),
        ]);
        
        // Set repository expectations for recruiter
        $this->mockRepository->shouldReceive('getByRecruiterId')
            ->once()
            ->with($this->recruiter->id)
            ->andReturn($recruiterJobOffers);
        
        // Set repository expectations for candidate
        $this->mockRepository->shouldReceive('getActiveOffers')
            ->once()
            ->andReturn($activeJobOffers);
        
        // Set repository expectations for admin
        $this->mockRepository->shouldReceive('getAll')
            ->once()
            ->andReturn($allJobOffers);
        
        // Test recruiter view
        Auth::shouldReceive('user')->andReturn($this->recruiter);
        $recruiterResult = $this->service->getAllJobOffers();
        $this->assertEquals(2, $recruiterResult->count());
        $this->assertEquals('Job 1', $recruiterResult[0]->title);
        
        // Test candidate view
        Auth::shouldReceive('user')->andReturn($this->candidate);
        $candidateResult = $this->service->getAllJobOffers();
        $this->assertEquals(2, $candidateResult->count());
        $this->assertTrue($candidateResult[0]->is_active);
        
        // Test admin view
        Auth::shouldReceive('user')->andReturn($this->admin);
        $adminResult = $this->service->getAllJobOffers();
        $this->assertEquals(3, $adminResult->count());
    }

    /** @test */
    public function it_calculates_statistics_for_recruiters()
    {
        // Mock statistics data
        $stats = [
            'total_offers' => 5,
            'active_offers' => 3,
            'expired_offers' => 2,
        ];
        
        // Mock Auth facade to return recruiter
        Auth::shouldReceive('user')->andReturn($this->recruiter);
        
        // Set repository expectations
        $this->mockRepository->shouldReceive('getStatisticsForRecruiter')
            ->once()
            ->with($this->recruiter->id)
            ->andReturn($stats);
        
        // Execute service method
        $result = $this->service->getStatistics();
        
        // Assertions
        $this->assertEquals($stats, $result);
        $this->assertEquals(5, $result['total_offers']);
        $this->assertEquals(3, $result['active_offers']);
    }

    /** @test */
    public function it_throws_error_if_job_offer_not_found()
    {
        // Mock Auth facade to return recruiter
        Auth::shouldReceive('user')->andReturn($this->recruiter);
        
        // Set repository expectation to return null (job offer not found)
        $this->mockRepository->shouldReceive('findById')
            ->once()
            ->with(999)
            ->andReturn(null);
        
        // Expect an exception
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        
        // Execute service method
        $this->service->updateJobOffer(999, ['title' => 'Updated Title']);
    }
}