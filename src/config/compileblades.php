<?php

return [
    /*
    |--------------------------------------------------------------------------
    | View Composers
    |--------------------------------------------------------------------------
    |
    | Define whether or not you want to compile sections that has view composers.
    | You have to define the location of the service provider if set to true!
    | Default: false
    |
    */
   
    'view_composers' => [
        'exclude_sections' => false,
        'composerserviceprovider_location' => '', // e.g. app_path('Providers/ComposerServiceProvider.php'),
    ],

];
