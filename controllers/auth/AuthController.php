<?php
// controllers/AuthController.php

require_once __DIR__ . '/../../models/AdminModel.php';
require_once __DIR__ . '/../../models/MembreModel.php';
require_once __DIR__ . '/../../models/UserModel.php';
require_once __DIR__ . '/../../models/VisiteurModel.php';

class AuthController {
    private $userModel;
    private $adminModel;
    private $membreModel;
    private $visiteurModel;
    
    public function __construct() {
        $this->userModel = new UserModel();
        $this->adminModel = new AdminModel();
        $this->membreModel = new MembreModel();
        $this->visiteurModel = new VisiteurModel();
    }
    
    /**
     * Afficher la page de login
     */
    public function showLogin() {
        // Démarrer la session si pas déjà démarrée
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Si déjà connecté, rediriger
        if (isset($_SESSION['user_id'])) {
            $this->redirectByRole($_SESSION['role']);
            return;
        }
        
        require_once __DIR__ . '/../../views/auth/login.php';
    }
    
    /**
     * Traiter la connexion
     */
 public function login() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: /TDW_project/login');
        exit;
    }
    
    // Démarrer la session si pas déjà démarrée
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Vérifier si c'est une requête AJAX
    $isAjax = !empty($_POST['ajax']) || 
              !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
              strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    
    // Récupérer et nettoyer les données
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validation
    $errors = [];
    
    if (empty($username)) {
        $errors[] = "Le nom d'utilisateur est requis";
    } elseif (strlen($username) < 3) {
        $errors[] = "Le nom d'utilisateur doit contenir au moins 3 caractères";
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errors[] = "Le nom d'utilisateur contient des caractères invalides";
    }
    
    if (empty($password)) {
        $errors[] = "Le mot de passe est requis";
    } elseif (strlen($password) < 3) {
        $errors[] = "Le mot de passe doit contenir au moins 3 caractères";
    }
    
    // Si erreurs de validation
    if (!empty($errors)) {
        if ($isAjax) {
            $this->sendJsonResponse([
                'success' => false,
                'message' => implode(', ', $errors),
                'errors' => $errors
            ]);
        } else {
            $_SESSION['errors'] = $errors;
            header('Location: /TDW_project/login');
            exit;
        }
    }
    
    // Tentative de connexion
    $user = $this->userModel->login($username, $password);
    
    if ($user) {
        // Connexion réussie
        $this->createSession($user);
        $this->logLogin($user['id'], true);
        
        // Déterminer l'URL de redirection
        $redirectUrl = $this->getRedirectUrlByRole($user['role']);
        
        if ($isAjax) {
            $this->sendJsonResponse([
                'success' => true,
                'message' => 'Connexion réussie',
                'redirect' => $redirectUrl,
                'user' => [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'role' => $user['role']
                ]
            ]);
        } else {
            header("Location: $redirectUrl");
            exit;
        }
        
    } else {
        // Connexion échouée
        $this->logLogin($username, false);
        
        // Protection contre le brute force : délai progressif
        $this->applyBruteForceDelay($username);
        
        if ($isAjax) {
            $this->sendJsonResponse([
                'success' => false,
                'message' => "Nom d'utilisateur ou mot de passe incorrect",
                'attempts' => $this->getFailedAttempts($username)
            ]);
        } else {
            $_SESSION['error'] = "Nom d'utilisateur ou mot de passe incorrect";
            header('Location: /TDW_project/login');
            exit;
        }
    }
}

/**
 * Envoyer une réponse JSON
 */
