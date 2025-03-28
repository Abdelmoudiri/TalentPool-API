<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobApplication extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'job_offer_id',
        'status',
        'cover_letter',
        'cv_path',
        'notes',
        'last_status_change',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'last_status_change' => 'datetime',
    ];

    /**
     * Get the candidate who submitted this application.
     */
    public function candidate(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the job offer this application is for.
     */
    public function jobOffer(): BelongsTo
    {
        return $this->belongsTo(JobOffer::class);
    }

    /**
     * Update the application status and track the change time.
     *
     * @param string $status
     * @return bool
     */
    public function updateStatus(string $status): bool
    {
        if (!in_array($status, ['pending', 'reviewing', 'accepted', 'rejected'])) {
            return false;
        }

        $this->status = $status;
        $this->last_status_change = now();
        return $this->save();
    }
}