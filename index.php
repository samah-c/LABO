<?php


// Définir l'encodage
header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding('UTF-8');
ini_set('default_charset', 'UTF-8');

// Configuration de session AVANT de démarrer la session
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);

// Démarrer la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Forcer l'encodage pour les données POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    array_walk_recursive($_POST, function(&$value) {
        if (is_string($value)) {
            $value = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
        }
    });
}

// Charger les helpers (nécessaire pour base_url)
require_once __DIR__ . '/lib/helpers.php';

// ============================================================
// VÉRIFICATION DU MODE MAINTENANCE
// ============================================================

/**
 * Vérifier le mode maintenance
 * IMPORTANT: Cette fonction doit être appelée APRÈS avoir traité les routes de login
 */
function checkMaintenanceMode() {
    // Charger les paramètres
    $settingsFile = __DIR__ . '/config/settings.json';
    
    if (!file_exists($settingsFile)) {
        return; // Pas de fichier de config, pas de maintenance
    }
    
    $content = file_get_contents($settingsFile);
    $settings = json_decode($content, true);
    
    // Si le mode maintenance n'est pas activé, continuer normalement
    if (empty($settings['maintenance_mode'])) {
        return;
    }
    
    // Le mode maintenance est activé
    // Vérifier si l'utilisateur est admin
    $isAdmin = isset($_SESSION['user_id']) && 
               isset($_SESSION['role']) && 
               $_SESSION['role'] === 'admin';
    
    if ($isAdmin) {
        // Admin → Laisser passer
        return;
    }
    
    // Non admin → Afficher la page de maintenance
    displayMaintenancePage($settings);
}
/**
 * Afficher la page de maintenance
 */
function displayMaintenancePage($settings) {
    $message = $settings['maintenance_message'] ?? 'Site en maintenance, revenez bientôt.';
    $labName = $settings['lab_name'] ?? 'Laboratoire TDW';
    
    http_response_code(503);
    header('Retry-After: 3600');
    ?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Maintenance - <?= htmlspecialchars($labName) ?></title>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            
            body {
                font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
                background: #f8f9fa;
                min-height: 100vh;
                display: flex;
                justify-content: center;
                align-items: center;
                padding: 20px;
            }
            
            .maintenance-container {
                background: white;
                padding: 60px 50px;
                border-radius: 8px;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
                max-width: 600px;
                width: 100%;
                text-align: center;
            }
            
            .status-badge {
                display: inline-block;
                padding: 8px 16px;
                background: #fee;
                border: 1px solid #fdd;
                border-radius: 4px;
                color: #c00;
                font-size: 13px;
                font-weight: 600;
                margin-bottom: 32px;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }
            
            .icon-container {
                width: 80px;
                height: 80px;
                margin: 0 auto 32px;
                background: #f0f4f8;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            
            .icon-container svg {
                width: 40px;
                height: 40px;
                color: #64748b;
            }
            
            h1 {
                color: #1e293b;
                font-size: 32px;
                margin-bottom: 16px;
                font-weight: 700;
            }
            
            .subtitle {
                color: #64748b;
                font-size: 16px;
                margin-bottom: 32px;
                font-weight: 400;
            }
            
            .message {
                color: #475569;
                font-size: 15px;
                line-height: 1.7;
                margin-bottom: 36px;
                padding: 24px;
                background: #f8fafc;
                border-radius: 6px;
                border-left: 3px solid #3b82f6;
            }
            
            .info-grid {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 16px;
                margin: 32px 0;
            }
            
            .info-item {
                padding: 20px;
                background: #f8fafc;
                border-radius: 6px;
                border: 1px solid #e2e8f0;
            }
            
            .info-item-label {
                color: #64748b;
                font-size: 12px;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                margin-bottom: 8px;
            }
            
            .info-item-value {
                color: #1e293b;
                font-size: 18px;
                font-weight: 600;
            }
            
            .contact {
                margin-top: 32px;
                padding-top: 32px;
                border-top: 1px solid #e2e8f0;
            }
            
            .contact-text {
                color: #64748b;
                font-size: 14px;
                margin-bottom: 12px;
            }
            
            .contact a {
                color: #3b82f6;
                text-decoration: none;
                font-weight: 500;
            }
            
            .contact a:hover {
                text-decoration: underline;
            }
            
            .admin-login {
                margin-top: 32px;
            }
            
            .btn-login {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                padding: 14px 32px;
                background: #3b82f6;
                color: white;
                text-decoration: none;
                border-radius: 6px;
                font-size: 15px;
                font-weight: 600;
                transition: background 0.2s;
            }
            
            .btn-login:hover {
                background: #2563eb;
            }
            
            @media (max-width: 640px) {
                .maintenance-container {
                    padding: 40px 30px;
                }
                
                h1 {
                    font-size: 26px;
                }
                
                .info-grid {
                    grid-template-columns: 1fr;
                }
            }
        </style>
    </head>
    <body>
        <div class="maintenance-container">
            <div class="status-badge">
                En maintenance
            </div>
            
            <div class="icon-container">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
            </div>
            
            <h1>Site en Maintenance</h1>
            <div class="subtitle">Nous améliorons votre expérience</div>
            
            <div class="message">
                <?= nl2br(htmlspecialchars($message)) ?>
            </div>
            
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-item-label">Statut</div>
                    <div class="info-item-value">En cours</div>
                </div>
                <div class="info-item">
                    <div class="info-item-label">Auto-refresh</div>
                    <div class="info-item-value">5 minutes</div>
                </div>
            </div>
            
            <?php if (!empty($settings['lab_email'])): ?>
            <div class="contact">
                <div class="contact-text">Besoin d'aide urgente ?</div>
                <a href="mailto:<?= htmlspecialchars($settings['lab_email']) ?>">
                    <?= htmlspecialchars($settings['lab_email']) ?>
                </a>
            </div>
            <?php endif; ?>
            
            <div class="admin-login">
                <a href="<?= base_url('login') ?>" class="btn-login">
                    Connexion Administrateur
                </a>
            </div>
            <div class="admin-login">
                <a href="<?= base_url('logout') ?>" class="btn-login">
                    Déconnexion
                </a>
            </div>
            <div class="contact-text" style="padding-top: 40px;">Vous devez déconnecter avant pour connecter comme étant admin </div>
        </div>
        
        
        <script>
            setTimeout(() => location.reload(), 300000);
        </script>
    </body>
    </html>
    <?php
    exit;
}


// ============================================================
// CONFIGURATION SUITE
// ============================================================

require_once __DIR__ . '/controllers/auth/AuthController.php';
require_once __DIR__ . '/lib/helpers.php';

// Désactiver le cache pour le développement
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Logging
$logFile = __DIR__ . '/logs/debug.log';
$logDir = dirname($logFile);
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}
file_put_contents($logFile, date('Y-m-d H:i:s') . " - URI: " . $_SERVER['REQUEST_URI'] . "\n", FILE_APPEND);

