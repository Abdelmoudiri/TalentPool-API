<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create admin user for system administration
        \App\Models\User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@talentpool.com',
            'role' => 'admin',
            'password' => bcrypt('password123'), // For demo purposes only
        ]);

        // Create recruiters
        \App\Models\User::factory(3)->create([
            'role' => 'recruiter',
        ]);

        // Create a specific recruiter for demo
        \App\Models\User::factory()->create([
            'name' => 'Recruiter Demo',
            'email' => 'recruiter@talentpool.com',
            'role' => 'recruiter',
            'password' => bcrypt('password123'), // For demo purposes only
        ]);

        // Create candidates
        \App\Models\User::factory(10)->create([
            'role' => 'candidate',
        ]);

        // Create a specific candidate for demo
        \App\Models\User::factory()->create([
            'name' => 'Candidate Demo',
            'email' => 'candidate@talentpool.com',
            'role' => 'candidate',
            'password' => bcrypt('password123'), // For demo purposes only
        ]);

        // Create job offers with the JobOfferSeeder
        $this->call(JobOfferSeeder::class);

        // Get existing job offers and candidates for applications
        $jobOffers = \App\Models\JobOffer::where('is_active', true)->get();
        $candidates = \App\Models\User::where('role', 'candidate')->get();

        // Create applications for each candidate to random job offers
        foreach ($candidates as $candidate) {
            // Each candidate applies to 1-3 random job offers
            $appliedOffers = $jobOffers->random(rand(1, min(3, $jobOffers->count())));
            
            foreach ($appliedOffers as $offer) {
                // Create different status applications
                $status = $this->getRandomStatus();
                
                if ($status === 'pending') {
                    \App\Models\JobApplication::factory()->pending()->create([
                        'user_id' => $candidate->id,
                        'job_offer_id' => $offer->id,
                    ]);
                } elseif ($status === 'reviewing') {
                    \App\Models\JobApplication::factory()->reviewing()->create([
                        'user_id' => $candidate->id,
                        'job_offer_id' => $offer->id,
                    ]);
                } elseif ($status === 'accepted') {
                    \App\Models\JobApplication::factory()->accepted()->create([
                        'user_id' => $candidate->id,
                        'job_offer_id' => $offer->id,
                    ]);
                } elseif ($status === 'rejected') {
                    \App\Models\JobApplication::factory()->rejected()->create([
                        'user_id' => $candidate->id,
                        'job_offer_id' => $offer->id,
                    ]);
                }
            }
        }
    }
    
    /**
     * Get a random status with weighted probabilities
     * 
     * @return string
     */
    private function getRandomStatus(): string
    {
        $rand = rand(1, 100);
        
        if ($rand <= 40) {
            return 'pending'; // 40% pending
        } elseif ($rand <= 70) {
            return 'reviewing'; // 30% reviewing
        } elseif ($rand <= 85) {
            return 'accepted'; // 15% accepted
        } else {
            return 'rejected'; // 15% rejected
        }
    }
}
