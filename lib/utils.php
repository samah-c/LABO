<?php
/**
 * Utils.php - Bibliothèque de fonctions utilitaires réutilisables
 * 
 * Cette bibliothèque centralise toutes les fonctions communes
 * utilisées dans l'application pour éviter la duplication de code
 */

class Utils {
    
    // ========================================
    // SÉCURITÉ ET VALIDATION
    // ========================================
    
    /**
     * Nettoyer et sécuriser une chaîne
     */
    public static function sanitize($data) {
        if (is_array($data)) {
            return array_map([self::class, 'sanitize'], $data);
        }
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Valider un email
     */
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }
    
    /**
     * Valider un numéro de téléphone algérien
     */
    public static function validatePhone($phone) {
        // Format: 0X XX XX XX XX ou +213 X XX XX XX XX
        $pattern = '/^(0|\+213)[5-7][0-9]{8}$/';
        return preg_match($pattern, str_replace(' ', '', $phone));
    }
    
    /**
     * Valider une URL
     */
    public static function validateUrl($url) {
        return filter_var($url, FILTER_VALIDATE_URL);
    }
    
    /**
     * Générer un token CSRF sécurisé
     */
    public static function generateCsrfToken() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Vérifier un token CSRF
     */
    public static function verifyCsrfToken($token) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Hasher un mot de passe
     */
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_DEFAULT);
    }
    
    /**
     * Vérifier un mot de passe
     */
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    // ========================================
    // GESTION DES FICHIERS
    // ========================================
    
    /**
     * Upload d'un fichier avec validation
     */
    public static function uploadFile($file, $destination, $allowedTypes = [], $maxSize = 5242880) {
        // Vérifier si le fichier existe
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return ['success' => false, 'error' => 'Aucun fichier uploadé'];
        }
        
        // Vérifier la taille
        if ($file['size'] > $maxSize) {
            return ['success' => false, 'error' => 'Fichier trop volumineux'];
        }
        
        // Vérifier le type MIME
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!empty($allowedTypes) && !in_array($mimeType, $allowedTypes)) {
            return ['success' => false, 'error' => 'Type de fichier non autorisé'];
        }
        
        // Générer un nom unique
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '_' . time() . '.' . $extension;
        $filepath = $destination . '/' . $filename;
        
        // Créer le dossier si inexistant
        if (!is_dir($destination)) {
            mkdir($destination, 0755, true);
        }
        
        // Déplacer le fichier
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            return ['success' => true, 'filename' => $filename, 'path' => $filepath];
        }
        
        return ['success' => false, 'error' => 'Erreur lors du déplacement du fichier'];
    }
    
    /**
     * Supprimer un fichier
     */
    public static function deleteFile($filepath) {
        if (file_exists($filepath)) {
            return unlink($filepath);
        }
        return false;
    }
    
    /**
     * Obtenir l'extension d'un fichier
     */
    public static function getFileExtension($filename) {
        return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    }
    
    /**
     * Formater la taille d'un fichier
     */
    public static function formatFileSize($bytes) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
    
    // ========================================
    // DATES ET TEMPS
    // ========================================
    
    /**
     * Formater une date en français
     */
    public static function formatDateFr($date, $format = 'd/m/Y') {
        if (empty($date)) return '';
        return date($format, strtotime($date));
    }
    
    /**
     * Obtenir une date relative (il y a 2 jours, etc.)
     */
    public static function getRelativeTime($datetime) {
        $timestamp = strtotime($datetime);
        $diff = time() - $timestamp;
        
        if ($diff < 60) {
            return "À l'instant";
        } elseif ($diff < 3600) {
            $mins = floor($diff / 60);
            return "Il y a $mins minute" . ($mins > 1 ? 's' : '');
        } elseif ($diff < 86400) {
            $hours = floor($diff / 3600);
            return "Il y a $hours heure" . ($hours > 1 ? 's' : '');
        } elseif ($diff < 604800) {
            $days = floor($diff / 86400);
            return "Il y a $days jour" . ($days > 1 ? 's' : '');
        } else {
            return self::formatDateFr($datetime);
        }
    }
    
    /**
     * Calculer l'âge
     */
    public static function calculateAge($birthdate) {
        $birth = new DateTime($birthdate);
        $today = new DateTime();
        return $birth->diff($today)->y;
    }
    
    // ========================================
    // MANIPULATION DE CHAÎNES
    // ========================================
    
    /**
     * Tronquer un texte
     */
    public static function truncate($text, $length = 100, $suffix = '...') {
        if (mb_strlen($text) <= $length) {
            return $text;
        }
        return mb_substr($text, 0, $length) . $suffix;
    }
    
    /**
     * Générer un slug à partir d'une chaîne
     */
    public static function slugify($text) {
        // Remplacer les caractères accentués
        $text = iconv('UTF-8', 'ASCII//TRANSLIT', $text);
        // Convertir en minuscules
        $text = strtolower($text);
        // Remplacer les caractères non alphanumériques par des tirets
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);
        // Supprimer les tirets en début et fin
        $text = trim($text, '-');
        return $text;
    }
    
    /**
     * Générer un extrait de texte
     */
    public static function excerpt($text, $length = 200) {
        $text = strip_tags($text);
        if (mb_strlen($text) <= $length) {
            return $text;
        }
        
        $excerpt = mb_substr($text, 0, $length);
        $lastSpace = mb_strrpos($excerpt, ' ');
        
        if ($lastSpace !== false) {
            $excerpt = mb_substr($excerpt, 0, $lastSpace);
        }
        
        return $excerpt . '...';
    }
    
    // ========================================
    // PAGINATION
    // ========================================
    
    /**
     * Générer les paramètres de pagination
     */
    public static function paginate($total, $perPage = 10, $currentPage = 1) {
        $totalPages = ceil($total / $perPage);
        $currentPage = max(1, min($currentPage, $totalPages));
        $offset = ($currentPage - 1) * $perPage;
        
        return [
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $currentPage,
            'total_pages' => $totalPages,
            'offset' => $offset,
            'has_previous' => $currentPage > 1,
            'has_next' => $currentPage < $totalPages
        ];
    }
    
    /**
     * Générer le HTML de pagination
     */
    public static function renderPagination($pagination, $baseUrl) {
        if ($pagination['total_pages'] <= 1) {
            return '';
        }
        
        $html = '<div class="pagination">';
        
        // Bouton précédent
        if ($pagination['has_previous']) {
            $prev = $pagination['current_page'] - 1;
            $html .= '<a href="' . $baseUrl . '?page=' . $prev . '" class="page-link">« Précédent</a>';
        }
        
        // Numéros de page
        for ($i = 1; $i <= $pagination['total_pages']; $i++) {
            $active = $i === $pagination['current_page'] ? ' active' : '';
            $html .= '<a href="' . $baseUrl . '?page=' . $i . '" class="page-link' . $active . '">' . $i . '</a>';
        }
        
        // Bouton suivant
        if ($pagination['has_next']) {
            $next = $pagination['current_page'] + 1;
            $html .= '<a href="' . $baseUrl . '?page=' . $next . '" class="page-link">Suivant »</a>';
        }
        
        $html .= '</div>';
        return $html;
    }
    
    // ========================================
    // GÉNÉRATION DE DONNÉES
    // ========================================
    
    /**
     * Générer un identifiant unique
     */
    public static function generateUniqueId($prefix = '') {
        return $prefix . uniqid() . '_' . time();
    }
    
    /**
     * Générer un mot de passe aléatoire
     */
    public static function generatePassword($length = 12) {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
        $password = '';
        $charsLength = strlen($chars);
        
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, $charsLength - 1)];
        }
        
        return $password;
    }
    
    // ========================================
    // HTTP ET REDIRECTIONS
    // ========================================
    
    /**
     * Rediriger vers une URL
     */
    public static function redirect($url, $statusCode = 302) {
        header("Location: $url", true, $statusCode);
        exit;
    }
    
    /**
     * Vérifier si la requête est AJAX
     */
    public static function isAjaxRequest() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
    
    /**
     * Envoyer une réponse JSON
     */
    public static function jsonResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
    
    /**
     * Obtenir l'IP du client
     */
    public static function getClientIp() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            return $_SERVER['REMOTE_ADDR'];
        }
    }
    
    // ========================================
    // LOGGING
    // ========================================
    
    /**
     * Logger un message dans un fichier
     */
    public static function log($message, $level = 'INFO', $file = 'app.log') {
        $logDir = __DIR__ . '/../logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $logFile = $logDir . '/' . $file;
        $timestamp = date('Y-m-d H:i:s');
        $ip = self::getClientIp();
        $logMessage = "[$timestamp] [$level] [IP: $ip] $message\n";
        
        file_put_contents($logFile, $logMessage, FILE_APPEND);
    }
    
    // ========================================
    // FORMATAGE ET AFFICHAGE
    // ========================================
    
    /**
     * Formater un nombre
     */
    public static function formatNumber($number, $decimals = 0) {
        return number_format($number, $decimals, ',', ' ');
    }
    
    /**
     * Formater un montant en devise
     */
    public static function formatCurrency($amount, $currency = 'DZD') {
        return self::formatNumber($amount, 2) . ' ' . $currency;
    }
    
    /**
     * Générer des initiales à partir d'un nom
     */
    public static function getInitials($name) {
        $words = explode(' ', $name);
        $initials = '';
        
        foreach ($words as $word) {
            if (!empty($word)) {
                $initials .= mb_strtoupper(mb_substr($word, 0, 1));
            }
        }
        
        return mb_substr($initials, 0, 2);
    }
    
    /**
     * Générer une couleur aléatoire pour avatar
     */
    public static function getAvatarColor($text) {
        $colors = [
            '#e74c3c', '#3498db', '#2ecc71', '#f39c12', 
            '#9b59b6', '#1abc9c', '#34495e', '#e67e22'
        ];
        
        $hash = 0;
        for ($i = 0; $i < strlen($text); $i++) {
            $hash = ord($text[$i]) + (($hash << 5) - $hash);
        }
        
        return $colors[abs($hash) % count($colors)];
    }
    
    // ========================================
    // ARRAYS ET DONNÉES
    // ========================================
    
    /**
     * Trier un tableau multidimensionnel
     */
    public static function sortArrayBy($array, $key, $order = 'ASC') {
        usort($array, function($a, $b) use ($key, $order) {
            $comparison = $a[$key] <=> $b[$key];
            return $order === 'ASC' ? $comparison : -$comparison;
        });
        return $array;
    }
    
    /**
     * Grouper un tableau par clé
     */
    public static function groupBy($array, $key) {
        $result = [];
        foreach ($array as $item) {
            $result[$item[$key]][] = $item;
        }
        return $result;
    }
    
    /**
     * Extraire une colonne d'un tableau
     */
    public static function pluck($array, $key) {
        return array_map(function($item) use ($key) {
            return $item[$key];
        }, $array);
    }
}
?>