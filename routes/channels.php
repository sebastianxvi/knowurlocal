<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Private User Channel
|--------------------------------------------------------------------------
|
| Allows a user to listen only to their own private channel.
|
*/
Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

/*
|--------------------------------------------------------------------------
| Admin Support Requests Channel
|--------------------------------------------------------------------------
|
| Allows authorized administrators to receive new support-request events.
|
*/
Broadcast::channel('admin.support-requests', function ($user) {
    return in_array($user->role, [
        'admin',
        'superadmin',
    ], true);
});

/*
|--------------------------------------------------------------------------
| Administrator Collaboration Channel
|--------------------------------------------------------------------------
|
| Each administrator gets a private channel. A task event is only sent to
| the creator and assignee, and Laravel authorizes the subscription here.
|
*/
Broadcast::channel('admin.{id}', function ($user, $id) {
    return in_array($user->role, ['admin', 'superadmin'], true)
        && (int) $user->id === (int) $id;
});