// Autoloader
spl_autoload_register(function ($class) {
    $paths = [
        __DIR__ . '/models/' . $class . '.php',
        __DIR__ . '/controllers/' . $class . '.php',
        __DIR__ . '/controllers/admin/' . $class . '.php',
        __DIR__ . '/views/components/' . $class . '.php',
        __DIR__ . '/views/admin/' . $class . '.php'
    ];
    
    foreach ($paths as $file) {
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = str_replace('/TDW_project', '', $uri);
$method = $_SERVER['REQUEST_METHOD'];

file_put_contents($logFile, date('Y-m-d H:i:s') . " - Processed URI: $uri - Method: $method\n", FILE_APPEND);

// Autoloader
spl_autoload_register(function ($class) {
    $paths = [
        __DIR__ . '/models/' . $class . '.php',
        __DIR__ . '/controllers/' . $class . '.php',
        __DIR__ . '/controllers/admin/' . $class . '.php',
        __DIR__ . '/views/components/' . $class . '.php',
        __DIR__ . '/views/admin/' . $class . '.php'
    ];
    
    foreach ($paths as $file) {
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = str_replace('/TDW_project', '', $uri);
$method = $_SERVER['REQUEST_METHOD'];

file_put_contents($logFile, date('Y-m-d H:i:s') . " - Processed URI: $uri - Method: $method\n", FILE_APPEND);

// ============================================================
// ROUTES D'AUTHENTIFICATION
// ============================================================

// Page de login uniquement sur /login
if ($uri === '/login') {
    $authController = new AuthController();
    $authController->showLogin();
    exit;
}

if ($uri === '/auth/login' && $method === 'POST') {
    $authController = new AuthController();
    $authController->login();
    exit;
}

if ($uri === '/logout') {
    $authController = new AuthController();
    $authController->logout();
    exit;
}

// Appeler la vérification du mode maintenance
checkMaintenanceMode();


// ============================================================
// ROUTES VISITEUR (PUBLIC)
// ============================================================

// Ces routes ne nécessitent pas d'authentification
require_once __DIR__ . '/controllers/visitor/VisiteurController.php';
$visiteurController = new VisiteurController();

// ===== PAGE D'ACCUEIL =====
if ($uri === '/' || $uri === '' || $uri === '/accueil') {
    $visiteurController->index();
    exit;
}


// ===== MEMBRES (PUBLIC) =====
if ($uri === '/membres') {
    $visiteurController->membres();
    exit;
}

if (preg_match('#^/membres/(\d+)$#', $uri, $matches)) {
    $visiteurController->membreDetail($matches[1]);
    exit;
}

// ===== PROJETS =====
if ($uri === '/projets') {
    $visiteurController->projets();
    exit;
}

if (preg_match('#^/projets/(\d+)$#', $uri, $matches)) {
    $visiteurController->projetDetail($matches[1]);
    exit;
}

// ===== PUBLICATIONS =====
if ($uri === '/publications') {
    $visiteurController->publications();
    exit;
}


if (preg_match('#^/publications/(\d+)$#', $uri, $matches)) {
    $visiteurController->publicationDetail($matches[1]);
    exit;
}

// ===== ÉQUIPEMENTS =====
if ($uri === '/equipements') {
    $visiteurController->equipements();
    exit;
}

if (preg_match('#^/equipements/(\d+)$#', $uri, $matches)) {
    $visiteurController->equipementDetail($matches[1]);
    exit;
}

// ===== ÉVÉNEMENTS =====
if ($uri === '/evenements') {
    $visiteurController->evenements();
    exit;
}

if (preg_match('#^/evenements/(\d+)$#', $uri, $matches)) {
    $visiteurController->evenementDetail($matches[1]);
    exit;
}

// ===== ACTUALITÉS =====
if ($uri === '/actualites') {
    $visiteurController->actualites();
    exit;
}

// Détail d'une actualité
if (preg_match('#^/actualites/(\d+)$#', $uri, $matches)) {
    $visiteurController->actualiteDetail($matches[1]);
    exit;
}

if ($uri === '/offres') {
    error_log("Route LISTE appelée");
    $visiteurController->offres();
    exit;
}

// ===== CONTACT =====
if ($uri === '/contact') {
    $visiteurController->contact();
    exit;
}

if ($uri === '/contact/envoyer' && $method === 'POST') {
    $visiteurController->envoyerContact();
    exit;
}
// ===== ORGANIGRAMME =====
if ($uri === '/organigramme') {
    $visiteurController->organigramme();
    exit;
}
// ============================================================
// ROUTES API - AVANT TOUTES LES ROUTES ADMIN
// ============================================================

if (strpos($uri, '/api/admin') === 0) {
    AuthController::requireAdmin();
    
    // ===== API PUBLICATIONS (le plus spécifique en premier) =====
    if (strpos($uri, '/api/admin/publications/publications/') === 0) {
        require_once __DIR__ . '/controllers/admin/PublicationsController.php';
        $publicationsController = new PublicationsController();
        
        // POST /api/admin/publications/publications/:id/valider
        if (preg_match('#^/api/admin/publications/publications/(\d+)/valider$#', $uri, $matches) && $method === 'POST') {
            $publicationsController->valider($matches[1]);
            exit;
        }
        
        // POST /api/admin/publications/publications/:id/rejeter
        if (preg_match('#^/api/admin/publications/publications/(\d+)/rejeter$#', $uri, $matches) && $method === 'POST') {
            $publicationsController->rejeter($matches[1]);
            exit;
        }
        
        // DELETE /api/admin/publications/publications/:id
        if (preg_match('#^/api/admin/publications/publications/(\d+)$#', $uri, $matches) && $method === 'DELETE') {
            $publicationsController->delete($matches[1]);
            exit;
        }
        
        // GET /api/admin/publications/publications/:id
        if (preg_match('#^/api/admin/publications/publications/(\d+)$#', $uri, $matches) && $method === 'GET') {
            $publicationsController->get($matches[1]);
            exit;
        }
    }

    // ===== API PROJETS =====
    if (strpos($uri, '/api/admin/projets') === 0) {
        require_once __DIR__ . '/controllers/admin/ProjetsController.php';
        $projetsController = new ProjetsController();
        
        // POST /api/admin/projets/add-membre
        if ($uri === '/api/admin/projets/add-membre' && $method === 'POST') {
            $projetsController->addMembre();
            exit;
        }

        // GET /api/admin/projets/:id/membres-disponibles
        if (preg_match('#^/api/admin/projets/(\d+)/membres-disponibles$#', $uri, $matches) && $method === 'GET') {
            $projetsController->getMembresDisponibles($matches[1]);
            exit;
        }
        
        // POST /api/admin/projets/remove-membre
        if ($uri === '/api/admin/projets/remove-membre' && $method === 'POST') {
            $projetsController->removeMembre();
            exit;
        }
        
        // GET /api/admin/projets/:id/membres-disponibles
        if (preg_match('#^/api/admin/projets/(\d+)/membres-disponibles$#', $uri, $matches) && $method === 'GET') {
            require_once __DIR__ . '/models/MembreModel.php';
            $membreModel = new MembreModel();
            $membres = $membreModel->getMembresDisponibles();
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'membres' => $membres
            ]);
            exit;
        }
        
        // GET /api/admin/projets/:id
        if (preg_match('#^/api/admin/projets/(\d+)$#', $uri, $matches) && $method === 'GET') {
            $projetsController->get($matches[1]);
            exit;
        }
        
        // DELETE /api/admin/projets/:id
        if (preg_match('#^/api/admin/projets/(\d+)$#', $uri, $matches) && $method === 'DELETE') {
            $projetsController->delete($matches[1]);
            exit;
        }
    }
    
    // ===== API ÉQUIPES =====
    if (strpos($uri, '/api/admin/equipes') === 0) {
        require_once __DIR__ . '/controllers/admin/EquipesController.php';
        $equipesController = new EquipesController();
        
        // GET /api/admin/equipes/:id/membres-disponibles
        if (preg_match('#^/api/admin/equipes/(\d+)/membres-disponibles$#', $uri, $matches) && $method === 'GET') {
            require_once __DIR__ . '/models/MembreModel.php';
            $membreModel = new MembreModel();
            $membres = $membreModel->getMembresDisponibles();
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'membres' => $membres
            ]);
            exit;
        }
        
        // POST /api/admin/equipes/add-membre
        if ($uri === '/api/admin/equipes/add-membre' && $method === 'POST') {
            $equipesController->addMembre();
            exit;
        }
        
        // POST /api/admin/equipes/remove-membre
        if ($uri === '/api/admin/equipes/remove-membre' && $method === 'POST') {
            $equipesController->removeMembre();
            exit;
        }
        
        // GET /api/admin/equipes/:id
        if (preg_match('#^/api/admin/equipes/(\d+)$#', $uri, $matches) && $method === 'GET') {
            $equipesController->get($matches[1]);
            exit;
        }
        
        // DELETE /api/admin/equipes/:id
        if (preg_match('#^/api/admin/equipes/(\d+)$#', $uri, $matches) && $method === 'DELETE') {
            $equipesController->delete($matches[1]);
            exit;
        }
    }
    
    // ===== API ÉQUIPEMENTS =====
    if (strpos($uri, '/api/admin/equipements') === 0) {
        require_once __DIR__ . '/controllers/admin/EquipementsController.php';
        $equipementsController = new EquipementsController();
        
        // POST /api/admin/equipements/planifier-maintenance
        if ($uri === '/api/admin/equipements/planifier-maintenance' && $method === 'POST') {
            $equipementsController->planifierMaintenance();
            exit;
        }
        
        // GET /api/admin/equipements/:id
        if (preg_match('#^/api/admin/equipements/(\d+)$#', $uri, $matches) && $method === 'GET') {
            $equipementsController->get($matches[1]);
            exit;
        }
        
        // DELETE /api/admin/equipements/:id
        if (preg_match('#^/api/admin/equipements/(\d+)$#', $uri, $matches) && $method === 'DELETE') {
            $equipementsController->delete($matches[1]);
            exit;
        }
    }
    
    // ===== API ÉVÉNEMENTS =====
    if (strpos($uri, '/api/admin/evenements/evenements/') === 0) {
        require_once __DIR__ . '/controllers/admin/EvenementsController.php';
        $evenementsController = new EvenementsController();
        
        // DELETE /api/admin/evenements/evenements/:id
        if (preg_match('#^/api/admin/evenements/evenements/(\d+)$#', $uri, $matches) && $method === 'DELETE') {
            $evenementsController->delete($matches[1]);
            exit;
        }
        
        // GET /api/admin/evenements/evenements/:id
        if (preg_match('#^/api/admin/evenements/evenements/(\d+)$#', $uri, $matches) && $method === 'GET') {
            $evenementsController->get($matches[1]);
            exit;
        }
    }
    
    // ===== API MEMBRES =====
    if ($uri === '/api/admin/membres' && $method === 'GET') {
        require_once __DIR__ . '/models/MembreModel.php';
        $membreModel = new MembreModel();
        $membres = $membreModel->getAllMembresWithUser();
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'membres' => $membres
        ]);
        exit;
    }
    
    // ===== API USERS =====
    if (strpos($uri, '/api/admin/users') === 0) {
        require_once __DIR__ . '/controllers/admin/UsersController.php';
        $usersController = new UsersController();
        
        // POST /api/admin/users/change-role
        if ($uri === '/api/admin/users/change-role' && $method === 'POST') {
            $usersController->changeRole();
            exit;
        }
        
        // POST /api/admin/users/change-status
        if ($uri === '/api/admin/users/change-status' && $method === 'POST') {
            $usersController->changeStatus();
            exit;
        }
        
        // GET /api/admin/users/:id
        if (preg_match('#^/api/admin/users/(\d+)$#', $uri, $matches) && $method === 'GET') {
            $usersController->get($matches[1]);
            exit;
        }
        
        // DELETE /api/admin/users/:id
        if (preg_match('#^/api/admin/users/(\d+)$#', $uri, $matches) && $method === 'DELETE') {
            $usersController->delete($matches[1]);
            exit;
        }
    }

    // ===== API PARAMÈTRES (Base de données) =====
    if (strpos($uri, '/api/admin/database') === 0) {
        require_once __DIR__ . '/controllers/admin/ParametresController.php';
        $parametresController = new ParametresController();
        
        // POST /api/admin/database/backup
        if ($uri === '/api/admin/database/backup' && $method === 'POST') {
            $parametresController->backupDatabase();
            exit;
        }
        
        // POST /api/admin/database/restore
        if ($uri === '/api/admin/database/restore' && $method === 'POST') {
            $parametresController->restoreDatabase();
            exit;
        }
    }

    // ===== API PARAMÈTRES (Cache) =====
    if ($uri === '/api/admin/cache/clear' && $method === 'POST') {
        require_once __DIR__ . '/controllers/admin/ParametresController.php';
        $parametresController = new ParametresController();
        $parametresController->clearCache();
        exit;
    }

    // ===== API PARAMÈTRES (Maintenance) =====
    if ($uri === '/api/admin/maintenance/save' && $method === 'POST') {
        require_once __DIR__ . '/controllers/admin/ParametresController.php';
        $parametresController = new ParametresController();
        $parametresController->saveMaintenance();
        exit;
    }
    
    // ===== API GÉNÉRALE =====
    require_once __DIR__ . '/controllers/admin/AdminApiController.php';
    $apiController = new AdminApiController();
    
    if ($uri === '/api/admin/stats' && $method === 'GET') {
        $apiController->getStats();
        exit;
    }
    
    if ($uri === '/api/admin/search' && $method === 'POST') {
        $apiController->search();
        exit;
    }
    
    if ($uri === '/api/admin/notifications' && $method === 'GET') {
        $apiController->getNotifications();
        exit;
    }
    
    // Si aucune route API ne correspond
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'API endpoint not found', 'uri' => $uri]);
    exit;
}


