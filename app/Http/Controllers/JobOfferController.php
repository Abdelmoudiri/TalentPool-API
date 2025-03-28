<?php

namespace App\Http\Controllers;

use App\Models\JobOffer;
use App\Services\JobOfferService;
use App\Http\Requests\StoreJobOfferRequest;
use App\Http\Requests\UpdateJobOfferRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="TalentPool API Documentation"
 * )
 * 
 * @OA\Server(
 *      url="/api"
 * )
 */
class JobOfferController extends Controller
{
    protected $jobOfferService;

    /**
     * Create a new controller instance.
     *
     * @param JobOfferService $jobOfferService
     */
    public function __construct(JobOfferService $jobOfferService)
    {
        $this->jobOfferService = $jobOfferService;
        // Removed middleware call - authentication is handled in routes/api.php
    }

    /**
     * Display a listing of job offers.
     *
     * @return \Illuminate\Http\JsonResponse
     * 
     * @OA\Get(
     *     path="/job-offers",
     *     operationId="getJobOffers",
     *     tags={"Job Offers"},
     *     summary="Get list of job offers",
     *     description="Returns list of job offers based on user role. Recruiters see their own postings, candidates and admins see all active offers",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="job_offers",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="title", type="string", example="Senior PHP Developer"),
     *                     @OA\Property(property="description", type="string", example="We are looking for an experienced PHP developer..."),
     *                     @OA\Property(property="requirements", type="string", example="5+ years of experience, Laravel, MySQL..."),
     *                     @OA\Property(property="location", type="string", example="Paris, France"),
     *                     @OA\Property(property="salary_range", type="string", example="50,000€ - 70,000€"),
     *                     @OA\Property(property="company_name", type="string", example="TechCorp Inc."),
     *                     @OA\Property(property="type", type="string", example="Full-time"),
     *                     @OA\Property(property="is_active", type="boolean", example=true),
     *                     @OA\Property(property="user_id", type="integer", example=2),
     *                     @OA\Property(property="created_at", type="string", format="date-time"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized access",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Unauthorized access")
     *         )
     *     )
     * )
     */
    public function index(): JsonResponse
    {
        $user = Auth::user();
        
        // If user is a recruiter, show their job offers
        if ($user->isRecruiter()) {
            $jobOffers = $this->jobOfferService->getMyJobOffers();
            
            if (!$jobOffers) {
                return response()->json(['error' => 'Unauthorized access'], 403);
            }
            
            return response()->json(['job_offers' => $jobOffers]);
        }
        
        // For candidates and admins, show all active job offers
        $jobOffers = $this->jobOfferService->getActiveJobOffers();
        return response()->json(['job_offers' => $jobOffers]);
    }

    /**
     * Store a newly created job offer.
     *
     * @param StoreJobOfferRequest $request
     * @return \Illuminate\Http\JsonResponse
     * 
     * @OA\Post(
     *     path="/job-offers",
     *     operationId="storeJobOffer",
     *     tags={"Job Offers"},
     *     summary="Create a new job offer",
     *     description="Creates a new job offer (recruiter only)",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="title", type="string", example="Senior PHP Developer"),
     *             @OA\Property(property="description", type="string", example="We are looking for an experienced PHP developer..."),
     *             @OA\Property(property="requirements", type="string", example="5+ years of experience, Laravel, MySQL..."),
     *             @OA\Property(property="location", type="string", example="Paris, France"),
     *             @OA\Property(property="salary_range", type="string", example="50,000€ - 70,000€"),
     *             @OA\Property(property="company_name", type="string", example="TechCorp Inc."),
     *             @OA\Property(property="type", type="string", example="Full-time"),
     *             @OA\Property(property="is_active", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Job offer created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Job offer created successfully"),
     *             @OA\Property(
     *                 property="job_offer", 
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="title", type="string", example="Senior PHP Developer"),
     *                 @OA\Property(property="description", type="string", example="We are looking for an experienced PHP developer..."),
     *                 @OA\Property(property="requirements", type="string", example="5+ years of experience, Laravel, MySQL..."),
     *                 @OA\Property(property="location", type="string", example="Paris, France"),
     *                 @OA\Property(property="salary_range", type="string", example="50,000€ - 70,000€"),
     *                 @OA\Property(property="company_name", type="string", example="TechCorp Inc."),
     *                 @OA\Property(property="type", type="string", example="Full-time"),
     *                 @OA\Property(property="is_active", type="boolean", example=true),
     *                 @OA\Property(property="user_id", type="integer", example=2),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized to create job offers",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Unauthorized to create job offers")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\AdditionalProperties(
     *                     type="array",
     *                     @OA\Items(type="string")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function store(StoreJobOfferRequest $request): JsonResponse
    {
        $jobOffer = $this->jobOfferService->createJobOffer($request->validated());
        
        if (!$jobOffer) {
            return response()->json(['error' => 'Unauthorized to create job offers'], 403);
        }
        
        return response()->json([
            'message' => 'Job offer created successfully',
            'job_offer' => $jobOffer
        ], 201);
    }

    /**
     * Display the specified job offer.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     * 
     * @OA\Get(
     *     path="/job-offers/{id}",
     *     operationId="getJobOfferById",
     *     tags={"Job Offers"},
     *     summary="Get job offer information",
     *     description="Returns job offer data. Non-active job offers can only be viewed by their creator or admin.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Job Offer ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="job_offer", 
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="title", type="string", example="Senior PHP Developer"),
     *                 @OA\Property(property="description", type="string", example="We are looking for an experienced PHP developer..."),
     *                 @OA\Property(property="requirements", type="string", example="5+ years of experience, Laravel, MySQL..."),
     *                 @OA\Property(property="location", type="string", example="Paris, France"),
     *                 @OA\Property(property="salary_range", type="string", example="50,000€ - 70,000€"),
     *                 @OA\Property(property="company_name", type="string", example="TechCorp Inc."),
     *                 @OA\Property(property="type", type="string", example="Full-time"),
     *                 @OA\Property(property="is_active", type="boolean", example=true),
     *                 @OA\Property(property="user_id", type="integer", example=2),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Job offer not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Job offer not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized access",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Unauthorized access")
     *         )
     *     )
     * )
     */
    public function show(int $id): JsonResponse
    {
        $jobOffer = $this->jobOfferService->findJobOffer($id);
        
        if (!$jobOffer) {
            return response()->json(['error' => 'Job offer not found'], 404);
        }
        
        // If the job offer is not active, only the recruiter who created it or admin can view it
        $user = Auth::user();
        if (!$jobOffer->is_active && !$user->isAdmin() && $jobOffer->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized access'], 403);
        }
        
        return response()->json(['job_offer' => $jobOffer]);
    }

