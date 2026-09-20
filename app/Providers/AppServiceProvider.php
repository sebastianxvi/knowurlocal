<?php

namespace App\Providers;

use App\Models\SupportRequest;
use App\Session\LoggingDatabaseSessionHandler;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\URL;
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
    public function boot(): void
    {
        /*
         * Replace Laravel's default database session handler
         * with KNOWURLOCAL's auditing version.
         *
         * Laravel will still use the normal database session
         * lifecycle. Our custom handler only adds the
         * SESSION EXPIRED audit event.
         */
        Session::extend('database', function ($app) {

            return new LoggingDatabaseSessionHandler(
                $app->make('db')->connection(
                    config('session.connection')
                ),
                config('session.table'),
                config('session.lifetime'),
                $app
            );
        });

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
         * Limit "Talk to Human" submissions separately
         * from normal chatbot questions.
         */
        RateLimiter::for('support-request', function ($request) {

            /*
             * Authenticated users receive a limit based on
             * their account ID.
             */
            $userLimit = Limit::perMinute(3)
                ->by(
                    'user:' . ($request->user()?->id ?? 'guest')
                );

            /*
             * Add a second IP-based limit as defense in depth.
             */
            $ipLimit = Limit::perMinute(10)
                ->by(
                    'ip:' . $request->ip()
                );

            /*
             * The request must satisfy both limits.
             */
            return [
                $userLimit,
                $ipLimit,
            ];
        });

        /*
         * Share unread inquiry information with public-user
         * Blade views.
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