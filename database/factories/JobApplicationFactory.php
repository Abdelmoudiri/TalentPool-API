<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\JobOffer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\JobApplication>
 */
class JobApplicationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $statuses = ['pending', 'reviewing', 'accepted', 'rejected'];
        
        return [
            'user_id' => function () {
                return User::factory()->create(['role' => 'candidate'])->id;
            },
            'job_offer_id' => function () {
                return JobOffer::factory()->create()->id;
            },
            'status' => $this->faker->randomElement($statuses),
            'cover_letter' => $this->faker->paragraphs(2, true),
            'cv_path' => 'storage/cvs/sample_' . $this->faker->uuid() . '.pdf',
            'recruiter_notes' => $this->faker->boolean(60) ? $this->faker->paragraph() : null,
            'created_at' => $this->faker->dateTimeBetween('-2 months', 'now'),
            'updated_at' => function (array $attributes) {
                return $this->faker->dateTimeBetween($attributes['created_at'], 'now');
            }
        ];
    }

    /**
     * Configure the factory to generate a pending application.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function pending()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'pending',
                'recruiter_notes' => null,
            ];
        });
    }

    /**
     * Configure the factory to generate an application under review.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function reviewing()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'reviewing',
                'recruiter_notes' => $this->faker->paragraph(),
            ];
        });
    }

    /**
     * Configure the factory to generate an accepted application.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function accepted()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'accepted',
                'recruiter_notes' => $this->faker->paragraph(),
            ];
        });
    }

    /**
     * Configure the factory to generate a rejected application.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function rejected()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'rejected',
                'recruiter_notes' => $this->faker->paragraph(),
            ];
        });
    }
}