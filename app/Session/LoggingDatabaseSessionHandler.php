<?php

namespace App\Session;

use App\Models\UserLog;
use Illuminate\Session\DatabaseSessionHandler;
use Illuminate\Support\Facades\DB;

class LoggingDatabaseSessionHandler extends DatabaseSessionHandler
{
    /**
     * Read a session from the database.
     *
     * Laravel normally returns an empty session when the
     * database record has expired. We intercept that moment
     * so KNOWURLOCAL can record SESSION EXPIRED first.
     */
    public function read($sessionId): string|false
    {
        /*
         * Retrieve the existing session record directly.
         *
         * We use the same database table configured by Laravel.
         */
        $session = $this->connection
            ->table($this->table)
            ->where('id', $sessionId)
            ->first();

        /*
         * If there is no database record, let Laravel's normal
         * session behavior handle it.
         */
        if (!$session) {
            return parent::read($sessionId);
        }

        /*
         * Check whether Laravel considers this session expired.
         *
         * The parent class already contains Laravel's official
         * expiration calculation.
         */
        if (!$this->expired($session)) {
            /*
             * The session is still valid, so use Laravel's
             * normal session-reading behavior.
             */
            return parent::read($sessionId);
        }

        /*
         * Only authenticated sessions should create a
         * SESSION EXPIRED activity record.
         *
         * Anonymous sessions are not meaningful authentication
         * events.
         */
        if ($session->user_id) {

            /*
             * Get the user's role while the expired session still
             * identifies the user.
             */
            $role = DB::table('users')
                ->where('id', $session->user_id)
                ->value('role');

            /*
             * Use the same database connection as the session
             * handler so the session claim and audit record can
             * be handled together.
             */
            $this->connection->transaction(function () use (
                $session,
                $sessionId,
                $role
            ) {

                /*
                 * Atomically claim the expired session.
                 *
                 * The additional conditions prevent two simultaneous
                 * requests from recording the same expiration twice.
                 */
                $claimed = $this->connection
                    ->table($this->table)
                    ->where('id', $sessionId)
                    ->where('user_id', $session->user_id)
                    ->where('last_activity', $session->last_activity)
                    ->update([
                        /*
                         * Laravel will subsequently treat the session
                         * as unauthenticated. Clearing user_id also
                         * prevents another request from claiming it.
                         */
                        'user_id' => null,
                    ]);

                /*
                 * If another request already claimed this session,
                 * do not create a duplicate activity record.
                 */
                if ($claimed !== 1) {
                    return;
                }

                /*
                 * Create the KNOWURLOCAL activity-log entry.
                 */
                UserLog::on($this->connection->getName())->create([
                    'user_id' => $session->user_id,
                    'action' => 'session_expired',
                    'page' => request()->path(),
                    'role' => $role,
                    'ip_address' => $session->ip_address,
                    'device' => $session->user_agent,
                    'description' =>
                        'User session expired due to inactivity.',
                ]);
            });
        }

        /*
         * Let Laravel continue handling the expired session normally.
         *
         * The parent implementation returns an empty string for
         * expired sessions, which causes Laravel to start an
         * unauthenticated session.
         */
        return parent::read($sessionId);
    }
}