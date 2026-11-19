<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Internal school notifications
    |--------------------------------------------------------------------------
    |
    | These addresses receive an internal email whenever a family requests an
    | assessment from the public site and when the school books the call.
    | ASSESSMENT_NOTIFY_TO / ASSESSMENT_NOTIFY_CC can override them per
    | environment (comma-separated) without touching the code.
    |
    */

    'assessment' => [
        'to' => array_values(array_filter(array_map('trim', explode(',', (string) env(
            'ASSESSMENT_NOTIFY_TO',
            'excellentscholars2@gmail.com'
        ))))),

        'cc' => array_values(array_filter(array_map('trim', explode(',', (string) env(
            'ASSESSMENT_NOTIFY_CC',
            'admin@eshglobal.org,ceo@eshglobal.org'
        ))))),
    ],

];