// ============================================================
// ROUTES ADMIN
// ============================================================

if (strpos($uri, '/admin') === 0) {
    AuthController::requireAdmin();
    
    require_once __DIR__ . '/controllers/admin/AdminController.php';
    $adminController = new AdminController();
    
    // ===== DASHBOARD =====
    if ($uri === '/admin' || $uri === '/admin/dashboard') {
        $adminController->dashboard();
        exit;
    }

    // ===== UTILISATEURS =====
    if (strpos($uri, '/admin/users') === 0) {
        require_once __DIR__ . '/controllers/admin/UsersController.php';
        $usersController = new UsersController();
        
        // Export
        if ($uri === '/admin/users/users' && isset($_GET['export'])) {
            $usersController->export();
            exit;
        }
        
        // Formulaire (AJAX)
        if (preg_match('#^/admin/users/users/form(/(\d+))?$#', $uri, $matches)) {
            $id = $matches[2] ?? null;
            $usersController->form($id);
            exit;
        }
        
        // Sauvegarder
        if ($uri === '/admin/users/users/save' && $method === 'POST') {
            $usersController->save();
            exit;
        }
        
        // Vue détaillée
        if (preg_match('#^/admin/users/users/view/(\d+)$#', $uri, $matches)) {
            $usersController->view($matches[1]);
            exit;
        }
        
        // Liste
        if ($uri === '/admin/users' || $uri === '/admin/users/users') {
            $usersController->index();
            exit;
        }
    }

       
     // ===== PROJETS =====
if (strpos($uri, '/admin/projets') === 0) {
    require_once __DIR__ . '/controllers/admin/ProjetsController.php';
    $projetsController = new ProjetsController();
     
    // Au début de la section projets dans le router
error_log("URI reçu pour projets: " . $uri);

if (preg_match('#^/admin/projets/projets/rapport/(\d+)$#', $uri, $matches)) {
    error_log("Route rapport détectée pour ID: " . $matches[1]);
    $projetsController->genererRapport($matches[1]);
    exit;
}
    // Export
    if ($uri === '/admin/projets/projets' && isset($_GET['export'])) {
        $projetsController->export();
        exit;
    }

    // RAPPORT 
    if (preg_match('#^/admin/projets/projets/rapport/(\d+)$#', $uri, $matches)) {
        $projetsController->genererRapport($matches[1]);
        exit;
    }
    
    // Formulaire (AJAX)
    if (preg_match('#^/admin/projets/projets/form(/(\d+))?$#', $uri, $matches)) {
        $id = $matches[2] ?? null;
        $projetsController->form($id);
        exit;
    }
    
    // Sauvegarder
    if ($uri === '/admin/projets/projets/save' && $method === 'POST') {
        $projetsController->save();
        exit;
    }
    
    // Vue détaillée - APRÈS rapport
    if (preg_match('#^/admin/projets/projets/view/(\d+)$#', $uri, $matches)) {
        $projetsController->view($matches[1]);
        exit;
    }
    
    // Liste
    if ($uri === '/admin/projets' || $uri === '/admin/projets/projets') {
        $projetsController->index();
        exit;
    }
}
    
    // ===== ÉQUIPES =====
    if (strpos($uri, '/admin/equipes') === 0) {
        require_once __DIR__ . '/controllers/admin/EquipesController.php';
        $equipesController = new EquipesController();
        
        // Export
        if ($uri === '/admin/equipes/equipes' && isset($_GET['export'])) {
            $equipesController->export();
            exit;
        }
        
        // Formulaire (AJAX)
        if (preg_match('#^/admin/equipes/equipes/form(/(\d+))?$#', $uri, $matches)) {
            $id = $matches[2] ?? null;
            $equipesController->form($id);
            exit;
        }
        
        // Sauvegarder
        if ($uri === '/admin/equipes/equipes/save' && $method === 'POST') {
            $equipesController->save();
            exit;
        }
        
        // Vue détaillée
        if (preg_match('#^/admin/equipes/equipes/view/(\d+)$#', $uri, $matches)) {
            $equipesController->view($matches[1]);
            exit;
        }
        
        // Liste
        if ($uri === '/admin/equipes' || $uri === '/admin/equipes/equipes') {
            $equipesController->index();
            exit;
        }
    }
    
    // ===== PUBLICATIONS =====
    if (strpos($uri, '/admin/publications') === 0) {
        require_once __DIR__ . '/controllers/admin/PublicationsController.php';
        $publicationsController = new PublicationsController();
        
        // Export
        if ($uri === '/admin/publications/publications' && isset($_GET['export'])) {
            $publicationsController->export();
            exit;
        }
        
        // Rapport bibliographique
        if ($uri === '/admin/publications/publications/rapport') {
            $publicationsController->rapport();
            exit;
        }

        
        // Formulaire (AJAX)
        if (preg_match('#^/admin/publications/publications/form(/(\d+))?$#', $uri, $matches)) {
            $id = $matches[2] ?? null;
            $publicationsController->form($id);
            exit;
        }
        
        
        // Vue détaillée
        if (preg_match('#^/admin/publications/publications/view/(\d+)$#', $uri, $matches)) {
            $publicationsController->view($matches[1]);
            exit;
        }
        
        // Liste
        if ($uri === '/admin/publications/publications') {
            $publicationsController->index();
            exit;
        }

          // Sauvegarder
        if ($uri === '/admin/publications/publications/save' && $method === 'POST') {
            $publicationsController->save();
            exit;
        }
    }
    
    // ===== ÉQUIPEMENTS =====
    if (strpos($uri, '/admin/equipements') === 0) {
        require_once __DIR__ . '/controllers/admin/EquipementsController.php';
        $equipementsController = new EquipementsController();
        
        // Export
        if ($uri === '/admin/equipements/equipements' && isset($_GET['export'])) {
            $equipementsController->export();
            exit;
        }
        
        // Tableau de bord
        if ($uri === '/admin/equipements/equipements/dashboard') {
            $equipementsController->dashboard();
            exit;
        }
        
        // Historique global
        if ($uri === '/admin/equipements/equipements/historique') {
            $equipementsController->historique();
            exit;
        }
        
        // Historique d'un équipement spécifique
        if (preg_match('#^/admin/equipements/equipements/historique/(\d+)$#', $uri, $matches)) {
            $equipementsController->historique($matches[1]);
            exit;
        }
        
        // Rapport d'utilisation
        if ($uri === '/admin/equipements/equipements/rapport') {
            $equipementsController->rapport();
            exit;
        }

   if (strpos($uri, '/admin/equipements/equipements/export-pdf') !== false) {
    $equipementsController = new EquipementsController();
    $equipementsController->exportRapport();
    exit;
}
        
        
        // Formulaire (AJAX)
        if (preg_match('#^/admin/equipements/equipements/form(/(\d+))?$#', $uri, $matches)) {
            $id = $matches[2] ?? null;
            $equipementsController->form($id);
            exit;
        }
        
        // Sauvegarder
        if ($uri === '/admin/equipements/equipements/save' && $method === 'POST') {
            $equipementsController->save();
            exit;
        }
        
        // Vue détaillée
        if (preg_match('#^/admin/equipements/equipements/view/(\d+)$#', $uri, $matches)) {
            $equipementsController->view($matches[1]);
            exit;
        }
        
        // Liste
        if ($uri === '/admin/equipements' || $uri === '/admin/equipements/equipements') {
            $equipementsController->index();
            exit;
        }
    }
    
    // ===== ÉVÉNEMENTS =====
    if (strpos($uri, '/admin/evenements') === 0) {
        require_once __DIR__ . '/controllers/admin/EvenementsController.php';
        $evenementsController = new EvenementsController();
        
        // Export
        if ($uri === '/admin/evenements/evenements' && isset($_GET['export'])) {
            $evenementsController->export();
            exit;
        }
        
        // Formulaire (AJAX)
        if (preg_match('#^/admin/evenements/evenements/form(/(\d+))?$#', $uri, $matches)) {
            $id = $matches[2] ?? null;
            $evenementsController->form($id);
            exit;
        }
        
        // Sauvegarder
        if ($uri === '/admin/evenements/evenements/save' && $method === 'POST') {
            $evenementsController->save();
            exit;
        }
        
        // Vue détaillée
        if (preg_match('#^/admin/evenements/evenements/view/(\d+)$#', $uri, $matches)) {
            $evenementsController->view($matches[1]);
            exit;
        }
        
        // Liste
        if ($uri === '/admin/evenements' || $uri === '/admin/evenements/evenements') {
            $evenementsController->index();
            exit;
        }
    }
    
if (strpos($uri, '/admin/parametres') === 0) {
    require_once __DIR__ . '/controllers/admin/ParametresController.php';
    $parametresController = new ParametresController();
    
    // Télécharger un backup (plus spécifique d'abord)
    if (preg_match('#^/admin/parametres/download-backup/(.+)$#', $uri, $matches)) {
        $parametresController->downloadBackup($matches[1]);
        exit;
    }
    
    // Sauvegarder paramètres généraux
    if ($uri === '/admin/parametres/save-general' && $method === 'POST') {
        $parametresController->saveGeneral();
        exit;
    }
    
    // Sauvegarder réseaux sociaux
    if ($uri === '/admin/parametres/save-social' && $method === 'POST') {
        $parametresController->saveSocial();
        exit;
    }
    
    
    // Page principale des paramètres
    if ($uri === '/admin/parametres') {
        $parametresController->index();
        exit;
    }
}

    // ===== NOTIFICATIONS =====
if (strpos($uri, '/admin/notifications') === 0) {
    require_once __DIR__ . '/controllers/admin/NotificationsController.php';
    $notificationsController = new NotificationsController();
    
    // Formulaire (AJAX)
    if (preg_match('#^/admin/notifications/form(/(\d+))?$#', $uri, $matches)) {
        $id = $matches[2] ?? null;
        $notificationsController->form($id);
        exit;
    }
    
    // Sauvegarder
    if ($uri === '/admin/notifications/save' && $method === 'POST') {
        $notificationsController->save();
        exit;
    }
    
    // Supprimer
    if (preg_match('#^/admin/notifications/delete/(\d+)$#', $uri, $matches) && $method === 'POST') {
        $notificationsController->delete($matches[1]);
        exit;
    }
    
    // API pour les utilisateurs
    if ($uri === '/admin/notifications/getUserNotifications' && $method === 'GET') {
        $notificationsController->getUserNotifications();
        exit;
    }
    
    if ($uri === '/admin/notifications/getUnreadCount' && $method === 'GET') {
        $notificationsController->getUnreadCount();
        exit;
    }
    
    if (preg_match('#^/admin/notifications/markAsRead/(\d+)$#', $uri, $matches) && $method === 'GET') {
        $notificationsController->markAsRead($matches[1]);
        exit;
    }
    
    if ($uri === '/admin/notifications/markAllAsRead' && $method === 'GET') {
        $notificationsController->markAllAsRead();
        exit;
    }
    
    // Liste
    if ($uri === '/admin/notifications') {
        $notificationsController->index();
        exit;
    }
}
}

