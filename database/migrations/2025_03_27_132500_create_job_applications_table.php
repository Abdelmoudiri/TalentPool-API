<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('job_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('job_offer_id')->constrained()->onDelete('cascade');
            $table->enum('status', ['pending', 'reviewing', 'accepted', 'rejected'])->default('pending');
            $table->text('cover_letter')->nullable();
            $table->string('cv_path')->nullable(); // Path to the uploaded CV file
            $table->text('recruiter_notes')->nullable(); // For recruiter feedback
            $table->timestamp('last_status_change')->nullable();
            $table->timestamps();
            
            // Ensure a user can only apply once for each job offer
            $table->unique(['user_id', 'job_offer_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_applications');
    }
};