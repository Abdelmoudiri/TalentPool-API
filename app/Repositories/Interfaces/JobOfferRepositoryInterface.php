<?php

namespace App\Repositories\Interfaces;

use App\Models\JobOffer;
use Illuminate\Database\Eloquent\Collection;

interface JobOfferRepositoryInterface
{
    /**
     * Get all job offers.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAll(): Collection;
    
    /**
     * Get active job offers.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getActive(): Collection;

    /**
     * Get job offers for a specific recruiter.
     *
     * @param int $recruiterId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByRecruiter(int $recruiterId): Collection;

    /**
     * Find a job offer by its ID.
     *
     * @param int $id
     * @return JobOffer|null
     */
    public function find(int $id): ?JobOffer;

    /**
     * Create a new job offer.
     *
     * @param array $data
     * @return JobOffer
     */
    public function create(array $data): JobOffer;

    /**
     * Update a job offer.
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool;

    /**
     * Delete a job offer.
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool;
}