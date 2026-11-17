<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\ChatParticipant;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('chat.user.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id && $user->canDo('read', 'team_chat');
});