// ============================================================
// ROUTES MEMBRE
// ============================================================

if (strpos($uri, '/membre') === 0) {
    AuthController::requireMembre();
    
    require_once __DIR__ . '/controllers/member/MembreController.php';
    $membreController = new MembreController();
    
     require_once __DIR__ . '/controllers/member/PublicationController.php';
    $publicationController = new PublicationController();
    
    // ===== DASHBOARD =====
    if ($uri === '/membre' || $uri === '/membre/dashboard') {
        $membreController->dashboard();
        exit;
    }
    
    // ===== PROFIL =====
    if ($uri === '/membre/profil') {
        $membreController->profil();
        exit;
    }
    
    if ($uri === '/membre/profil/update' && $method === 'POST') {
        $membreController->updateProfil();
        exit;
    }

    if ($uri === '/membre/profil/change-password' && $method === 'POST') {
    $membreController->changePassword();
    exit;
}
    
   // ===== PROJETS =====
if ($uri === '/membre/projets') {
    $membreController->projets();
    exit;
}

// Route pour le formulaire d'édition (AJAX) - GET
if (preg_match('#^/membre/projets/form/(\d+)$#', $uri, $matches) && $method === 'GET') {
    $membreController->projetForm($matches[1]);
    exit;
}

// Route pour sauvegarder les modifications - POST
if ($uri === '/membre/projets/save' && $method === 'POST') {
    $membreController->projetSave();
    exit;
}

// Route pour voir un projet (doit être APRÈS form et save)
if (preg_match('#^/membre/projets/(\d+)$#', $uri, $matches)) {
    $membreController->projetDetail($matches[1]);
    exit;
}
    
    // ===== PUBLICATIONS =====
    if ($uri === '/membre/publications') {
        $membreController->publications();
        exit;
    }
    
    if ($uri === '/membre/publications/nouveau' && $method === 'POST') {
        $membreController->soumettrePublication();
        exit;
    }

    // GET projets for publication form
if ($uri === '/membre/publications/get-projets' && $method === 'GET') {
   
    $publicationController->getProjets();
    exit;
}

// GET membres for co-auteurs
if ($uri === '/membre/publications/get-membres' && $method === 'GET') {
    $publicationController->getMembres();
    exit;
}

// Route de suppression -
if (preg_match('#^/membre/publications/delete/(\d+)$#', $uri, $matches) && $method === 'POST') {
  
    $publicationController->deletePublication($matches[1]);
    exit;
}

// Route de suppression -
if (preg_match('#^/membre/publications/get/(\d+)$#', $uri, $matches) && $method === 'GET') {
  
    $publicationController->getPublication($matches[1]);
    exit;
}

// Route de modification - UPDATE
if (preg_match('#^/membre/publications/update/(\d+)$#', $uri, $matches) && $method === 'POST') {
    $publicationController->updatePublication($matches[1]);
    exit;
}
    
    // ===== RÉSERVATIONS =====
    if ($uri === '/membre/reservations') {
        $membreController->reservations();
        exit;
    }
    
    if ($uri === '/membre/reservations/creer' && $method === 'POST') {
        $membreController->creerReservation();
        exit;
    }
    
    if (preg_match('#^/membre/reservations/annuler/(\d+)$#', $uri, $matches) && $method === 'POST') {
        $membreController->annulerReservation($matches[1]);
        exit;
    }

    
    
    // ===== ÉVÉNEMENTS =====
    if ($uri === '/membre/evenements') {
        $membreController->evenements();
        exit;
    }


    // ===== NOTIFICATIONS MEMBRE =====
if (strpos($uri, '/membre/notifications') === 0) {
    require_once __DIR__ . '/controllers/member/MembreNotificationsController.php';
    $notificationsController = new MembreNotificationsController();
    
    if ($uri === '/membre/notifications/getUserNotifications' && $method === 'GET') {
        $notificationsController->getUserNotifications();
        exit;
    }
    
    if ($uri === '/membre/notifications/getUnreadCount' && $method === 'GET') {
        $notificationsController->getUnreadCount();
        exit;
    }
    
    if (preg_match('#^/membre/notifications/markAsRead/(\d+)$#', $uri, $matches) && $method === 'GET') {
        $notificationsController->markAsRead($matches[1]);
        exit;
    }
    
    if ($uri === '/membre/notifications/markAllAsRead' && $method === 'GET') {
        $notificationsController->markAllAsRead();
        exit;
    }
}
}


