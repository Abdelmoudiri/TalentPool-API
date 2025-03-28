<?php

namespace App\Repositories;

use App\Models\JobApplication;
use App\Repositories\Interfaces\JobApplicationRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class JobApplicationRepository implements JobApplicationRepositoryInterface
{
    /**
     * Get all job applications.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAll(): Collection
    {
        return JobApplication::all();
    }
    
    /**
     * Get job applications for a specific job offer.
     *
     * @param int $jobOfferId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByJobOffer(int $jobOfferId): Collection
    {
        return JobApplication::where('job_offer_id', $jobOfferId)->get();
    }

    /**
     * Get job applications for a specific candidate.
     *
     * @param int $candidateId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByCandidate(int $candidateId): Collection
    {
        return JobApplication::where('user_id', $candidateId)->get();
    }
    
    /**
     * Get job applications by status.
     *
     * @param string $status
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByStatus(string $status): Collection
    {
        return JobApplication::where('status', $status)->get();
    }

    /**
     * Find a job application by its ID.
     *
     * @param int $id
     * @return JobApplication|null
     */
    public function find(int $id): ?JobApplication
    {
        return JobApplication::find($id);
    }

    /**
     * Create a new job application.
     *
     * @param array $data
     * @return JobApplication
     */
    public function create(array $data): JobApplication
    {
        return JobApplication::create($data);
    }

    /**
     * Update a job application.
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        $jobApplication = $this->find($id);
        
        if (!$jobApplication) {
            return false;
        }
        
        return $jobApplication->update($data);
    }

    /**
     * Update the status of a job application.
     *
     * @param int $id
     * @param string $status
     * @return bool
     */
    public function updateStatus(int $id, string $status): bool
    {
        $jobApplication = $this->find($id);
        
        if (!$jobApplication) {
            return false;
        }
        
        return $jobApplication->updateStatus($status);
    }

    /**
     * Delete a job application.
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        $jobApplication = $this->find($id);
        
        if (!$jobApplication) {
            return false;
        }
        
        return $jobApplication->delete();
    }
}