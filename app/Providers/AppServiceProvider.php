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
 * Limit "Talk to Human" submissions separately
 * from normal chatbot questions.
 *
 * The public chatbot is allowed more frequent requests
 * because users may legitimately ask several questions.
 *
 * Human-support requests are more expensive because each
 * submission creates a database record that may require
 * human attention.
 */
RateLimiter::for('support-request', function ($request) {

    /*
     * Authenticated users receive a limit based on their
     * account ID.
     *
     * This is preferable to IP-only limiting because
     * KNOWURLOCAL users are authenticated.
     */
    $userLimit = Limit::perMinute(3)
        ->by(
            'user:' . ($request->user()?->id ?? 'guest')
        );

    /*
     * Add a second IP-based limit as defense in depth.
     *
     * This protects against unusual cases where multiple
     * accounts are used from the same machine/network.
     */
    $ipLimit = Limit::perMinute(10)
        ->by(
            'ip:' . $request->ip()
        );

    /*
     * Laravel evaluates all returned limits.
     *
     * Therefore a request must satisfy BOTH limits.
     */
    return [
        $userLimit,
        $ipLimit,
    ];

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
