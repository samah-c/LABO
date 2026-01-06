<?php
/**
 * AdminController.php - Version complète avec CrudView
 */

require_once __DIR__ . '/../../models/AdminModel.php';
require_once __DIR__ . '/../../models/MembreModel.php';
require_once __DIR__ . '/../../models/UserModel.php';
require_once __DIR__ . '/../../models/ProjetModel.php';
require_once __DIR__ . '/../../models/PublicationModel.php';
require_once __DIR__ . '/../../lib/helpers.php';
require_once __DIR__ . '/../../lib/ViewComponents.php';
require_once __DIR__ . '/../../lib/CrudView.php';
require_once __DIR__ . '/../../views/admin/DashboardView.php';

class AdminController {
    private $userModel;
    private $membreModel;
    private $projetModel;
    private $publicationModel;
    
    public function __construct() {
        AuthController::requireAdmin();
        AuthController::checkSessionTimeout();
        
        $this->userModel = new UserModel();
        $this->membreModel = new MembreModel();
        $this->projetModel = new ProjetModel();
        $this->publicationModel = new PublicationModel();
    }
    
    /**
     * Dashboard admin
     */
    public function dashboard() {
        $stats = [
            'total_users' => count($this->userModel->getAll()),
            'total_membres' => count($this->membreModel->getAll()),
            'total_projets' => count($this->projetModel->getAll()),
            'total_publications' => count($this->publicationModel->getAll()),
        ];

        $dashboard = new DashboardView($stats, session('username'));
        $dashboard->render();
    }
    

   
    // ============================================
    // GESTION DES ÉVÉNEMENTS
    // ============================================
    
    public function evenements() {
        require_once __DIR__ . '/../../models/EvenementModel.php';
        $evenementModel = new EvenementModel();

        // === Filtres ===
        $filters = [
            'type_evenement' => get('type_evenement'),
            'statut' => get('statut'),
            'search' => get('search')
        ];

        // Récupérer tous les événements avec les organisateurs
        $evenements = $evenementModel->getAllWithOrganisateurs();

        // Appliquer la recherche si nécessaire
        if (!empty($filters['search'])) {
            $evenements = array_filter($evenements, function($e) use ($filters) {
                return stripos($e['titre'], $filters['search']) !== false
                    || stripos($e['organisateur_nom'] ?? '', $filters['search']) !== false;
            });
        }

        // Filtrer par type
        if (!empty($filters['type_evenement'])) {
            $evenements = array_filter($evenements, function($e) use ($filters) {
                return $e['type_evenement'] === $filters['type_evenement'];
            });
        }

        // Filtrer par statut
        if (!empty($filters['statut'])) {
            $evenements = array_filter($evenements, function($e) use ($filters) {
                if ($filters['statut'] === 'à venir') {
                    return strtotime($e['date_evenement']) > time();
                } elseif ($filters['statut'] === 'en cours') {
                    $now = time();
                    return strtotime($e['date_evenement']) <= $now;
                } else { // terminé
                    return strtotime($e['date_evenement']) < time();
                }
            });
        }

        // === Pagination ===
        $page = (int) get('page', 1);
        $perPage = 10;
        $pagination = Utils::paginate(count($evenements), $perPage, $page);
        $evenements = array_slice($evenements, $pagination['offset'], $perPage);

        // Charger la vue
        require_once __DIR__ . '/../../views/admin/evenements.php';
    }
    
    // ============================================
    // PARAMÈTRES
    // ============================================
    
    public function parametres() {
        // Récupérer les paramètres
        $settings = [];
        $backups = [];
        
        // Charger la vue
        require_once __DIR__ . '/../../views/admin/parametres/parametres.php';
    }
}
?>