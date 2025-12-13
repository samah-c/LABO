<?php
/**
 * App.php - Classe de gestion de l'application
 * À placer dans : /TDW_project/lib/App.php
 */

class App {
    private static $config = null;
    private static $instance = null;
    
    /**
     * Obtenir l'instance unique
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Charger la configuration
     */
    public static function config($key = null, $default = null) {
        if (self::$config === null) {
            self::$config = require __DIR__ . '/../config/app.php';
        }
        
        if ($key === null) {
            return self::$config;
        }
        
        // Support de la notation pointée (ex: 'paths.root')
        $keys = explode('.', $key);
        $value = self::$config;
        
        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return $default;
            }
            $value = $value[$k];
        }
        
        return $value;
    }
    
    /**
     * Obtenir le chemin de base
     */
    public static function basePath($path = '') {
        return self::config('paths.root') . ($path ? '/' . ltrim($path, '/') : '');
    }
    
    /**
     * Obtenir l'URL de base
     */
    public static function baseUrl($path = '') {
        $baseUrl = self::config('base_url');
        return $baseUrl . ($path ? '/' . ltrim($path, '/') : '');
    }
    
    /**
     * Obtenir l'URL des assets
     */
    public static function asset($path) {
        return self::config('assets_url') . '/' . ltrim($path, '/');
    }
    
    /**
     * Vérifier si on est en mode développement
     */
    public static function isDevelopment() {
        return self::config('environment') === 'development';
    }
    
    /**
     * Vérifier si on est en production
     */
    public static function isProduction() {
        return self::config('environment') === 'production';
    }
    
    /**
     * Logger un message
     */
    public static function log($message, $level = 'INFO') {
        if (!self::config('logging.enabled')) {
            return;
        }
        
        $logFile = self::config('paths.logs') . '/' . self::config('logging.file');
        $logDir = dirname($logFile);
        
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] [$level] $message\n";
        
        file_put_contents($logFile, $logMessage, FILE_APPEND);
    }
    
    /**
     * Gérer une erreur
     */
    public static function error($message, $code = 500) {
        self::log($message, 'ERROR');
        
        if (self::isDevelopment()) {
            throw new Exception($message, $code);
        } else {
            // En production, afficher une page d'erreur générique
            http_response_code($code);
            include self::basePath('views/errors/500.php');
            exit;
        }
    }
    
    /**
     * Obtenir la version de l'application
     */
    public static function version() {
        return self::config('version');
    }
    
    /**
     * Obtenir le nom de l'application
     */
    public static function name() {
        return self::config('name');
    }
}

// Ajouter des fonctions helper globales
if (!function_exists('config')) {
    function config($key = null, $default = null) {
        return App::config($key, $default);
    }
}

if (!function_exists('app_path')) {
    function app_path($path = '') {
        return App::basePath($path);
    }
}

if (!function_exists('app_url')) {
    function app_url($path = '') {
        return App::baseUrl($path);
    }
}

if (!function_exists('app_asset')) {
    function app_asset($path) {
        return App::asset($path);
    }
}
?>