<?php

namespace App\Services;

use App\Models\JobApplication;
use App\Models\JobOffer;
use App\Models\User;
use App\Repositories\Interfaces\JobApplicationRepositoryInterface;
use App\Repositories\Interfaces\JobOfferRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Collection;

class JobApplicationService
{
    protected $jobApplicationRepository;
    protected $jobOfferRepository;

    /**
     * Create a new service instance.
     *
     * @param JobApplicationRepositoryInterface $jobApplicationRepository
     * @param JobOfferRepositoryInterface $jobOfferRepository
     */
    public function __construct(
        JobApplicationRepositoryInterface $jobApplicationRepository,
        JobOfferRepositoryInterface $jobOfferRepository
    ) {
        $this->jobApplicationRepository = $jobApplicationRepository;
        $this->jobOfferRepository = $jobOfferRepository;
    }

    /**
     * Get all applications for a specific job offer.
     *
     * @param int $jobOfferId
     * @return Collection|null
     */
    public function getApplicationsForJobOffer(int $jobOfferId): ?Collection
    {
        $user = Auth::user();
        $jobOffer = $this->jobOfferRepository->find($jobOfferId);
        
        if (!$user || !$jobOffer || (!$user->isAdmin() && $jobOffer->user_id !== $user->id)) {
            return null;
        }
        
        return $this->jobApplicationRepository->getByJobOffer($jobOfferId);
    }

    /**
     * Get all applications submitted by the authenticated candidate.
     *
     * @return Collection|null
     */
    public function getMyApplications(): ?Collection
    {
        $user = Auth::user();
        
        if (!$user || !$user->isCandidate()) {
            return null;
        }
        
        return $this->jobApplicationRepository->getByCandidate($user->id);
    }

    /**
     * Find a job application by ID.
     *
     * @param int $id
     * @return JobApplication|null
     */
    public function findApplication(int $id): ?JobApplication
    {
        $application = $this->jobApplicationRepository->find($id);
        $user = Auth::user();
        
        if (!$user || !$application) {
            return null;
        }
        
        // Only allow access if user is admin, the candidate who applied, or the recruiter who posted the job
        $jobOffer = $this->jobOfferRepository->find($application->job_offer_id);
        
        if ($user->isAdmin() || 
            ($user->isCandidate() && $application->user_id === $user->id) || 
            ($user->isRecruiter() && $jobOffer && $jobOffer->user_id === $user->id)) {
            return $application;
        }
        
        return null;
    }

    /**
     * Apply for a job offer.
     *
     * @param int $jobOfferId
     * @param array $data
     * @param $cvFile
     * @return JobApplication|null
     */
    public function applyForJob(int $jobOfferId, array $data, $cvFile = null): ?JobApplication
    {
        $user = Auth::user();
        $jobOffer = $this->jobOfferRepository->find($jobOfferId);
        
        if (!$user || !$user->isCandidate() || !$jobOffer || !$jobOffer->is_active) {
            return null;
        }
        
        // Check if the candidate has already applied for this job
        $existingApplication = JobApplication::where('user_id', $user->id)
            ->where('job_offer_id', $jobOfferId)
            ->first();
            
        if ($existingApplication) {
            return null; // Already applied
        }
        
        // Handle CV file upload if provided
        $cvPath = null;
        if ($cvFile) {
            $filename = 'cv_' . $user->id . '_' . time() . '.' . $cvFile->getClientOriginalExtension();
            $cvPath = $cvFile->storeAs('cvs', $filename, 'public');
        }
        
        $applicationData = [
            'user_id' => $user->id,
            'job_offer_id' => $jobOfferId,
            'cover_letter' => $data['cover_letter'] ?? null,
            'cv_path' => $cvPath,
            'status' => 'pending',
            'last_status_change' => now(),
        ];
        
        return $this->jobApplicationRepository->create($applicationData);
    }

    /**
     * Withdraw a job application.
     *
     * @param int $id
     * @return bool
     */
    public function withdrawApplication(int $id): bool
    {
        $user = Auth::user();
        $application = $this->jobApplicationRepository->find($id);
        
        if (!$user || !$application || !$user->isCandidate() || $application->user_id !== $user->id) {
            return false;
        }
        
        return $this->jobApplicationRepository->delete($id);
    }

    /**
     * Update the status of a job application.
     *
     * @param int $id
     * @param string $status
     * @param string|null $notes
     * @return bool
     */
    public function updateApplicationStatus(int $id, string $status, ?string $notes = null): bool
    {
        $user = Auth::user();
        $application = $this->jobApplicationRepository->find($id);
        
        if (!$user || !$application) {
            return false;
        }
        
        // Check if the user is the recruiter who posted the job
        $jobOffer = $this->jobOfferRepository->find($application->job_offer_id);
        
        if (!$user->isAdmin() && (!$jobOffer || $jobOffer->user_id !== $user->id)) {
            return false;
        }
        
        // Update notes if provided
        if ($notes !== null) {
            $this->jobApplicationRepository->update($id, ['notes' => $notes]);
        }
        
        // Update status
        $updated = $this->jobApplicationRepository->updateStatus($id, $status);
        
        if ($updated) {
            // Here we would trigger status change notifications
            // For now, we'll leave this as a placeholder
            $this->sendStatusChangeNotification($application);
        }
        
        return $updated;
    }

