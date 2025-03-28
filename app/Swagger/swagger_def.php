<?php

/**
 * @OA\Info(
 *      version="1.0.0",
 *      title="TalentPool API Documentation",
 *      description="TalentPool API Documentation - Job offers and applications management",
 *      @OA\Contact(
 *          email="contact@talentpool.example.com",
 *          name="TalentPool Support"
 *      ),
 *      @OA\License(
 *          name="Apache 2.0",
 *          url="http://www.apache.org/licenses/LICENSE-2.0.html"
 *      )
 * )
 *
 * @OA\Server(
 *      url=L5_SWAGGER_CONST_HOST,
 *      description="TalentPool API Server"
 * )
 *
 * @OA\Tag(
 *     name="Authentication",
 *     description="API endpoints for user authentication"
 * )
 * 
 * @OA\Tag(
 *     name="Job Offers",
 *     description="API endpoints for job offers management"
 * )
 *
 * @OA\Tag(
 *     name="Job Applications",
 *     description="API endpoints for job applications management"
 * )
 *
 * @OA\SecurityScheme(
 *      securityScheme="bearerAuth",
 *      type="http",
 *      scheme="bearer",
 *      bearerFormat="JWT"
 * )
 *
 * @OA\Schema(
 *     schema="Error",
 *     @OA\Property(
 *         property="error",
 *         type="string",
 *         example="Error message description"
 *     )
 * )
 * 
 * @OA\Schema(
 *     schema="JobOffer",
 *     @OA\Property(
 *         property="id",
 *         type="integer",
 *         example=1
 *     ),
 *     @OA\Property(
 *         property="title",
 *         type="string",
 *         example="Senior PHP Developer"
 *     ),
 *     @OA\Property(
 *         property="description",
 *         type="string",
 *         example="We are looking for an experienced PHP developer..."
 *     ),
 *     @OA\Property(
 *         property="requirements",
 *         type="string",
 *         example="5+ years of experience, Laravel, MySQL..."
 *     ),
 *     @OA\Property(
 *         property="location",
 *         type="string",
 *         example="Paris, France"
 *     ),
 *     @OA\Property(
 *         property="salary_range",
 *         type="string",
 *         example="50,000€ - 70,000€"
 *     ),
 *     @OA\Property(
 *         property="company_name",
 *         type="string",
 *         example="TechCorp Inc."
 *     ),
 *     @OA\Property(
 *         property="type",
 *         type="string",
 *         example="Full-time"
 *     ),
 *     @OA\Property(
 *         property="is_active",
 *         type="boolean",
 *         example=true
 *     ),
 *     @OA\Property(
 *         property="user_id",
 *         type="integer",
 *         example=2
 *     ),
 *     @OA\Property(
 *         property="created_at",
 *         type="string",
 *         format="date-time",
 *         example="2025-03-19T15:16:24.000000Z"
 *     ),
 *     @OA\Property(
 *         property="updated_at",
 *         type="string",
 *         format="date-time",
 *         example="2025-03-19T15:16:24.000000Z"
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="JobApplication",
 *     @OA\Property(
 *         property="id",
 *         type="integer",
 *         example=1
 *     ),
 *     @OA\Property(
 *         property="job_offer_id",
 *         type="integer",
 *         example=1
 *     ),
 *     @OA\Property(
 *         property="user_id",
 *         type="integer",
 *         example=3
 *     ),
 *     @OA\Property(
 *         property="cover_letter",
 *         type="string",
 *         example="I am interested in this position because..."
 *     ),
 *     @OA\Property(
 *         property="cv_path",
 *         type="string",
 *         example="applications/3/resume.pdf"
 *     ),
 *     @OA\Property(
 *         property="status",
 *         type="string",
 *         example="pending"
 *     ),
 *     @OA\Property(
 *         property="recruiter_notes",
 *         type="string",
 *         example="Good candidate, schedule interview"
 *     ),
 *     @OA\Property(
 *         property="created_at",
 *         type="string",
 *         format="date-time",
 *         example="2025-03-27T13:25:00.000000Z"
 *     ),
 *     @OA\Property(
 *         property="updated_at",
 *         type="string",
 *         format="date-time",
 *         example="2025-03-27T13:25:00.000000Z"
 *     )
 * )
 */