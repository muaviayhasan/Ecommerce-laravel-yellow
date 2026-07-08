<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Staff-only firehose of new support messages (drives the admin inbox realtime + bell).
Broadcast::channel('support.admin', fn ($user) => $user->can('support.view'));

// Staff-only firehose of new storefront orders (drives the header notification bell).
Broadcast::channel('admin.orders', fn ($user) => $user->can('orders.view'));
