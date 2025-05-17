<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

// Public channel for orders
Broadcast::channel('orders', function () {
    return true;
});

// Public channel for analytics
Broadcast::channel('analytics', function () {
    return true;
});
