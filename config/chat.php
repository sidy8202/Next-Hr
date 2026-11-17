<?php

return [
    // Supply TURN servers in production for calls across restrictive networks.
    'ice_servers' => array_values(array_filter([
        env('CHAT_TURN_URL') ? [
            'urls' => array_map('trim', explode(',', env('CHAT_TURN_URL'))),
            'username' => env('CHAT_TURN_USERNAME'),
            'credential' => env('CHAT_TURN_CREDENTIAL'),
        ] : null,
    ])),
];
