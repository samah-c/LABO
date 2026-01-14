<?php
/**
 * app.php - Configuration de l'application
 */

return [
    // Informations de base
    'name' => 'Laboratoire TDW',
    'version' => '1.0.0',
    'environment' => 'development', // development, production
    
    // URLs
    'base_url' => '/TDW_project',
    'assets_url' => '/TDW_project/assets',
    
    // Chemins
    'paths' => [
        'root' => __DIR__ . '/..',
        'controllers' => __DIR__ . '/../controllers',
        'models' => __DIR__ . '/../models',
        'views' => __DIR__ . '/../views',
        'lib' => __DIR__ . '/../lib',
        'uploads' => __DIR__ . '/../uploads',
        'logs' => __DIR__ . '/../logs'
    ],
    
    // Session
    'session' => [
        'timeout' => 1800, // 30 minutes
        'cookie_httponly' => true,
        'cookie_secure' => false, // true en production avec HTTPS
    ],
    
    // Pagination
    'pagination' => [
        'per_page' => 10,
        'per_page_options' => [5, 10, 25, 50, 100]
    ],
    
    // Upload
    'upload' => [
        'max_size' => 5242880, // 5MB
        'allowed_types' => [
            'images' => ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'],
            'documents' => [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            ]
        ]
    ],
    
    // Logging
    'logging' => [
        'enabled' => true,
        'level' => 'DEBUG', // DEBUG, INFO, WARNING, ERROR
        'file' => 'app.log'
    ],
    
    // Sécurité
    'security' => [
        'csrf_enabled' => true,
        'brute_force_protection' => true,
        'max_login_attempts' => 5,
        'lockout_duration' => 900 // 15 minutes
    ],
    
    // Email (pour notifications futures)
    'email' => [
        'from_address' => 'noreply@lab-tdw.dz',
        'from_name' => 'Laboratoire TDW'
    ],
    
    // Thèmes et apparence
    'theme' => [
        'primary_color' => '#3498db',
        'secondary_color' => '#2ecc71',
        'logo' => '/assets/images/logo.png'
    ],
    
    // Fonctionnalités
    'features' => [
        'ajax_enabled' => true,
        'real_time_stats' => true,
        'notifications' => true,
        'export_csv' => true,
        'export_pdf' => true // Nécessite une bibliothèque
    ]
];
?>