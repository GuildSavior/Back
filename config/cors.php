<?php

return [
    'paths' => [
        'api/*', 
        'sanctum/csrf-cookie',
        'auth/*',  // Pour tes routes Discord
        'oauth/*'  // Si tu as d'autres routes OAuth
    ],
    
    'allowed_methods' => ['*'],
    
    'allowed_origins' => [
        'http://localhost:4200',
        'http://127.0.0.1:4200',
        'http://82.112.255.241:3001', // ⭐ Frontend Angular
        //   // Au cas où
    ],
    
    'allowed_origins_patterns' => [],
    
    'allowed_headers' => [
        '*',
        'Accept',
        'Authorization',
        'Content-Type',
        'X-Requested-With',
        'X-CSRF-TOKEN',
        'X-XSRF-TOKEN',
    ],
    
    'exposed_headers' => [
        'Set-Cookie',  // Important pour exposer les cookies
    ],
    
    'max_age' => 0,
    
    'supports_credentials' => true, // ✅ Correct
];