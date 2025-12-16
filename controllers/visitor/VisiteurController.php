<?php
/**
 * VisiteurController.php - Contrôleur pour les pages publiques
 * À créer dans : controllers/visitor/VisiteurController.php
 */

require_once __DIR__ . '/../../models/ProjetModel.php';
require_once __DIR__ . '/../../models/PublicationModel.php';
require_once __DIR__ . '/../../models/EquipeModel.php';
require_once __DIR__ . '/../../models/MembreModel.php';
require_once __DIR__ . '/../../models/EvenementModel.php';
require_once __DIR__ . '/../../lib/helpers.php';
require_once __DIR__ . '/../../lib/ViewComponents.php';

class VisiteurController {
    private $projetModel;
    private $publicationModel;
    private $equipeModel;
    private $membreModel;
    private $evenementModel;
    
    public function __construct() {
        $this->projetModel = new ProjetModel();
        $this->publicationModel = new PublicationModel();
        $this->equipeModel = new EquipeModel();
        $this->membreModel = new MembreModel();
        $this->evenementModel = new EvenementModel();
    }
    
    /**
     * Page d'accueil
     */
    public function index() {
        // Statistiques générales
        $stats = [
            'total_projets' => $this->projetModel->count(),
            'total_publications' => $this->publicationModel->count(),
            'total_membres' => $this->membreModel->count(),
            'total_equipes' => $this->equipeModel->count()
        ];
        
        // Projets récents
        $projetsRecents = $this->projetModel->getRecent(6);
        
        // Publications récentes
        $publicationsRecentes = $this->publicationModel->getRecent(5);
        
        // Événements à venir
        $evenements = $this->evenementModel->getUpcoming(3);
        
        require_once __DIR__ . '/../../views/visitor/index.php';
    }
    
    /**
     * Page À propos
     */
    public function apropos() {
        $stats = [
            'total_projets' => $this->projetModel->count(),
            'total_publications' => $this->publicationModel->count(),
            'total_membres' => $this->membreModel->count(),
            'annee_creation' => 2010
        ];
        
        require_once __DIR__ . '/../../views/visitor/apropos.php';
    }
    
    /**
     * Liste des projets
     */
    public function projets() {
        $filters = [
            'statut' => get('statut'),
            'domaine' => get('domaine'),
            'search' => get('search')
        ];
        
        $projets = $this->projetModel->getAllPublic();
        
        // Appliquer les filtres
        if (!empty($filters['search'])) {
            $projets = array_filter($projets, function($p) use ($filters) {
                return stripos($p['titre'], $filters['search']) !== false ||
                       stripos($p['descriptif'], $filters['search']) !== false;
            });
        }
        
        if (!empty($filters['statut'])) {
            $projets = array_filter($projets, function($p) use ($filters) {
                return $p['statut'] === $filters['statut'];
            });
        }
        
        if (!empty($filters['domaine'])) {
            $projets = array_filter($projets, function($p) use ($filters) {
                return $p['domaine_recherche'] === $filters['domaine'];
            });
        }
        
        // Pagination
        $page = (int) get('page', 1);
        $perPage = 12;
        $pagination = Utils::paginate(count($projets), $perPage, $page);
        $projets = array_slice($projets, $pagination['offset'], $perPage);
        
        require_once __DIR__ . '/../../views/visitor/projets.php';
    }
    
    /**
     * Détail d'un projet
     */
    public function projetDetail($id) {
        $projet = $this->projetModel->getById($id);
        
        if (!$projet || $projet['statut'] !== 'en_cours') {
            flash('error', 'Projet introuvable');
            redirect('projets');
        }
        
        $membres = $this->projetModel->getMembres($id);
        $publications = $this->publicationModel->getByProjet($id);
        
        require_once __DIR__ . '/../../views/visitor/projet-detail.php';
    }
    
    /**
     * Liste des publications
     */
    public function publications() {
        $filters = [
            'type' => get('type'),
            'annee' => get('annee'),
            'search' => get('search')
        ];
        
        $publications = $this->publicationModel->getAllPublic();
        
        // Appliquer les filtres
        if (!empty($filters['search'])) {
            $publications = array_filter($publications, function($p) use ($filters) {
                return stripos($p['titre'], $filters['search']) !== false;
            });
        }
        
        if (!empty($filters['type'])) {
            $publications = array_filter($publications, function($p) use ($filters) {
                return $p['type_publication'] === $filters['type'];
            });
        }
        
        if (!empty($filters['annee'])) {
            $publications = array_filter($publications, function($p) use ($filters) {
                return $p['annee_publication'] == $filters['annee'];
            });
        }
        
        // Pagination
        $page = (int) get('page', 1);
        $perPage = 15;
        $pagination = Utils::paginate(count($publications), $perPage, $page);
        $publications = array_slice($publications, $pagination['offset'], $perPage);
        
        require_once __DIR__ . '/../../views/visitor/publications.php';
    }
    
