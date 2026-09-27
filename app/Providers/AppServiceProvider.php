<?php

namespace App\Providers;

use App\Models\SupportRequest;
use App\Models\CollaborationTask;
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
         * Share the lightweight admin notification center with the shared
         * header. Notifications are intentionally derived from current
         * actionable records; no separate notification-history table is
         * required. Reverb handles the live delivery layer.
         */
        View::composer(
            'partials.header',
            function ($view) {
                if (!auth()->check() || !in_array(auth()->user()->role, ['admin', 'superadmin'], true)) {
                    $view->with([
                        'adminNotificationSupportCount' => 0,
                        'adminNotificationSupportRequests' => collect(),
                        'adminNotificationCollaborationCount' => 0,
                        'adminNotificationCollaborationTasks' => collect(),
                    ]);
                    return;
                }

                $adminId = auth()->id();

                $supportQuery = SupportRequest::query()
                    ->whereIn('status', ['pending', 'needs_follow_up'])
                    ->with(['user', 'agency'])
                    ->orderByRaw('CASE WHEN assigned_admin_id = ? THEN 0 ELSE 1 END', [$adminId])
                    ->latest('created_at');

                $supportCount = (clone $supportQuery)->count();
                $supportRequests = $supportQuery->limit(5)->get();

                $collaborationQuery = CollaborationTask::query()
                    ->whereIn('status', ['open', 'in_progress'])
                    ->where(function ($query) use ($adminId) {
                        $query
                            ->where('assigned_to_id', $adminId)
                            ->orWhere('created_by_id', $adminId);
                    })
                    ->with(['creator', 'assignee'])
                    ->orderByRaw('CASE WHEN assigned_to_id = ? THEN 0 ELSE 1 END', [$adminId])
                    ->orderByRaw("CASE WHEN due_at IS NOT NULL AND due_at < ? THEN 0 ELSE 1 END", [now()])
                    ->latest('created_at');

                $collaborationCount = (clone $collaborationQuery)->count();
                $collaborationTasks = $collaborationQuery->limit(5)->get();

                $view->with([
                    'adminNotificationSupportCount' => $supportCount,
                    'adminNotificationSupportRequests' => $supportRequests,
                    'adminNotificationCollaborationCount' => $collaborationCount,
                    'adminNotificationCollaborationTasks' => $collaborationTasks,
                ]);
            }
        );

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

                    $view->with([
                        'hasUnreadInquiry' => false,
                        'unreadInquiryCount' => 0,
                    ]);

                    return;
                }

                /*
                 * A citizen notification represents an official response
                 * that still needs the citizen's attention.
                 *
                 * awaiting_confirmation is always actionable because the
                 * citizen must review and confirm/follow up.
                 *
                 * answered + answer_seen_at NULL supports the legacy/simple
                 * answer workflow where the response has not yet been opened.
                 */
                $unreadInquiryCount =
                    SupportRequest::where(
                        'user_id',
                        auth()->id()
                    )
                    ->where(function ($query) {
                        $query
                            ->where('status', 'awaiting_confirmation')
                            ->whereNull('answer_seen_at')
                            ->orWhere(function ($query) {
                                $query
                                    ->where('status', 'answered')
                                    ->whereNull('answer_seen_at');
                            });
                    })
                    ->count();

                /*
                 * Keep the boolean available for existing public views,
                 * while exposing the authoritative numeric count for the
                 * notification badge.
                 */
                $view->with([
                    'hasUnreadInquiry' => $unreadInquiryCount > 0,
                    'unreadInquiryCount' => $unreadInquiryCount,
                ]);
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