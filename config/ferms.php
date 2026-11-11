<?php

return [
    'super_admin_email' => strtolower((string) env('FERMS_SUPERADMIN_EMAIL', '')),
    'mobile_callback_uri' => env('FERMS_MOBILE_CALLBACK_URI', 'mccferms://auth/callback'),
    'flutter_web_url' => env('FERMS_FLUTTER_WEB_URL'),
    'api_token_days' => (int) env('FERMS_API_TOKEN_DAYS', 30),
];
