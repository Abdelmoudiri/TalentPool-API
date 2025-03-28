<?php

namespace App\Repositories;

use App\Models\JobOffer;
use App\Repositories\Interfaces\JobOfferRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class JobOfferRepository implements JobOfferRepositoryInterface
{
    /**
     * Get all job offers.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAll(): Collection
    {
        return JobOffer::all();
    }
    
    /**
     * Get active job offers.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getActive(): Collection
    {
        return JobOffer::where('is_active', true)
            ->where(function($query) {
                $query->whereNull('expires_at')
                      ->orWhere('expires_at', '>=', now());
            })
            ->get();
    }

    /**
     * Get job offers for a specific recruiter.
     *
     * @param int $recruiterId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByRecruiter(int $recruiterId): Collection
    {
        return JobOffer::where('user_id', $recruiterId)->get();
    }

    /**
     * Find a job offer by its ID.
     *
     * @param int $id
     * @return JobOffer|null
     */
    public function find(int $id): ?JobOffer
    {
        return JobOffer::find($id);
    }

    /**
     * Create a new job offer.
     *
     * @param array $data
     * @return JobOffer
     */
    public function create(array $data): JobOffer
    {
        return JobOffer::create($data);
    }

    /**
     * Update a job offer.
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        $jobOffer = $this->find($id);
        
        if (!$jobOffer) {
            return false;
        }
        
        return $jobOffer->update($data);
    }

    /**
     * Delete a job offer.
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        $jobOffer = $this->find($id);
        
        if (!$jobOffer) {
            return false;
        }
        
        return $jobOffer->delete();
    }
}