if (strpos($uri, '/api/membre') === 0) {
    AuthController::requireMembre();
    
    // Vérifier les conflits de réservation
    if ($uri === '/api/membre/reservations/check-conflicts' && $method === 'POST') {
        require_once __DIR__ . '/controllers/member/ReservationApiController.php';
        $apiController = new ReservationApiController();
        $apiController->checkConflicts();
        exit;
    }
    
    // Statistiques d'un équipement spécifique
    if (preg_match('#^/api/membre/equipements/(\d+)/stats$#', $uri, $matches) && $method === 'GET') {
        require_once __DIR__ . '/controllers/member/ReservationApiController.php';
        $apiController = new ReservationApiController();
        $apiController->getEquipementStats($matches[1]);
        exit;
    }
    
    // Statistiques globales des réservations du membre
    if ($uri === '/api/membre/reservations/stats' && $method === 'GET') {
        require_once __DIR__ . '/controllers/member/ReservationApiController.php';
        $apiController = new ReservationApiController();
        $apiController->getGlobalStats();
        exit;
    }
    
    // Calendrier des réservations (pour affichage calendrier)
    if ($uri === '/api/membre/reservations/calendar' && $method === 'GET') {
        require_once __DIR__ . '/controllers/member/ReservationApiController.php';
        $apiController = new ReservationApiController();
        $apiController->getCalendarData();
        exit;
    }
    
    // Réservations à venir (pour notifications)
    if ($uri === '/api/membre/reservations/upcoming' && $method === 'GET') {
        require_once __DIR__ . '/controllers/member/ReservationApiController.php';
        $apiController = new ReservationApiController();
        $apiController->getUpcomingReservations();
        exit;
    }
}

