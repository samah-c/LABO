<?php
/**
 * helpers.php - Chargement automatique des bibliothèques
 */

// Charger la classe App en premier
require_once __DIR__ . '/App.php';

// Charger les bibliothèques
require_once __DIR__ . '/Utils.php';
require_once __DIR__ . '/LabHelpers.php';

// ========================================
// FONCTIONS GLOBALES RACCOURCIES
// ========================================

/**
 * Échapper et nettoyer une valeur
 */
function e($value) {
    return Utils::sanitize($value);
}

/**
 * Redirection rapide
 */
function redirect($url) {
    Utils::redirect($url);
}

/**
 * Réponse JSON rapide
 */
function json($data, $code = 200) {
    Utils::jsonResponse($data, $code);
}

/**
 * Vérifier si requête AJAX
 */
function is_ajax() {
    return Utils::isAjaxRequest();
}

/**
 * Logger un message
 */
function log_message($message, $level = 'INFO') {
    Utils::log($message, $level);
}

/**
 * Générer un token CSRF
 */
function csrf_token() {
    return Utils::generateCsrfToken();
}

/**
 * Champ CSRF caché pour formulaires
 */
function csrf_field() {
    $token = csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

/**
 * Obtenir une valeur de la session
 */
function session($key, $default = null) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return $_SESSION[$key] ?? $default;
}

/**
 * Définir une valeur dans la session
 */
function set_session($key, $value) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION[$key] = $value;
}

/**
 * Flash message (message temporaire)
 */
function flash($key, $message = null) {
    if ($message === null) {
        // Récupérer le message
        $msg = session($key);
        unset($_SESSION[$key]);
        return $msg;
    } else {
        // Définir le message
        set_session($key, $message);
    }
}

/**
 * Notifier l'utilisateur
 */
function notify($message, $type = 'info') {
    LabHelpers::notify($message, $type);
}

/**
 * Obtenir les notifications
 */
function get_notifications() {
    return LabHelpers::getNotifications();
}

/**
 * Obtenir une variable GET
 */
function get($key, $default = null) {
    return $_GET[$key] ?? $default;
}

/**
 * Obtenir une variable POST
 */
function post($key, $default = null) {
    return $_POST[$key] ?? $default;
}

/**
 * Obtenir une variable REQUEST
 */
function request($key, $default = null) {
    return $_REQUEST[$key] ?? $default;
}

/**
 * Vérifier si une valeur existe dans POST
 */
function has_post($key) {
    return isset($_POST[$key]);
}

/**
 * Obtenir toutes les données POST
 */
function all_post() {
    return $_POST;
}

/**
 * Obtenir l'URL actuelle
 */
function current_url() {
    return $_SERVER['REQUEST_URI'];
}

/**
 * Obtenir l'URL de base
 */
function base_url($path = '') {
    $baseUrl = '/TDW_project';
    return $baseUrl . ($path ? '/' . ltrim($path, '/') : '');
}

/**
 * Générer une URL pour un asset
 */
function asset($path) {
    return base_url('assets/' . ltrim($path, '/'));
}

/**
 * Vérifier si l'utilisateur est connecté
 */
function is_logged_in() {
    return session('user_id') !== null;
}

/**
 * Vérifier si l'utilisateur est admin
 */
function is_admin() {
    return session('role') === 'admin';
}

/**
 * Vérifier si l'utilisateur est membre
 */
function is_membre() {
    return session('role') === 'membre';
}

/**
 * Obtenir l'utilisateur connecté
 */
function auth_user() {
    return [
        'id' => session('user_id'),
        'username' => session('username'),
        'email' => session('email'),
        'role' => session('role')
    ];
}

/**
 * Formater une date
 */
function format_date($date, $format = 'd/m/Y') {
    return Utils::formatDateFr($date, $format);
}

/**
 * Date relative (il y a X temps)
 */
function time_ago($datetime) {
    return Utils::getRelativeTime($datetime);
}

/**
 * Tronquer un texte
 */
function truncate($text, $length = 100) {
    return Utils::truncate($text, $length);
}

/**
 * Générer un slug
 */
function slugify($text) {
    return Utils::slugify($text);
}

/**
 * Vérifier si une valeur est vide
 */
