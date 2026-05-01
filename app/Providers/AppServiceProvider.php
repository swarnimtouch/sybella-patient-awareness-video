<?php

namespace App\Providers;
use App\Models\User;
use App\Models\UserFile;
use Illuminate\Support\Facades\View;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot()
    {
        View::composer('*', function ($view) {
            $view->with([
                'employeeCount' => User::where('type', 'employee')->count(),
                'doctorCount' => User::where('type', 'doctor')->count(),
                'bannerCount' => UserFile::count(),
            ]);
        });
    }
}