    /**
     * Update the specified job offer.
     *
     * @param UpdateJobOfferRequest $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     * 
     * @OA\Put(
     *     path="/job-offers/{id}",
     *     operationId="updateJobOffer",
     *     tags={"Job Offers"},
     *     summary="Update job offer information",
     *     description="Updates a job offer (recruiter who created it or admin only)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Job Offer ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="title", type="string", example="Senior PHP Developer"),
     *             @OA\Property(property="description", type="string", example="We are looking for an experienced PHP developer..."),
     *             @OA\Property(property="requirements", type="string", example="5+ years of experience, Laravel, MySQL..."),
     *             @OA\Property(property="location", type="string", example="Paris, France"),
     *             @OA\Property(property="salary_range", type="string", example="50,000€ - 70,000€"),
     *             @OA\Property(property="company_name", type="string", example="TechCorp Inc."),
     *             @OA\Property(property="type", type="string", example="Full-time"),
     *             @OA\Property(property="is_active", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Job offer updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Job offer updated successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized or job offer not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Unauthorized or job offer not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\AdditionalProperties(
     *                     type="array",
     *                     @OA\Items(type="string")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function update(UpdateJobOfferRequest $request, int $id): JsonResponse
    {
        $updated = $this->jobOfferService->updateJobOffer($id, $request->validated());
        
        if (!$updated) {
            return response()->json(['error' => 'Unauthorized or job offer not found'], 403);
        }
        
        return response()->json(['message' => 'Job offer updated successfully']);
    }

    /**
     * Remove the specified job offer.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     * 
     * @OA\Delete(
     *     path="/job-offers/{id}",
     *     operationId="deleteJobOffer",
     *     tags={"Job Offers"},
     *     summary="Delete a job offer",
     *     description="Deletes a job offer (recruiter who created it or admin only)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Job Offer ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Job offer deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Job offer deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized or job offer not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Unauthorized or job offer not found")
     *         )
     *     )
     * )
     */
    public function destroy(int $id): JsonResponse
    {
        $deleted = $this->jobOfferService->deleteJobOffer($id);
        
        if (!$deleted) {
            return response()->json(['error' => 'Unauthorized or job offer not found'], 403);
        }
        
        return response()->json(['message' => 'Job offer deleted successfully']);
    }

    /**
     * Get statistics for a recruiter's job offers.
     *
     * @return \Illuminate\Http\JsonResponse
     * 
     * @OA\Get(
     *     path="/job-offers/statistics",
     *     operationId="getJobOfferStatistics",
     *     tags={"Job Offers"},
     *     summary="Get job offer statistics",
     *     description="Returns statistics about a recruiter's job offers (recruiters and admins only)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="statistics",
     *                 type="object",
     *                 @OA\Property(property="total_offers", type="integer", example=15),
     *                 @OA\Property(property="active_offers", type="integer", example=10),
     *                 @OA\Property(property="inactive_offers", type="integer", example=5),
     *                 @OA\Property(property="offers_with_applications", type="integer", example=8)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized access",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Unauthorized access")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Could not retrieve statistics",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Could not retrieve statistics")
     *         )
     *     )
     * )
     */
    public function statistics(): JsonResponse
    {
        $user = Auth::user();
        
        if (!$user->isRecruiter() && !$user->isAdmin()) {
            return response()->json(['error' => 'Unauthorized access'], 403);
        }
        
        $statistics = $this->jobOfferService->getRecruiterStatistics();
        
        if (empty($statistics)) {
            return response()->json(['error' => 'Could not retrieve statistics'], 400);
        }
        
        return response()->json(['statistics' => $statistics]);
    }
}