function is_empty($value) {
    return empty($value);
}

/**
 * Vérifier si un fichier a été uploadé
 */
function has_file($key) {
    return isset($_FILES[$key]) && $_FILES[$key]['error'] === UPLOAD_ERR_OK;
}

/**
 * Obtenir un fichier uploadé
 */
function get_file($key) {
    return has_file($key) ? $_FILES[$key] : null;
}

/**
 * Afficher une variable pour debug
 */
function dd(...$vars) {
    echo '<pre style="background:#1e1e1e;color:#dcdcdc;padding:20px;margin:10px;border-radius:5px;font-family:monospace;">';
    foreach ($vars as $var) {
        var_dump($var);
    }
    echo '</pre>';
    die();
}

/**
 * Afficher une variable sans arrêter l'exécution
 */
function dump(...$vars) {
    echo '<pre style="background:#1e1e1e;color:#dcdcdc;padding:20px;margin:10px;border-radius:5px;font-family:monospace;">';
    foreach ($vars as $var) {
        var_dump($var);
    }
    echo '</pre>';
}

/**
 * Générer des options HTML pour un select
 */
function select_options($options, $selected = null, $placeholder = null) {
    $html = '';
    
    if ($placeholder) {
        $html .= '<option value="">' . e($placeholder) . '</option>';
    }
    
    foreach ($options as $value => $label) {
        $isSelected = $value == $selected ? ' selected' : '';
        $html .= '<option value="' . e($value) . '"' . $isSelected . '>' . e($label) . '</option>';
    }
    
    return $html;
}

/**
 * Générer une classe active si l'URL correspond
 */
function active_link($path, $class = 'active') {
    return strpos(current_url(), $path) !== false ? $class : '';
}

/**
 * Générer un message d'erreur de validation
 */
function validation_error($field) {
    $errors = session('validation_errors') ?? [];
    return $errors[$field] ?? null;
}

/**
 * Afficher un message d'erreur de validation
 */
function show_error($field) {
    $error = validation_error($field);
    return $error ? '<div class="field-error">' . e($error) . '</div>' : '';
}

/**
 * Ancienne valeur du formulaire après erreur
 */
function old($field, $default = '') {
    $old = session('old_input') ?? [];
    return $old[$field] ?? $default;
}

/**
 * Sauvegarder les anciennes valeurs
 */
function save_old_input() {
    set_session('old_input', $_POST);
}

/**
 * Nettoyer les anciennes valeurs
 */
function clear_old_input() {
    unset($_SESSION['old_input']);
}

/**
 * Vérifier si un message flash existe
 * @param string $key Clé du message
 * @return bool
 */
function has_flash($key) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    return isset($_SESSION['flash'][$key]);
}

/**
 * Récupérer tous les messages flash
 * @return array
 */
function get_all_flash() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    
    return $messages;
}

/**
 * Effacer tous les messages flash
 */
function clear_flash() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    unset($_SESSION['flash']);
}

function setFlash($key, $message) {
    if (!isset($_SESSION['flash'])) {
        $_SESSION['flash'] = [];
    }
    $_SESSION['flash'][$key] = $message;
}

/**
 * Récupérer un message flash
 * @param string $key Clé du message
 * @return string|null Le message ou null
 */
function getFlash($key) {
    if (isset($_SESSION['flash'][$key])) {
        $message = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]); // Supprimer immédiatement après lecture
        return $message;
    }
    return null;
}

/**
 * Vérifier si un message flash existe
 * @param string $key Clé du message
 * @return bool
 */
function hasFlash($key) {
    return isset($_SESSION['flash'][$key]);
}

/**
 * Récupérer tous les messages flash et les supprimer
 * @return array
 */
function getAllFlash() {
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

/**
 * Définir un message de succès
 * @param string $message
 */
function setSuccess($message) {
    setFlash('success', $message);
}

/**
 * Définir un message d'erreur
 * @param string $message
 */
function setError($message) {
    setFlash('error', $message);
}

/**
 * Définir un message d'info
 * @param string $message
 */
function setInfo($message) {
    setFlash('info', $message);
}

/**
 * Définir un message d'avertissement
 * @param string $message
 */
function setWarning($message) {
    setFlash('warning', $message);
}
?>