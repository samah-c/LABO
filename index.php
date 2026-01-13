<?php

// Définir l'encodage
header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding('UTF-8');
ini_set('default_charset', 'UTF-8');

// Forcer l'encodage pour les données POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    array_walk_recursive($_POST, function(&$value) {
        if (is_string($value)) {
            $value = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
        }
    });
}


require_once __DIR__ . '/controllers/auth/AuthController.php';
require_once __DIR__ . '/lib/helpers.php';


// Désactiver le cache pour le développement
header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1
header("Pragma: no-cache"); // HTTP 1.0
header("Expires: 0"); // Proxies

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);

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
    
    // ===== PARAMÈTRES =====
    if (strpos($uri, '/admin/parametres') === 0) {
        require_once __DIR__ . '/controllers/admin/ParametresController.php';
        $parametresController = new ParametresController();
        
        // Télécharger un backup (plus spécifique d'abord)
        if (preg_match('#^/admin/parametres/download-backup/(.+)$#', $uri, $matches)) {
            $parametresController->downloadBackup($matches[1]);
            exit;
        }
        
        // Backup database
        if ($uri === '/admin/parametres/backup-database' && $method === 'POST') {
            $parametresController->backupDatabase();
            exit;
        }
        
        // Restore database
        if ($uri === '/admin/parametres/restore-database' && $method === 'POST') {
            $parametresController->restoreDatabase();
            exit;
        }
        
        // Clear cache
        if ($uri === '/admin/parametres/clear-cache' && $method === 'POST') {
            $parametresController->clearCache();
            exit;
        }
        
        // Save maintenance
        if ($uri === '/admin/parametres/save-maintenance' && $method === 'POST') {
            $parametresController->saveMaintenance();
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
        
        // Sauvegarder thème
        if ($uri === '/admin/parametres/save-theme' && $method === 'POST') {
            $parametresController->saveTheme();
            exit;
        }
        
        // Page principale des paramètres
        if ($uri === '/admin/parametres') {
            $parametresController->index();
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
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/TDW_project/assets/css/styles.css">
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            font-family: 'Poppins', sans-serif;
            margin: 0;
        }
        .error-container {
            text-align: center;
            background: white;
            padding: 60px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 500px;
        }
        .error-container h1 {
            font-size: 120px;
            margin: 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: 700;
        }
        .error-container h2 {
            color: #2c3e50;
            margin: 20px 0;
        }
        .error-container p {
            color: #7f8c8d;
            margin: 20px 0;
        }
        .error-container .debug-info {
            margin-top: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            text-align: left;
            font-family: monospace;
            font-size: 12px;
            color: #495057;
        }
        .error-container a {
            display: inline-block;
            margin-top: 20px;
            padding: 15px 40px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 600;
            transition: transform 0.3s;
        }
        .error-container a:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.5);
        }
        
        
    </style>
</head>
<body>
    <div class="error-container">
        <h1>404</h1>
        <h2>Page non trouvée</h2>
        <p>Désolé, la page que vous recherchez n'existe pas ou a été déplacée.</p>
        
        <div class="debug-info">
            <strong>URL demandée:</strong> <?= htmlspecialchars($uri) ?><br>
            <strong>Méthode:</strong> <?= htmlspecialchars($method) ?>
        </div>
        
        <a href="/TDW_project/">Retour à l'accueil</a>
    </div>

</body>
</html>
<?php exit; ?>