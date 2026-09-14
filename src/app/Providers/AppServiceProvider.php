<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\User;
use App\Observers\UserObserver;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

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
    public function boot(): void
    {
        if (request()->isSecure() || request()->header('x-forwarded-proto') === 'https' || str_starts_with(config('app.url', ''), 'https://')) {
            URL::forceScheme('https');
        }
        User::observe(UserObserver::class);

        View::composer('components.navbar', function ($view) {
            $categories = Cache::remember('navbar_categories', 3600, function () {
                return Category::select('id', 'name', 'slug')->orderBy('name')->get();
            });

            $view->with('categories', $categories);
        });
    }
}
