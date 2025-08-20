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
        // ⭐ URLs LOCALES (développement)
        'http://localhost:4200',
        'http://127.0.0.1:4200',
        'http://localhost:3000',
        'http://127.0.0.1:3000',
        
        // ⭐ URLs DE PRODUCTION
        'http://guildsavior.online',
        'http://www.guildsavior.online',
        'https://guildsavior.online',      // ⭐ HTTPS pour le futur
        'https://www.guildsavior.online',  // ⭐ HTTPS pour le futur
        
        // ⭐ ANCIENNES URLs (au cas où)
        'http://82.112.255.241:3001',
        'http://82.112.255.241:4200',
        'http://82.112.255.241:8080',
    ],
    
    'allowed_origins_patterns' => [
        // ⭐ PATTERN pour accepter tous les sous-domaines de guildsavior.online
        '/^https?:\/\/.*\.guildsavior\.online$/',
    ],
    
    'allowed_headers' => [
        '*',
        'Accept',
        'Authorization',
        'Content-Type',
        'X-Requested-With',
        'X-CSRF-TOKEN',
        'X-XSRF-TOKEN',
        'Origin',
        'Cache-Control',
    ],
    
    'exposed_headers' => [
        'Set-Cookie',  // Important pour exposer les cookies
        'Authorization',
    ],
    
    'max_age' => 86400, // ⭐ 24h de cache pour les requêtes preflight
    
    'supports_credentials' => true, // ✅ Correct pour les cookies
];