// ============================================================
// 404 - PAGE NON TROUVÉE
// ============================================================

http_response_code(404);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Page non trouvée</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #f8f9fa;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .error-container {
            background: white;
            padding: 60px 50px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            max-width: 700px;
            width: 100%;
            text-align: center;
        }
        
        .error-code {
            margin-bottom: 30px;
        }
        
        .error-code h1 {
            font-size: 120px;
            font-weight: 800;
            color: #3b82f6;
            line-height: 1;
            letter-spacing: -0.05em;
        }
        
        .error-title {
            color: #1e293b;
            font-size: 28px;
            margin-bottom: 16px;
            font-weight: 700;
        }
        
        .error-message {
            color: #64748b;
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 40px;
        }
        
        .debug-info {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 24px;
            margin: 32px 0;
            text-align: left;
        }
        
        .debug-info-title {
            color: #1e293b;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 16px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .debug-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #e2e8f0;
            font-family: 'Courier New', monospace;
            font-size: 13px;
        }
        
        .debug-item:last-child {
            border-bottom: none;
        }
        
        .debug-label {
            color: #64748b;
            font-weight: 600;
        }
        
        .debug-value {
            color: #3b82f6;
            font-weight: 500;
            word-break: break-all;
            max-width: 60%;
            text-align: right;
        }
        
        .action-buttons {
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 40px;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 14px 28px;
            text-decoration: none;
            border-radius: 6px;
            font-size: 15px;
            font-weight: 600;
            transition: all 0.2s;
            cursor: pointer;
            border: none;
        }
        
        .btn-primary {
            background: #3b82f6;
            color: white;
        }
        
        .btn-primary:hover {
            background: #2563eb;
        }
        
        .btn-secondary {
            background: #f1f5f9;
            color: #475569;
            border: 1px solid #e2e8f0;
        }
        
        .btn-secondary:hover {
            background: #e2e8f0;
        }
        
        .suggestions {
            margin-top: 40px;
            padding-top: 32px;
            border-top: 1px solid #e2e8f0;
        }
        
        .suggestions-title {
            color: #1e293b;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 16px;
        }
        
        .suggestions-list {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
        }
        
        .suggestion-link {
            color: #3b82f6;
            text-decoration: none;
            font-size: 14px;
            padding: 12px 16px;
            border-radius: 6px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            transition: all 0.2s;
            text-align: left;
        }
        
        .suggestion-link:hover {
            background: #f1f5f9;
            border-color: #cbd5e1;
        }
        
        @media (max-width: 640px) {
            .error-container {
                padding: 40px 30px;
            }
            
            .error-code h1 {
                font-size: 80px;
            }
            
            .error-title {
                font-size: 24px;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
            
            .suggestions-list {
                grid-template-columns: 1fr;
            }
            
            .debug-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 4px;
            }
            
            .debug-value {
                max-width: 100%;
                text-align: left;
            }
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-code">
            <h1>404</h1>
        </div>
        
        <h2 class="error-title">Page non trouvée</h2>
        <p class="error-message">
            Désolé, la page que vous recherchez n'existe pas ou a été déplacée.
        </p>
        
        <div class="debug-info">
            <div class="debug-info-title">Informations de débogage</div>
            <div class="debug-item">
                <span class="debug-label">URL demandée:</span>
                <span class="debug-value" id="requested-url"></span>
            </div>
            <div class="debug-item">
                <span class="debug-label">Méthode:</span>
                <span class="debug-value" id="method"></span>
            </div>
            <div class="debug-item">
                <span class="debug-label">Code d'erreur:</span>
                <span class="debug-value">404 Not Found</span>
            </div>
        </div>
        
        <div class="action-buttons">
            <a href="/TDW_project/" class="btn btn-primary">
                Retour à l'accueil
            </a>
            <button onclick="window.history.back()" class="btn btn-secondary">
                Page précédente
            </button>
        </div>
        
        <div class="suggestions">
            <div class="suggestions-title">Pages populaires</div>
            <div class="suggestions-list">
                <a href="/TDW_project/projets" class="suggestion-link">Projets de recherche</a>
                <a href="/TDW_project/publications" class="suggestion-link">Publications</a>
                <a href="/TDW_project/membres" class="suggestion-link">Membres de l'équipe</a>
                <a href="/TDW_project/contact" class="suggestion-link">Nous contacter</a>
            </div>
        </div>
    </div>
    
    <script>
        document.getElementById('requested-url').textContent = window.location.pathname;
        document.getElementById('method').textContent = 'GET';
    </script>
</body>
</html>
<?php exit; ?>