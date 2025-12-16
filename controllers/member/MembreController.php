<?php
/**
 * MembreController.php - Contrôleur pour l'espace membre
 * À créer dans : controllers/member/MembreController.php
 */

require_once __DIR__ . '/../../models/MembreModel.php';
require_once __DIR__ . '/../../models/ProjetModel.php';
require_once __DIR__ . '/../../models/PublicationModel.php';
require_once __DIR__ . '/../../models/EquipementModel.php';
require_once __DIR__ . '/../../models/EvenementModel.php';
require_once __DIR__ . '/../../lib/helpers.php';
require_once __DIR__ . '/../../lib/ViewComponents.php';
require_once __DIR__ . '/../auth/AuthController.php';

class MembreController {
    private $membreModel;
    private $projetModel;
    private $publicationModel;
    private $equipementModel;
    private $evenementModel;
    private $membreId;
    private $membre;
    
    public function __construct() {
        AuthController::requireMembre();
        AuthController::checkSessionTimeout();
        
        $this->membreModel = new MembreModel();
        $this->projetModel = new ProjetModel();
        $this->publicationModel = new PublicationModel();
        $this->equipementModel = new EquipementModel();
        $this->evenementModel = new EvenementModel();
        
        // Récupérer l'ID du membre connecté
        $userId = session('user_id');
        $this->membre = $this->membreModel->getByUserId($userId);
        $this->membreId = $this->membre['id'] ?? null;
        
        // Si le membre n'existe pas, créer un tableau par défaut
        if (!$this->membre) {
            $this->membre = [
                'nom' => session('username') ?? 'Utilisateur',
                'prenom' => '',
                'grade' => 'Membre',
                'domaine_recherche' => '',
                'photo' => ''
            ];
        }
    }
    
    /**
     * Dashboard membre
     */
    public function dashboard() {
        // Statistiques personnelles
        $stats = [
            'mes_projets' => $this->projetModel->countByMembre($this->membreId),
            'mes_publications' => $this->publicationModel->countByMembre($this->membreId),
            'reservations_actives' => $this->equipementModel->countReservationsByMembre($this->membreId),
            'evenements_a_venir' => $this->evenementModel->countUpcoming()
        ];
        
        // Projets récents
        $mesProjets = $this->projetModel->getByMembre($this->membreId, 5);
        
        // Publications récentes
        $mesPublications = $this->publicationModel->getByMembre($this->membreId, 5);
        
        // Réservations actives
        $mesReservations = $this->equipementModel->getReservationsByMembre($this->membreId);
        
        // Événements à venir
        $evenements = $this->evenementModel->getUpcoming(5);
        
        // Variable membre pour la vue
        $membre = $this->membre;
        
        // Charger la vue
        require_once __DIR__ . '/../../views/member/dashboard.php';
    }
    
    /**
     * Profil du membre
     */
    public function profil() {
        $membre = $this->membreModel->getById($this->membreId);
        
        if (!$membre) {
            redirect('membre/dashboard');
        }
        
        // Statistiques complètes
        $stats = [
            'total_projets' => $this->projetModel->countByMembre($this->membreId),
            'total_publications' => $this->publicationModel->countByMembre($this->membreId),
            'projets_en_cours' => $this->projetModel->countByMembreAndStatus($this->membreId, 'en_cours'),
            'publications_validees' => $this->publicationModel->countByMembreAndStatus($this->membreId, 'validé')
        ];
        
        require_once __DIR__ . '/../../views/member/profil.php';
    }
    
