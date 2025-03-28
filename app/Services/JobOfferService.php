<?php

namespace App\Services;

use App\Models\JobOffer;
use App\Repositories\Interfaces\JobOfferRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Collection;

class JobOfferService
{
    protected $jobOfferRepository;

    /**
     * Create a new service instance.
     *
     * @param JobOfferRepositoryInterface $jobOfferRepository
     */
    public function __construct(JobOfferRepositoryInterface $jobOfferRepository)
    {
        $this->jobOfferRepository = $jobOfferRepository;
    }

    /**
     * Get all job offers.
     *
     * @return Collection
     */
    public function getAllJobOffers(): Collection
    {
        return $this->jobOfferRepository->getAll();
    }

    /**
     * Get active job offers for candidates to view.
     *
     * @return Collection
     */
    public function getActiveJobOffers(): Collection
    {
        return $this->jobOfferRepository->getActive();
    }

    /**
     * Get job offers for the authenticated recruiter.
     *
     * @return Collection|null
     */
    public function getMyJobOffers(): ?Collection
    {
        $user = Auth::user();
        
        if (!$user || !$user->isRecruiter()) {
            return null;
        }
        
        return $this->jobOfferRepository->getByRecruiter($user->id);
    }

    /**
     * Get job offers for a specific recruiter.
     *
     * @param int $recruiterId
     * @return Collection
     */
    public function getJobOffersByRecruiter(int $recruiterId): Collection
    {
        return $this->jobOfferRepository->getByRecruiter($recruiterId);
    }

    /**
     * Find a job offer by ID.
     *
     * @param int $id
     * @return JobOffer|null
     */
    public function findJobOffer(int $id): ?JobOffer
    {
        return $this->jobOfferRepository->find($id);
    }

    /**
     * Create a new job offer for the authenticated recruiter.
     *
     * @param array $data
     * @return JobOffer|null
     */
    public function createJobOffer(array $data): ?JobOffer
    {
        $user = Auth::user();
        
        if (!$user || !$user->isRecruiter()) {
            return null;
        }
        
        // Set the user_id to the authenticated recruiter
        $data['user_id'] = $user->id;
        
        return $this->jobOfferRepository->create($data);
    }

    /**
     * Update a job offer if the authenticated user is the owner.
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function updateJobOffer(int $id, array $data): bool
    {
        $user = Auth::user();
        $jobOffer = $this->jobOfferRepository->find($id);
        
        if (!$user || !$jobOffer || !$user->isRecruiter() || $jobOffer->user_id !== $user->id) {
            return false;
        }
        
        return $this->jobOfferRepository->update($id, $data);
    }

    /**
     * Delete a job offer if the authenticated user is the owner.
     *
     * @param int $id
     * @return bool
     */
    public function deleteJobOffer(int $id): bool
    {
        $user = Auth::user();
        $jobOffer = $this->jobOfferRepository->find($id);
        
        if (!$user || !$jobOffer || !$user->isRecruiter() || $jobOffer->user_id !== $user->id) {
            return false;
        }
        
        return $this->jobOfferRepository->delete($id);
    }

    /**
     * Get statistics for a recruiter's job offers.
     *
     * @param int|null $recruiterId
     * @return array
     */
    public function getRecruiterStatistics(?int $recruiterId = null): array
    {
        $user = Auth::user();
        
        // If no recruiter ID is provided, use the authenticated user
        if (!$recruiterId) {
            if (!$user || !$user->isRecruiter()) {
                return [];
            }
            
            $recruiterId = $user->id;
        }
        
        $jobOffers = $this->jobOfferRepository->getByRecruiter($recruiterId);
        
        $totalOffers = $jobOffers->count();
        $activeOffers = $jobOffers->where('is_active', true)->count();
        $expiredOffers = $jobOffers->where('expires_at', '<', now())->count();
        
        // We'll get more detailed statistics including applications in the JobApplicationService
        
        return [
            'total_offers' => $totalOffers,
            'active_offers' => $activeOffers,
            'expired_offers' => $expiredOffers,
        ];
    }
}