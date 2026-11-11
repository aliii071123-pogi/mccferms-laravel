<?php

$origins = array_filter(array_map('trim', explode(',', (string) env('FERMS_CORS_ALLOWED_ORIGINS', ''))));
$flutterWebUrl = (string) env('FERMS_FLUTTER_WEB_URL', '');
$flutterWebParts = parse_url($flutterWebUrl);

if (is_array($flutterWebParts) && isset($flutterWebParts['scheme'], $flutterWebParts['host'])) {
    $flutterWebOrigin = $flutterWebParts['scheme'].'://'.$flutterWebParts['host'];
    if (isset($flutterWebParts['port'])) {
        $flutterWebOrigin .= ':'.$flutterWebParts['port'];
    }
    $origins[] = $flutterWebOrigin;
}

$origins = array_values(array_unique($origins));

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    'allowed_origins' => $origins,
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => false,
];
