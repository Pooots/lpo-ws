<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default administrator
    |--------------------------------------------------------------------------
    |
    | Used by AdminUserSeeder to create or update the LPO admin account.
    | Keep the real password in .env only.
    |
    */

    'name' => env('ADMIN_NAME', 'LPO Administrator'),
    'email' => env('ADMIN_EMAIL'),
    'password' => env('ADMIN_PASSWORD'),

];
