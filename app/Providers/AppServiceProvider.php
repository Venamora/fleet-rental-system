<?php

namespace App\Providers;

use App\Domain\Shared\Contracts\Clock;
use App\Infrastructure\Clock\LaravelClock;
use App\Application\Shared\Transaction;
use App\Application\Vehicles\VehicleRepository;
use App\Application\Customers\CustomerRepository;
use App\Application\Rentals\RentalRepository;
use App\Application\Rentals\RentalHistoryRepository;
use App\Application\Rentals\RentalHistory;
use App\Application\Rentals\RentalReadRepository;
use App\Application\MasterData\{BrandRepository,TypeRepository};
use App\Infrastructure\Persistence\{LaravelTransaction,EloquentVehicleRepository,EloquentCustomerRepository,EloquentRentalRepository,EloquentRentalHistoryRepository,EloquentRentalReadRepository,EloquentBrandRepository,EloquentTypeRepository};
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(Clock::class, LaravelClock::class);
        $this->app->bind(Transaction::class, LaravelTransaction::class);
        $this->app->bind(VehicleRepository::class, EloquentVehicleRepository::class);
        $this->app->bind(CustomerRepository::class, EloquentCustomerRepository::class);
        $this->app->bind(RentalRepository::class, EloquentRentalRepository::class);
        $this->app->bind(RentalHistoryRepository::class, EloquentRentalHistoryRepository::class);
        $this->app->bind(RentalHistory::class, EloquentRentalHistoryRepository::class);
        $this->app->bind(RentalReadRepository::class, EloquentRentalReadRepository::class);
        $this->app->bind(BrandRepository::class, EloquentBrandRepository::class);
        $this->app->bind(TypeRepository::class, EloquentTypeRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('login', function (Request $request): Limit {
            return Limit::perMinute(5)->by($request->ip().'|'.$request->string('username')->lower());
        });
    }
}
