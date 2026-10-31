<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Starting money
    |--------------------------------------------------------------------------
    |
    | Balance given to a player when their account is created (first login or
    | first join on the game server). Defaults to 500, the amount set by the
    | economy spec (Brian, 2026-10-31).
    |
    */

    'starting_money' => (int) env('STARTING_MONEY', 500),

];
