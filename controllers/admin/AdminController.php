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
require_once __DIR__ . '/../../views/admin/dashboard.php';

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
        
        $dashboardView = new Dashboard($stats, session('username'));
        $dashboardView->render();
    }
    
    // ============================================
    // GESTION DES UTILISATEURS
    // ============================================
    
    public function users() {
        $users = $this->userModel->getAll();
        
        // Pagination
        $page = (int) get('page', 1);
        $perPage = 10;
        $pagination = Utils::paginate(count($users), $perPage, $page);
        $users = array_slice($users, $pagination['offset'], $perPage);
        
        // Charger la vue
        require_once __DIR__ . '/../../views/admin/users.php';
    }
    
   
    
    // ============================================
    // GESTION DES PROJETS
    // ============================================
    
public function projets() {
    // Récupérer les filtres depuis l'URL
    $filters = [
        'thematique' => get('thematique'),
        'statut' => get('statut'),
        'search' => get('search')
    ];
    
    // Utiliser la nouvelle méthode du modèle
    $projets = $this->projetModel->getAllFiltered($filters);
    
    // Pagination
    $page = (int) get('page', 1);
    $perPage = 10;
    $pagination = Utils::paginate(count($projets), $perPage, $page);
    $projets = array_slice($projets, $pagination['offset'], $perPage);
    
    // Charger la vue
    require_once __DIR__ . '/../../views/admin/projets.php';
}

    
    public function exportProjets() {
        if (get('export') === 'csv') {
            $projets = $this->projetModel->getAllWithResponsables();
            
            $data = [];
            $data[] = ['Titre', 'Thématique', 'Responsable', 'Statut', 'Date début'];
            
            foreach ($projets as $projet) {
                $data[] = [
                    $projet['titre'],
                    $projet['thematique'],
                    $projet['responsable_nom'] ?? '',
                    $projet['statut'],
                    format_date($projet['date_debut'])
                ];
            }
            
            LabHelpers::exportToCsv($data, 'projets_' . date('Y-m-d') . '.csv');
        }
    }
    
    // ============================================
    // GESTION DES ÉQUIPEMENTS
    // ============================================
    
    public function equipements() {
        require_once __DIR__ . '/../../models/EquipementModel.php';
        $equipementModel = new EquipementModel();

        // Récupérer les équipements selon filtres
        $filters = [
            'type_equipement' => get('type_equipement'),
            'etat' => get('etat'),
            'localisation' => get('localisation'),
            'search' => get('search')
        ];

        $equipements = $equipementModel->getAllFiltered($filters);

        // Pagination
        $page = (int) get('page', 1);
        $perPage = 10;
        $pagination = Utils::paginate(count($equipements), $perPage, $page);
        $equipements = array_slice($equipements, $pagination['offset'], $perPage);

        require __DIR__ . '/../../views/admin/equipements.php';
    }
    
    // ============================================
    // GESTION DES PUBLICATIONS
    // ============================================
    
    public function publications() {
    // Récupérer les filtres depuis l'URL
    $filters = [
        'type_publication' => get('type_publication'),
        'domaine' => get('domaine'),
        'annee' => get('annee'),
        'search' => get('search')
    ];
    
    // Utiliser la nouvelle méthode du modèle
    $publications = $this->publicationModel->getAllFiltered($filters);
    
    // Pagination
    $page = (int) get('page', 1);
    $perPage = 10;
    $pagination = Utils::paginate(count($publications), $perPage, $page);
    $publications = array_slice($publications, $pagination['offset'], $perPage);
    
    // Charger la vue
    require_once __DIR__ . '/../../views/admin/publications.php';
}

/**
 * Exporter les publications en CSV
 */
public function exportPublications() {
    if (get('export') === 'csv') {
        // Appliquer les mêmes filtres que la page
        $filters = [
            'type_publication' => get('type_publication'),
            'domaine' => get('domaine'),
            'annee' => get('annee'),
            'search' => get('search')
        ];
        
        $publications = $this->publicationModel->getAllFiltered($filters);
        
        $data = [];
        $data[] = ['Titre', 'Type', 'Auteur', 'Date', 'DOI', 'Nb Auteurs'];
        
        foreach ($publications as $pub) {
            $data[] = [
                $pub['titre'],
                $pub['type_publication'],
                $pub['auteur_nom'] ?? '',
                format_date($pub['date_publication']),
                $pub['doi'] ?? '',
                $pub['nb_auteurs'] ?? 0
            ];
        }
        
        LabHelpers::exportToCsv($data, 'publications_' . date('Y-m-d') . '.csv');
    }
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
        require_once __DIR__ . '/../../views/admin/parametres.php';
    }
}
?>