    /**
     * Détail d'une publication
     */
    public function publicationDetail($id) {
        $publication = $this->publicationModel->getById($id);
        
        if (!$publication || $publication['statut'] !== 'validé') {
            flash('error', 'Publication introuvable');
            redirect('publications');
        }
        
        require_once __DIR__ . '/../../views/visitor/publication-detail.php';
    }
    
    /**
     * Liste des équipes
     */
    public function equipes() {
        $equipes = $this->equipeModel->getAll();
        
        require_once __DIR__ . '/../../views/visitor/equipes.php';
    }
    
    /**
     * Détail d'une équipe
     */
    public function equipeDetail($id) {
        $equipe = $this->equipeModel->getById($id);
        
        if (!$equipe) {
            flash('error', 'Équipe introuvable');
            redirect('equipes');
        }
        
        $membres = $this->equipeModel->getMembres($id);
        $projets = $this->projetModel->getByEquipe($id);
        
        require_once __DIR__ . '/../../views/visitor/equipe-detail.php';
    }
    
    /**
     * Liste des membres
     */
    public function membres() {
        $membres = $this->membreModel->getAllPublic();
        
        require_once __DIR__ . '/../../views/visitor/membres.php';
    }
    
    /**
     * Détail d'un membre
     */
    public function membreDetail($id) {
        $membre = $this->membreModel->getById($id);
        
        if (!$membre) {
            flash('error', 'Membre introuvable');
            redirect('membres');
        }
        
        $projets = $this->projetModel->getByMembre($id);
        $publications = $this->publicationModel->getByMembre($id);
        
        require_once __DIR__ . '/../../views/visitor/membre-detail.php';
    }
    
    /**
     * Liste des événements
     */
    public function evenements() {
        $filters = [
            'type' => get('type'),
            'mois' => get('mois')
        ];
        
        $evenements = $this->evenementModel->getUpcoming();
        
        // Appliquer les filtres
        if (!empty($filters['type'])) {
            $evenements = array_filter($evenements, function($e) use ($filters) {
                return $e['type_evenement'] === $filters['type'];
            });
        }
        
        if (!empty($filters['mois'])) {
            $evenements = array_filter($evenements, function($e) use ($filters) {
                return date('Y-m', strtotime($e['date_evenement'])) === $filters['mois'];
            });
        }
        
        require_once __DIR__ . '/../../views/visitor/evenements.php';
    }
    
    /**
     * Détail d'un événement
     */
    public function evenementDetail($id) {
        $evenement = $this->evenementModel->getById($id);
        
        if (!$evenement) {
            flash('error', 'Événement introuvable');
            redirect('evenements');
        }
        
        require_once __DIR__ . '/../../views/visitor/evenement-detail.php';
    }
    
    /**
     * Actualités
     */
    public function actualites() {
        // Récupérer les dernières publications et événements
        $publications = $this->publicationModel->getRecent(5);
        $evenements = $this->evenementModel->getRecent(5);
        
        // Combiner et trier par date
        $actualites = [];
        
        foreach ($publications as $pub) {
            $actualites[] = [
                'type' => 'publication',
                'date' => $pub['created_at'],
                'data' => $pub
            ];
        }
        
        foreach ($evenements as $event) {
            $actualites[] = [
                'type' => 'evenement',
                'date' => $event['date_evenement'],
                'data' => $event
            ];
        }
        
        // Trier par date décroissante
        usort($actualites, function($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });
        
        require_once __DIR__ . '/../../views/visitor/actualites.php';
    }
    
    /**
     * Page de contact
     */
    public function contact() {
        require_once __DIR__ . '/../../views/visitor/contact.php';
    }
    
    /**
     * Envoyer un message de contact
     */
    public function envoyerContact() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('contact');
        }
        
        $data = [
            'nom' => post('nom'),
            'email' => post('email'),
            'sujet' => post('sujet'),
            'message' => post('message')
        ];
        
        // Validation
        if (empty($data['nom']) || empty($data['email']) || empty($data['message'])) {
            flash('error', 'Veuillez remplir tous les champs obligatoires');
            redirect('contact');
        }
        
        if (!Utils::validateEmail($data['email'])) {
            flash('error', 'Adresse email invalide');
            redirect('contact');
        }
        
        // TODO: Envoyer l'email ou sauvegarder dans la base
        // Pour l'instant, on simule l'envoi
        flash('success', 'Votre message a été envoyé avec succès. Nous vous répondrons dans les plus brefs délais.');
        redirect('contact');
    }
}
?>