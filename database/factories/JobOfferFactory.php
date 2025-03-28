<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\JobOffer>
 */
class JobOfferFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $contractTypes = ['CDI', 'CDD', 'Freelance', 'Stage', 'Alternance'];
        $minSalary = $this->faker->numberBetween(30000, 60000);
        
        return [
            'title' => $this->faker->jobTitle(),
            'description' => $this->faker->paragraphs(3, true),
            'company_name' => $this->faker->company(),
            'location' => $this->faker->city() . ', ' . $this->faker->country(),
            'contract_type' => $this->faker->randomElement($contractTypes),
            'salary_min' => $minSalary,
            'salary_max' => $minSalary + $this->faker->numberBetween(5000, 40000),
            'is_active' => $this->faker->boolean(80), // 80% chance of being active
            'expires_at' => $this->faker->dateTimeBetween('+1 week', '+6 months')->format('Y-m-d'),
            'requirements' => $this->faker->paragraphs(2, true),
            'benefits' => $this->faker->paragraphs(1, true),
            'user_id' => function () {
                // Default to creating a recruiter if no user_id is provided
                return \App\Models\User::factory()->create(['role' => 'recruiter'])->id;
            },
            'created_at' => $this->faker->dateTimeBetween('-3 months', 'now'),
            'updated_at' => function (array $attributes) {
                return $this->faker->dateTimeBetween($attributes['created_at'], 'now');
            }
        ];
    }
}
