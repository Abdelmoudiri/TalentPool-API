<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobOffer extends Model
{
    /** @use HasFactory<\Database\Factories\JobOfferFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'title',
        'description',
        'location',
        'company_name',
        'contract_type',
        'salary_min',
        'salary_max',
        'is_active',
        'expires_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'salary_min' => 'decimal:2',
        'salary_max' => 'decimal:2',
        'is_active' => 'boolean',
        'expires_at' => 'date',
    ];

    /**
     * Get the recruiter who posted this job offer.
     */
    public function recruiter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the applications for this job offer.
     */
    public function applications()
    {
        return $this->hasMany(JobApplication::class);
    }
}
