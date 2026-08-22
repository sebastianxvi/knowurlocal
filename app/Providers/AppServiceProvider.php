<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Models\SupportRequest;

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
    /*
     * Limit chatbot requests per authenticated user.
     *
     * The limiter is keyed by the authenticated user's ID,
     * so different users have independent limits.
     */
    RateLimiter::for('chatbot', function ($request) {

        return Limit::perMinute(20)
            ->by(
                $request->user()?->id ?? $request->ip()
            );

    });


    /*
     * Share unread inquiry information with public-user
     * Blade views.
     *
     * This allows the navbar to display a notification
     * indicator without putting database queries directly
     * inside the navbar Blade file.
     */
    View::composer(
    'components.public.navbar',
    function ($view) {

            /*
             * Guests cannot have personal inquiry notifications.
             */
            if (!auth()->check()) {

                $view->with(
                    'hasUnreadInquiry',
                    false
                );

                return;
            }


            /*
             * Check whether the authenticated user has at least
             * one answered inquiry whose answer has not yet been seen.
             *
             * exists() is used because we only need a yes/no result,
             * not the actual inquiry records.
             */
            $hasUnreadInquiry =
                SupportRequest::where(
                    'user_id',
                    auth()->id()
                )
                ->where(
                    'status',
                    'answered'
                )
                ->whereNull(
                    'answer_seen_at'
                )
                ->exists();


            /*
             * Make the result available to the public-user view.
             */
            $view->with(
                'hasUnreadInquiry',
                $hasUnreadInquiry
            );
        }
    );


    /*
     * Force HTTPS when the application is running in production.
     */
    if (app()->environment('production')) {
        URL::forceScheme('https');
    }
}
}