private function sendJsonResponse($data) {
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Obtenir l'URL de redirection selon le rôle
 */
private function getRedirectUrlByRole($role) {
    switch ($role) {
        case 'admin':
            return '/TDW_project/admin/dashboard';
        case 'membre':
            return '/TDW_project/membre/dashboard';
        case 'visiteur':
            return '/TDW_project/';
        default:
            return '/TDW_project/';
    }
}

/**
 * Protection contre le brute force
 */
private function applyBruteForceDelay($username) {
    $attempts = $this->getFailedAttempts($username);
    
    if ($attempts >= 3) {
        // Délai progressif : 1s, 2s, 4s, 8s...
        $delay = min(pow(2, $attempts - 2), 30);
        sleep($delay);
    }
}

/**
 * Compter les tentatives échouées récentes
 */
private function getFailedAttempts($username) {
    $logFile = __DIR__ . '/../../logs/login_attempts.log';
    
    if (!file_exists($logFile)) {
        return 0;
    }
    
    $lines = file($logFile);
    $recentAttempts = 0;
    $timeLimit = time() - 3600; // Dernière heure
    
    foreach (array_reverse($lines) as $line) {
        if (strpos($line, 'FAILED') !== false && 
            strpos($line, $username) !== false) {
            
            // Extraire le timestamp
            preg_match('/\[(.*?)\]/', $line, $matches);
            if (isset($matches[1])) {
                $logTime = strtotime($matches[1]);
                if ($logTime > $timeLimit) {
                    $recentAttempts++;
                } else {
                    break; // Arrêter si on dépasse la limite de temps
                }
            }
        }
    }
    
    return $recentAttempts;
}
    
    /**
     * Créer la session utilisateur de manière sécurisée
     */
    private function createSession($user) {
        
        // Régénérer l'ID de session (sécurité contre session fixation)
        session_regenerate_id(true);
        
        // Stocker les informations de base
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['login_time'] = time();
        
        // Récupérer les infos supplémentaires selon le rôle
        switch ($user['role']) {
            case 'admin':
                $admin = $this->adminModel->getByUserId($user['id']);
                if ($admin) {
                    $_SESSION['admin_id'] = $admin['id'];
                    $_SESSION['niveau_acces'] = $admin['niveau_acces'];
                }
                break;
                
            case 'membre':
                $membre = $this->membreModel->getByUserId($user['id']);
                if ($membre) {
                    $_SESSION['membre_id'] = $membre['id'];
                    $_SESSION['poste'] = $membre['poste'];
                    $_SESSION['grade'] = $membre['grade'];
                    $_SESSION['equipe_id'] = $membre['equipe_id'];
                }
                break;
                
            case 'visiteur':
                $visiteur = $this->visiteurModel->getByUserId($user['id']);
                if ($visiteur) {
                    $_SESSION['visiteur_id'] = $visiteur['id'];
                }
                break;
        }
        
        // Token CSRF (sécurité contre attaques CSRF)
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    
    /**
     * Rediriger selon le rôle
     */
    private function redirectByRole($role) {
        switch ($role) {
            case 'admin':
                header('Location: /TDW_project/admin/dashboard');
                break;
            case 'membre':
                header('Location: /TDW_project/membre/dashboard');
                break;
            case 'visiteur':
                header('Location: /TDW_project/');
                break;
            default:
                header('Location: /TDW_project/');
        }
        exit;
    }
    
    /**
     * Déconnexion
     */
    public function logout() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Log de déconnexion
        if (isset($_SESSION['user_id'])) {
            $this->logLogout($_SESSION['user_id']);
        }
        
        // Détruire toutes les variables de session
        $_SESSION = array();
        
        // Détruire le cookie de session
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        
        // Détruire la session
        session_destroy();
        
        // Rediriger vers la page de login
        header('Location: /TDW_project/login?logout=1');
        exit;
    }
    
    /**
     * Logger les tentatives de connexion
     */
    private function logLogin($identifier, $success) {
        $logFile = __DIR__ . '/../../logs/login_attempts.log';
        $timestamp = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        $status = $success ? 'SUCCESS' : 'FAILED';
        
        $logMessage = "[$timestamp] $status - User: $identifier - IP: $ip\n";
        
        // Créer le dossier logs si inexistant
        $logDir = dirname($logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        file_put_contents($logFile, $logMessage, FILE_APPEND);
    }
    
    /**
     * Logger les déconnexions
     */
    private function logLogout($userId) {
        $logFile = __DIR__ . '/../../logs/login_attempts.log';
        $timestamp = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        
        $logMessage = "[$timestamp] LOGOUT - User ID: $userId - IP: $ip\n";
        file_put_contents($logFile, $logMessage, FILE_APPEND);
    }
    
    /**
     * Vérifier si l'utilisateur est connecté (middleware)
     */
    public static function requireLogin() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['user_id'])) {
            header('Location: /TDW_project/login');
            exit;
        }
    }
    
    /**
     * Vérifier si l'utilisateur est admin (middleware)
     */
    public static function requireAdmin() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
            http_response_code(403);
            die('Accès refusé. Vous devez être administrateur.');
        }
    }
    
    /**
     * Vérifier si l'utilisateur est membre (middleware)
     */
    public static function requireMembre() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'membre') {
            http_response_code(403);
            die('Accès refusé. Vous devez être membre.');
        }
    }
    
    /**
     * Vérifier le token CSRF
     */
    public static function verifyCsrfToken($token) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
            http_response_code(403);
            die('Token CSRF invalide');
        }
    }
    
    /**
     * Vérifier le timeout de session (30 minutes d'inactivité)
     */
    public static function checkSessionTimeout() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $timeout = 1800; // 30 minutes en secondes
        
        if (isset($_SESSION['login_time'])) {
            if (time() - $_SESSION['login_time'] > $timeout) {
                // Session expirée
                session_destroy();
                header('Location: /TDW_project/login?timeout=1');
                exit;
            }
            // Mettre à jour le timestamp
            $_SESSION['login_time'] = time();
        }
    }
}