    /**
     * Mettre à jour le profil
     */
    public function updateProfil() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('membre/profil');
        }
        
        $data = [
            'nom' => post('nom'),
            'prenom' => post('prenom'),
            'email' => post('email'),
            'specialite' => post('specialite'),
            'telephone' => post('telephone'),
            'adresse' => post('adresse')
        ];
        
        // Validation
        if (empty($data['nom']) || empty($data['prenom']) || empty($data['email'])) {
            flash('error', 'Veuillez remplir tous les champs obligatoires');
            redirect('membre/profil');
        }
        
        // Mise à jour
        if ($this->membreModel->update($this->membreId, $data)) {
            flash('success', 'Profil mis à jour avec succès');
        } else {
            flash('error', 'Erreur lors de la mise à jour du profil');
        }
        
        redirect('membre/profil');
    }
    
    /**
     * Mes projets
     */
    public function projets() {
        // Filtres
        $filters = [
            'statut' => get('statut'),
            'search' => get('search')
        ];
        
        // Récupérer les projets
        $projets = $this->projetModel->getByMembre($this->membreId);
        
        // Appliquer les filtres
        if (!empty($filters['search'])) {
            $projets = array_filter($projets, function($p) use ($filters) {
                return stripos($p['titre'], $filters['search']) !== false;
            });
        }
        
        if (!empty($filters['statut'])) {
            $projets = array_filter($projets, function($p) use ($filters) {
                return $p['statut'] === $filters['statut'];
            });
        }
        
        // Pagination
        $page = (int) get('page', 1);
        $perPage = 10;
        $pagination = Utils::paginate(count($projets), $perPage, $page);
        $projets = array_slice($projets, $pagination['offset'], $perPage);
        
        require_once __DIR__ . '/../../views/member/projets.php';
    }
    
    /**
     * Détail d'un projet
     */
    public function projetDetail($id) {
        $projet = $this->projetModel->getById($id);
        
        if (!$projet) {
            flash('error', 'Projet introuvable');
            redirect('membre/projets');
        }
        
        // Vérifier que le membre fait partie du projet
        $membres = $this->projetModel->getMembres($id);
        $isMembre = false;
        foreach ($membres as $m) {
            if ($m['membre_id'] == $this->membreId) {
                $isMembre = true;
                break;
            }
        }
        
        if (!$isMembre) {
            flash('error', 'Vous n\'avez pas accès à ce projet');
            redirect('membre/projets');
        }
        
        require_once __DIR__ . '/../../views/member/projet-detail.php';
    }
    
    /**
     * Mes publications
     */
    public function publications() {
        // Filtres
        $filters = [
            'statut' => get('statut'),
            'type' => get('type'),
            'search' => get('search')
        ];
        
        // Récupérer les publications
        $publications = $this->publicationModel->getByMembre($this->membreId);
        
        // Appliquer les filtres
        if (!empty($filters['search'])) {
            $publications = array_filter($publications, function($p) use ($filters) {
                return stripos($p['titre'], $filters['search']) !== false;
            });
        }
        
        if (!empty($filters['statut'])) {
            $publications = array_filter($publications, function($p) use ($filters) {
                return $p['statut'] === $filters['statut'];
            });
        }
        
        if (!empty($filters['type'])) {
            $publications = array_filter($publications, function($p) use ($filters) {
                return $p['type_publication'] === $filters['type'];
            });
        }
        
        // Pagination
        $page = (int) get('page', 1);
        $perPage = 10;
        $pagination = Utils::paginate(count($publications), $perPage, $page);
        $publications = array_slice($publications, $pagination['offset'], $perPage);
        
        require_once __DIR__ . '/../../views/member/publications.php';
    }
    
    /**
     * Soumettre une nouvelle publication
     */
    public function soumettrePublication() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('membre/publications');
        }
        
        $data = [
            'titre' => post('titre'),
            'type_publication' => post('type_publication'),
            'resume' => post('resume'),
            'annee_publication' => post('annee_publication'),
            'editeur' => post('editeur'),
            'conference' => post('conference'),
            'doi' => post('doi'),
            'url' => post('url'),
            'statut' => 'en_attente',
            'membre_id' => $this->membreId
        ];
        
        // Validation
        if (empty($data['titre']) || empty($data['type_publication'])) {
            flash('error', 'Veuillez remplir tous les champs obligatoires');
            redirect('membre/publications');
        }
        
        // Créer la publication
        if ($this->publicationModel->create($data)) {
            flash('success', 'Publication soumise avec succès. En attente de validation.');
        } else {
            flash('error', 'Erreur lors de la soumission de la publication');
        }
        
        redirect('membre/publications');
    }
    
    /**
     * Réservations d'équipements
     */
    public function reservations() {
        // Récupérer les réservations
        $reservations = $this->equipementModel->getReservationsByMembre($this->membreId);
        
        // Séparer par statut
        $actives = array_filter($reservations, function($r) {
            return $r['statut'] === 'confirmée' || $r['statut'] === 'en_attente';
        });
        
        $historique = array_filter($reservations, function($r) {
            return $r['statut'] === 'terminée' || $r['statut'] === 'annulée';
        });
        
        // Équipements disponibles
        $equipements = $this->equipementModel->getByStatus('disponible');
        
        require_once __DIR__ . '/../../views/member/reservations.php';
    }
    
    /**
     * Créer une réservation
     */
    public function creerReservation() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('membre/reservations');
        }
        
        $data = [
            'equipement_id' => post('equipement_id'),
            'membre_id' => $this->membreId,
            'date_debut' => post('date_debut'),
            'date_fin' => post('date_fin'),
            'motif' => post('motif'),
            'statut' => 'en_attente'
        ];
        
        // Validation
        if (empty($data['equipement_id']) || empty($data['date_debut']) || empty($data['date_fin'])) {
            flash('error', 'Veuillez remplir tous les champs obligatoires');
            redirect('membre/reservations');
        }
        
        // Vérifier la disponibilité
        if (!$this->equipementModel->isAvailable($data['equipement_id'], $data['date_debut'], $data['date_fin'])) {
            flash('error', 'Équipement non disponible pour cette période');
            redirect('membre/reservations');
        }
        
        // Créer la réservation
        if ($this->equipementModel->createReservation($data)) {
            flash('success', 'Réservation créée avec succès');
        } else {
            flash('error', 'Erreur lors de la création de la réservation');
        }
        
        redirect('membre/reservations');
    }
    
    /**
     * Annuler une réservation
     */
    public function annulerReservation($id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('membre/reservations');
        }
        
        // Vérifier que la réservation appartient au membre
        $reservation = $this->equipementModel->getReservationById($id);
        
        if (!$reservation || $reservation['membre_id'] != $this->membreId) {
            flash('error', 'Réservation introuvable');
            redirect('membre/reservations');
        }
        
        // Annuler la réservation
        if ($this->equipementModel->updateReservation($id, ['statut' => 'annulée'])) {
            flash('success', 'Réservation annulée');
        } else {
            flash('error', 'Erreur lors de l\'annulation');
        }
        
        redirect('membre/reservations');
    }
    
    /**
     * Événements
     */
    public function evenements() {
        // Récupérer tous les événements à venir
        $evenements = $this->evenementModel->getUpcoming();
        
        // Filtres
        $type = get('type');
        if (!empty($type)) {
            $evenements = array_filter($evenements, function($e) use ($type) {
                return $e['type_evenement'] === $type;
            });
        }
        
        require_once __DIR__ . '/../../views/member/evenements.php';
    }
}
?>