<?php

return [
    'CURRENCY' => [
        // 'NAME' => '',
        'CODE' => '',
        // 'SYMBOL' => '',
        'POSITION' => ''
    ],

    'ONESIGNAL' => [
        'APP_ID' => env('ONESIGNAL_APP_ID'),
        'REST_API_KEY' => env('ONESIGNAL_REST_API_KEY'),
        'RIDER_CHANNEL_ID' => env('ONESIGNAL_RIDER_CHANNEL_ID'),
        'DRIVER_APP_ID' => env('ONESIGNAL_DRIVER_APP_ID'),
        'DRIVER_REST_API_KEY' => env('ONESIGNAL_DRIVER_REST_API_KEY'),
        'DRIVER_CHANNEL_ID' => env('ONESIGNAL_DRIVER_CHANNEL_ID'),
        'ONESIGNAL_CHANNEL_ID' => env('ONESIGNAL_CHANNEL_ID'),
    ],

    'DISTANCE' => [
        'RADIUS' => ''
    ],

    'RIDE' => [
        'FOR_OTHER' => '',
        'MULTIPLE_DROP_LOCATION' => '',
        'IS_SCHEDULE_RIDE' => '',
        'DRIVER_CAN_REVIEW' => '',
    ],

    'RIDER VERSION' => [
        'ANDROID_FORCE_UPDATE' => '',
        'ANDROID_VERSION_CODE' => '',
        'APPSTORE_URL' => '',
        'IOS_FORCE_UPDATE' => '',
        'IOS_VERSION' => '',
        'PLAYSTORE_URL' => '',
    ],

    'DRIVER VERSION' => [
        'ANDROID_FORCE_UPDATE' => '',
        'ANDROID_VERSION_CODE' => '',
        'APPSTORE_URL' => '',
        'IOS_FORCE_UPDATE' => '',
        'IOS_VERSION' => '',
        'PLAYSTORE_URL' => '',
    ],
];