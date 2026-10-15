<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Team Roster Rules
    |--------------------------------------------------------------------------
    |
    | A team can be submitted for admin approval once its approved roster has
    | at least `min_players` and at most `max_players` players. Defaults: 5
    | (enough to field a lineup) and 12 (FIBA roster size). Override per
    | deployment with ROSTER_MIN_PLAYERS / ROSTER_MAX_PLAYERS in .env.
    |
    */

    'roster' => [
        'min_players' => (int) env('ROSTER_MIN_PLAYERS', 5),
        'max_players' => (int) env('ROSTER_MAX_PLAYERS', 12),
    ],

];
