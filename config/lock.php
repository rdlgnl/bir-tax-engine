<?php

return [

    /*
    |--------------------------------------------------------------------------
    | App lock
    |--------------------------------------------------------------------------
    |
    | An optional PIN between the taxpayer's records and anyone else at this
    | computer. The PIN is stored hashed; forgetting it means restoring a
    | backup on a fresh install.
    |
    */

    // Minutes away before the app locks itself, as offered on screen.
    'after_options' => [1, 5, 15],

    'default_after' => 5,

    // Wrong PINs allowed per minute before the lock screen makes you wait.
    'max_attempts' => 5,

];
