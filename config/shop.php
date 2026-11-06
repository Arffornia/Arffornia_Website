<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Starting money
    |--------------------------------------------------------------------------
    |
    | Balance given to a player when their account is created (first login or
    | first join on the game server). Defaults to 500 (economy spec).
    |
    */

    'starting_money' => (int) env('STARTING_MONEY', 500),

];
