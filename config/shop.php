<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Starting money
    |--------------------------------------------------------------------------
    |
    | Balance given to a player when their account is created (first login or
    | first join on the game server). Defaults to 0.
    |
    */

    'starting_money' => (int) env('STARTING_MONEY', 0),

];
