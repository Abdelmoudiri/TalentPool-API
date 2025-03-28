<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

// Interfaces
use App\Repositories\Interfaces\JobOfferRepositoryInterface;
use App\Repositories\Interfaces\JobApplicationRepositoryInterface;

// Implementations
use App\Repositories\JobOfferRepository;
use App\Repositories\JobApplicationRepository;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Bind JobOfferRepository
        $this->app->bind(
            JobOfferRepositoryInterface::class,
            JobOfferRepository::class
        );

        // Bind JobApplicationRepository
        $this->app->bind(
            JobApplicationRepositoryInterface::class,
            JobApplicationRepository::class
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}