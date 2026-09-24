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

];
