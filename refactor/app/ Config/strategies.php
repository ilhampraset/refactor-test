<?php

use DTApi\Services\BookingStoreEmailService;
use DTApi\Services\BookingStoreService;

return [
    'store_context' => [
        'store_booking' => BookingStoreService::class,
        'store_email_booking' => BookingStoreEmailService::class
    ],
    'notifier_context' => [
        'sms_notifier' => SmsNotification::class,
        'push_notifier' => PushNotification::class
    ]
];