<?php

/*
|--------------------------------------------------------------------------
| Digital Health Passport configuration
|--------------------------------------------------------------------------
|
| Fixed medical standards live here. Everything a System Administrator may
| need to change, such as the passport number prefix or the list of ward
| types, is stored in the settings table and edited on the Settings page.
|
*/

return [

    'blood_groups' => ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'],

    // Seconds between checks for new notifications in the browser.
    'notification_poll_seconds' => (int) env('NOTIFICATION_POLL_SECONDS', 60),

    // Number of rows shown per page in lists.
    'per_page' => (int) env('LIST_PAGE_SIZE', 15),

    // Load demonstration facilities, staff and patients when seeding.
    // Set to false on the live server.
    'seed_demo_data' => (bool) env('SEED_DEMO_DATA', true),

    // The first System Administrator account created by the seeder.
    'admin' => [
        'name' => env('ADMIN_NAME', 'System Administrator'),
        'email' => env('ADMIN_EMAIL', 'admin@healthpassport.mw'),
        'password' => env('ADMIN_PASSWORD', 'Password@2026'),
    ],

];
