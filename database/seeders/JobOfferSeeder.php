<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class JobOfferSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create some recruiters
        $recruiters = \App\Models\User::factory(3)
            ->create(['role' => 'recruiter']);

        // Create job offers for each recruiter
        foreach ($recruiters as $recruiter) {
            // Create active job offers
            \App\Models\JobOffer::factory(rand(2, 5))
                ->create([
                    'user_id' => $recruiter->id,
                    'is_active' => true,
                ]);
                
            // Create some inactive/expired job offers
            \App\Models\JobOffer::factory(rand(1, 3))
                ->create([
                    'user_id' => $recruiter->id,
                    'is_active' => false,
                    'expires_at' => now()->subDays(rand(1, 30))->format('Y-m-d'),
                ]);
        }
    }
}
