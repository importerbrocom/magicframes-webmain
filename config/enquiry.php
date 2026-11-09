<?php

return [
    // Options shown in the "Event type" dropdown on the contact form.
    'event_types' => [
        'Wedding', 'Pre-Wedding', 'Engagement', 'Couple Shoot', 'Portrait', 'Event', 'Cinematic Film', 'Other',
    ],

    // Where new-enquiry alerts go if no contact email is set in Site settings.
    'notify_email' => env('ENQUIRY_NOTIFY_EMAIL'),
];
