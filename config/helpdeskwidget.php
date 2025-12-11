<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Helpdesk API Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the connection to your Helpdesk server.
    |
    */

    // URL base del servidor Helpdesk (sin trailing slash)
    'api_url' => env('HELPDESK_API_URL', 'https://helpdesk.example.com'),

    // API Key proporcionada por el administrador de Helpdesk
    'api_key' => env('HELPDESK_API_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | Widget Display Settings
    |--------------------------------------------------------------------------
    */

    // Altura del iframe del widget
    'iframe_height' => env('HELPDESK_WIDGET_HEIGHT', '600px'),

    // Ancho del iframe del widget (100% para responsive)
    'iframe_width' => env('HELPDESK_WIDGET_WIDTH', '100%'),

    // Mostrar borde en el iframe
    'iframe_border' => env('HELPDESK_WIDGET_BORDER', false),

    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    */

    // Tiempo de cache para tokens (en minutos)
    'token_cache_ttl' => env('HELPDESK_TOKEN_CACHE_TTL', 55),

    /*
    |--------------------------------------------------------------------------
    | Debug Mode
    |--------------------------------------------------------------------------
    */

    // Habilitar logs de debug
    'debug' => env('HELPDESK_DEBUG', false),
];