    /**
     * Get application statistics.
     *
     * @param string $type (admin, recruiter, candidate)
     * @param int|null $userId
     * @return array
     */
    public function getStatistics(string $type, ?int $userId = null): array
    {
        $user = Auth::user();
        
        if (!$user) {
            return [];
        }
        
        // Admin statistics (global)
        if ($type === 'admin' && $user->isAdmin()) {
            return $this->getAdminStatistics();
        }
        
        // Recruiter statistics
        if ($type === 'recruiter' && ($user->isAdmin() || ($user->isRecruiter() && (!$userId || $userId === $user->id)))) {
            $recruiterId = $userId ?? $user->id;
            return $this->getRecruiterStatistics($recruiterId);
        }
        
        // Candidate statistics
        if ($type === 'candidate' && ($user->isAdmin() || ($user->isCandidate() && (!$userId || $userId === $user->id)))) {
            $candidateId = $userId ?? $user->id;
            return $this->getCandidateStatistics($candidateId);
        }
        
        return [];
    }

    /**
     * Send notification about application status change.
     *
     * @param JobApplication $application
     * @return void
     */
    protected function sendStatusChangeNotification(JobApplication $application): void
    {
        // In a real implementation, this would send an email notification
        // For now, we'll just leave this as a placeholder
        // Later we can implement Laravel Notifications or use a queue for this
    }

    /**
     * Get admin-level statistics.
     *
     * @return array
     */
    protected function getAdminStatistics(): array
    {
        $allApplications = $this->jobApplicationRepository->getAll();
        $allOffers = $this->jobOfferRepository->getAll();
        
        $totalApplications = $allApplications->count();
        $totalOffers = $allOffers->count();
        $activeOffers = $allOffers->where('is_active', true)->count();
        
        $statusCounts = [
            'pending' => $allApplications->where('status', 'pending')->count(),
            'reviewing' => $allApplications->where('status', 'reviewing')->count(),
            'accepted' => $allApplications->where('status', 'accepted')->count(),
            'rejected' => $allApplications->where('status', 'rejected')->count(),
        ];
        
        $totalCandidates = User::where('role', 'candidate')->count();
        $totalRecruiters = User::where('role', 'recruiter')->count();
        
        return [
            'total_applications' => $totalApplications,
            'total_offers' => $totalOffers,
            'active_offers' => $activeOffers,
            'status_counts' => $statusCounts,
            'total_candidates' => $totalCandidates,
            'total_recruiters' => $totalRecruiters,
        ];
    }

    /**
     * Get recruiter-level statistics.
     *
     * @param int $recruiterId
     * @return array
     */
    protected function getRecruiterStatistics(int $recruiterId): array
    {
        $recruiterOffers = $this->jobOfferRepository->getByRecruiter($recruiterId);
        $offerIds = $recruiterOffers->pluck('id')->toArray();
        
        $applications = JobApplication::whereIn('job_offer_id', $offerIds)->get();
        
        $totalApplications = $applications->count();
        $totalOffers = $recruiterOffers->count();
        $activeOffers = $recruiterOffers->where('is_active', true)->count();
        
        $statusCounts = [
            'pending' => $applications->where('status', 'pending')->count(),
            'reviewing' => $applications->where('status', 'reviewing')->count(),
            'accepted' => $applications->where('status', 'accepted')->count(),
            'rejected' => $applications->where('status', 'rejected')->count(),
        ];
        
        $offerApplicationCounts = [];
        foreach ($recruiterOffers as $offer) {
            $offerApplicationCounts[$offer->id] = [
                'title' => $offer->title,
                'count' => $applications->where('job_offer_id', $offer->id)->count(),
            ];
        }
        
        return [
            'total_applications' => $totalApplications,
            'total_offers' => $totalOffers,
            'active_offers' => $activeOffers,
            'status_counts' => $statusCounts,
            'offer_application_counts' => $offerApplicationCounts,
        ];
    }

    /**
     * Get candidate-level statistics.
     *
     * @param int $candidateId
     * @return array
     */
    protected function getCandidateStatistics(int $candidateId): array
    {
        $applications = $this->jobApplicationRepository->getByCandidate($candidateId);
        
        $totalApplications = $applications->count();
        
        $statusCounts = [
            'pending' => $applications->where('status', 'pending')->count(),
            'reviewing' => $applications->where('status', 'reviewing')->count(),
            'accepted' => $applications->where('status', 'accepted')->count(),
            'rejected' => $applications->where('status', 'rejected')->count(),
        ];
        
        return [
            'total_applications' => $totalApplications,
            'status_counts' => $statusCounts,
        ];
    }
}