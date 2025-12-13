<?php
require_once __DIR__ . '/controllers/auth/AuthController.php';
require_once __DIR__ . '/lib/helpers.php';

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

file_put_contents($logFile, date('Y-m-d H:i:s') . " - Processed URI: $uri\n", FILE_APPEND);

// ============================================================
// ROUTES D'AUTHENTIFICATION
// ============================================================

if ($uri === '/login' || $uri === '/' || $uri === '') {
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
// ROUTES API - AVANT LES ROUTES ADMIN
// ============================================================

if (strpos($uri, '/api/admin') === 0) {
    AuthController::requireAdmin();
    
    // ===== API ÉQUIPES =====
    if (strpos($uri, '/api/admin/equipes') === 0) {
        require_once __DIR__ . '/controllers/admin/EquipesController.php';
        $equipesController = new EquipesController();
        
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
    }
    
    // API générale
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
    
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'API endpoint not found']);
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
    if ($uri === '/admin/users') {
        $adminController->users();
        exit;
    }
    
    // ===== ÉQUIPES - ORDRE IMPORTANT: routes spécifiques avant génériques =====
    if (strpos($uri, '/admin/equipes') === 0) {
        require_once __DIR__ . '/controllers/admin/EquipesController.php';
        $equipesController = new EquipesController();
        
        // Export (avant /admin/equipes pour éviter conflit)
        if ($uri === '/admin/equipes/equipes' && isset($_GET['export'])) {
            $equipesController->export();
            exit;
        }
        
        // Formulaire d'équipe (AJAX)
        if (preg_match('#^/admin/equipes/equipes/form(/(\d+))?$#', $uri, $matches)) {
            $id = $matches[2] ?? null;
            $equipesController->form($id);
            exit;
        }
        
        // Sauvegarder une équipe
        if ($uri === '/admin/equipes/equipes/save' && $method === 'POST') {
            $equipesController->save();
            exit;
        }
        
        // Vue détaillée d'une équipe
        if (preg_match('#^/admin/equipes/equipes/view/(\d+)$#', $uri, $matches)) {
            $equipesController->view($matches[1]);
            exit;
        }
        
        // Supprimer une équipe (API style)
        if (preg_match('#^/admin/equipes/equipes/delete/(\d+)$#', $uri, $matches) && $method === 'DELETE') {
            $equipesController->delete($matches[1]);
            exit;
        }
        
        // Liste des équipes (par défaut)
        if ($uri === '/admin/equipes' || $uri === '/admin/equipes/equipes') {
            $equipesController->index();
            exit;
        }
    }
    
    // ===== PROJETS =====
    if ($uri === '/admin/projets') {
        if (isset($_GET['export'])) {
            $adminController->exportProjets();
            exit;
        }
        $adminController->projets();
        exit;
    }
    
    if (preg_match('#^/admin/projets/view/(\d+)$#', $uri, $matches)) {
        echo "Vue détaillée du projet #" . $matches[1];
        exit;
    }
    
    // ===== ÉQUIPEMENTS =====
    if ($uri === '/admin/equipements') {
        $adminController->equipements();
        exit;
    }
    
    // ===== PUBLICATIONS =====
    if ($uri === '/admin/publications/publications') {
        if (isset($_GET['export'])) {
            $adminController->exportPublications();
            exit;
        }
        $adminController->publications();
        exit;
    }
    
    if (preg_match('#^/admin/publications/view/(\d+)$#', $uri, $matches)) {
        echo "Vue détaillée de la publication #" . $matches[1];
        exit;
    }
    
    // ===== ÉVÉNEMENTS =====
    if ($uri === '/admin/evenements') {
        $adminController->evenements();
        exit;
    }
    
    if (preg_match('#^/admin/evenements/view/(\d+)$#', $uri, $matches)) {
        echo "Vue détaillée de l'événement #" . $matches[1];
        exit;
    }
    
    // ===== PARAMÈTRES =====
    if ($uri === '/admin/parametres') {
        $adminController->parametres();
        exit;
    }
    
    if ($uri === '/admin/parametres/save-general' && $method === 'POST') {
        $_SESSION['success'] = 'Paramètres généraux enregistrés';
        redirect(base_url('admin/parametres'));
    }
}

// ============================================================
// ROUTES MEMBRE
// ============================================================

if (strpos($uri, '/membre') === 0) {
    AuthController::requireMembre();
    
    if ($uri === '/membre' || $uri === '/membre/dashboard') {
        echo "Dashboard Membre - À implémenter";
        exit;
    }
    
    if ($uri === '/membre/profil') {
        echo "Profil Membre - À implémenter";
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
        
        <a href="/TDW_project/">← Retour à l'accueil</a>
    </div>
</body>
</html>
<?php exit; ?>