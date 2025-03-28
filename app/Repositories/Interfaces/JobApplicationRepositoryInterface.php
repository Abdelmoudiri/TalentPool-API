<?php

namespace App\Repositories\Interfaces;

use App\Models\JobApplication;
use Illuminate\Database\Eloquent\Collection;

interface JobApplicationRepositoryInterface
{
    /**
     * Get all job applications.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAll(): Collection;
    
    /**
     * Get job applications for a specific job offer.
     *
     * @param int $jobOfferId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByJobOffer(int $jobOfferId): Collection;

    /**
     * Get job applications for a specific candidate.
     *
     * @param int $candidateId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByCandidate(int $candidateId): Collection;
    
    /**
     * Get job applications by status.
     *
     * @param string $status
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByStatus(string $status): Collection;

    /**
     * Find a job application by its ID.
     *
     * @param int $id
     * @return JobApplication|null
     */
    public function find(int $id): ?JobApplication;

    /**
     * Create a new job application.
     *
     * @param array $data
     * @return JobApplication
     */
    public function create(array $data): JobApplication;

    /**
     * Update a job application.
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool;

    /**
     * Update the status of a job application.
     *
     * @param int $id
     * @param string $status
     * @return bool
     */
    public function updateStatus(int $id, string $status): bool;

    /**
     * Delete a job application.
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool;
}