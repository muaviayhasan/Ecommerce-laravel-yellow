<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Staff-only firehose of new support messages (drives the admin inbox realtime + bell).
Broadcast::channel('support.admin', fn ($user) => $user->can('support.view'));
