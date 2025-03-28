<?php

namespace Tests\Unit\Repositories;

use Tests\TestCase;
use App\Models\User;
use App\Models\JobOffer;
use App\Repositories\JobOfferRepository;
use Illuminate\Foundation\Testing\WithFaker;

class JobOfferRepositoryTest extends TestCase
{
    use WithFaker;

    protected JobOfferRepository $repository;
    protected User $recruiter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new JobOfferRepository();
        $this->recruiter = User::factory()->create(['role' => 'recruiter']);
    }

    /** @test */
    public function it_can_get_all_job_offers()
    {
        // Create job offers
        JobOffer::factory(3)->create();

        $jobOffers = $this->repository->getAll();

        $this->assertCount(3, $jobOffers);
        $this->assertInstanceOf(JobOffer::class, $jobOffers->first());
    }

    /** @test */
    public function it_can_get_active_job_offers()
    {
        // Create active and inactive job offers
        JobOffer::factory(2)->create(['is_active' => true]);
        JobOffer::factory(1)->create(['is_active' => false]);

        $activeJobOffers = $this->repository->getActiveOffers();

        $this->assertCount(2, $activeJobOffers);
        foreach ($activeJobOffers as $offer) {
            $this->assertTrue($offer->is_active);
        }
    }

    /** @test */
    public function it_can_get_job_offers_by_recruiter()
    {
        // Create job offers for our test recruiter
        JobOffer::factory(2)->create(['user_id' => $this->recruiter->id]);
        
        // Create job offers for another recruiter
        $anotherRecruiter = User::factory()->create(['role' => 'recruiter']);
        JobOffer::factory(3)->create(['user_id' => $anotherRecruiter->id]);

        $recruiterJobOffers = $this->repository->getByRecruiterId($this->recruiter->id);

        $this->assertCount(2, $recruiterJobOffers);
        foreach ($recruiterJobOffers as $offer) {
            $this->assertEquals($this->recruiter->id, $offer->user_id);
        }
    }

    /** @test */
    public function it_can_create_a_job_offer()
    {
        $jobOfferData = [
            'title' => 'Senior Developer',
            'description' => 'We are looking for a senior developer with 5+ years of experience',
            'company_name' => 'Tech Corp',
            'location' => 'Paris',
            'contract_type' => 'CDI',
            'salary_min' => 45000,
            'salary_max' => 65000,
            'is_active' => true,
            'expires_at' => now()->addDays(30)->format('Y-m-d'),
            'user_id' => $this->recruiter->id
        ];

        $jobOffer = $this->repository->create($jobOfferData);

        $this->assertNotNull($jobOffer->id);
        $this->assertEquals('Senior Developer', $jobOffer->title);
        $this->assertEquals('Tech Corp', $jobOffer->company_name);
        $this->assertEquals($this->recruiter->id, $jobOffer->user_id);
        
        $this->assertDatabaseHas('job_offers', [
            'id' => $jobOffer->id,
            'title' => 'Senior Developer',
        ]);
    }

    /** @test */
    public function it_can_update_a_job_offer()
    {
        $jobOffer = JobOffer::factory()->create([
            'user_id' => $this->recruiter->id,
            'title' => 'Original Title',
            'salary_min' => 40000,
        ]);

        $updateData = [
            'title' => 'Updated Title',
            'salary_min' => 45000,
        ];

        $updatedJobOffer = $this->repository->update($jobOffer->id, $updateData);

        $this->assertEquals('Updated Title', $updatedJobOffer->title);
        $this->assertEquals(45000, $updatedJobOffer->salary_min);
        
        $this->assertDatabaseHas('job_offers', [
            'id' => $jobOffer->id,
            'title' => 'Updated Title',
            'salary_min' => 45000,
        ]);
    }

    /** @test */
    public function it_can_delete_a_job_offer()
    {
        $jobOffer = JobOffer::factory()->create([
            'user_id' => $this->recruiter->id,
        ]);

        $this->repository->delete($jobOffer->id);

        $this->assertDatabaseMissing('job_offers', [
            'id' => $jobOffer->id,
        ]);
    }

    /** @test */
    public function it_can_find_job_offer_by_id()
    {
        $jobOffer = JobOffer::factory()->create([
            'title' => 'Full Stack Developer',
        ]);

        $found = $this->repository->findById($jobOffer->id);

        $this->assertEquals($jobOffer->id, $found->id);
        $this->assertEquals('Full Stack Developer', $found->title);
    }

    /** @test */
    public function it_returns_null_when_job_offer_not_found()
    {
        $nonExistentId = 9999;
        $result = $this->repository->findById($nonExistentId);

        $this->assertNull($result);
    }

    /** @test */
    public function it_can_get_job_offer_statistics_for_recruiter()
    {
        // Create active job offers for our test recruiter
        JobOffer::factory(3)->create([
            'user_id' => $this->recruiter->id,
            'is_active' => true,
        ]);
        
        // Create expired job offers for our test recruiter
        JobOffer::factory(2)->create([
            'user_id' => $this->recruiter->id,
            'is_active' => false,
            'expires_at' => now()->subDays(5)->format('Y-m-d'),
        ]);

        $stats = $this->repository->getStatisticsForRecruiter($this->recruiter->id);

        $this->assertEquals(5, $stats['total_offers']);
        $this->assertEquals(3, $stats['active_offers']);
        $this->assertEquals(2, $stats['expired_offers']);
    }
}