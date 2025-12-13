<?php
// views/auth/login.php

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include the Login component
require_once __DIR__ . '/../components/Login.php';

// Create login instance with configuration
$loginComponent = new Login([
    'title' => 'Laboratoire TDW',
    'subtitle' => 'Authentification',
    'action' => '/TDW_project/auth/login',
    'method' => 'POST',
    'showCredentialsHint' => true,  // Set to false in production
    'backLink' => '/TDW_project/'
]);

// Demo credentials (remove in production)
$demoCredentials = [
    [
        'username' => 'admin',
        'password' => 'admin123',
        'role' => 'Administrateur'
    ],
    [
        'username' => 'membre',
        'password' => 'membre123',
        'role' => 'Membre'
    ]
];

// Render the complete login page
$loginComponent->render([
    'showUsername' => true,
    'showPassword' => true,
    'showEmail' => false,
    'submitText' => 'Se connecter',
    'credentials' => $demoCredentials  // Remove in production
]);
?>