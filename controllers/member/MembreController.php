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
        AuthController::requireMembre();
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
     * Mes projets
     */
    public function projets() {
        AuthController::requireMembre();
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
        AuthController::requireMembre();
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
        AuthController::requireMembre();
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
        AuthController::requireMembre();
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
        AuthController::requireMembre();
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
        AuthController::requireMembre();
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
        AuthController::requireMembre();
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
        AuthController::requireMembre();
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

    public function profil() {
    AuthController::requireMembre();
    
    // Récupérer le membre avec toutes ses informations
    $membre = $this->membreModel->getWithDetails($this->membreId);
    
    if (!$membre) {
        // Si le membre n'existe pas, utiliser les données de base de la session
        $membre = [
            'id' => $this->membreId,
            'nom' => '',
            'prenom' => '',
            'poste' => 'enseignant',
            'grade' => '',
            'specialite' => '',
            'telephone' => '',
            'adresse' => '',
            'biographie' => '',
            'photo' => ''
        ];
    }
    
    // Statistiques complètes
    $stats = [
        'total_projets' => $this->projetModel->countByMembre($this->membreId),
        'total_publications' => $this->publicationModel->countByMembre($this->membreId),
        'projets_en_cours' => $this->projetModel->countByMembreAndStatus($this->membreId, 'en_cours'),
        'publications_validees' => $this->publicationModel->countByMembreAndStatus($this->membreId, 'valide')
    ];
    
    require_once __DIR__ . '/../../views/member/profil.php';
}

/**
 * Mettre à jour le profil 
 */
public function updateProfil() {
    AuthController::requireMembre();
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ' . base_url('membre/profil'));
        exit;
    }
    
    try {
        $data = [
            'nom' => trim(post('nom', '')),
            'prenom' => trim(post('prenom', '')),
            'poste' => post('poste', 'enseignant'),
            'grade' => post('grade', ''),
            'specialite' => trim(post('specialite', '')),
            'telephone' => trim(post('telephone', '')),
            'adresse' => trim(post('adresse', '')),
            'biographie' => trim(post('biographie', ''))
        ];
        
        // Validation des champs obligatoires
        if (empty($data['nom']) || empty($data['prenom'])) {
            flash('error', 'Le nom et le prénom sont obligatoires');
            header('Location: ' . base_url('membre/profil'));
            exit;
        }
        
        // Gestion de la photo
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $uploadResult = $this->handlePhotoUpload($_FILES['photo']);
            
            if ($uploadResult['success']) {
                $data['photo'] = $uploadResult['filename'];
                
                // Supprimer l'ancienne photo si elle existe
                $oldMembre = $this->membreModel->getById($this->membreId);
                if (!empty($oldMembre['photo']) && file_exists(__DIR__ . '/../../uploads/photos/' . $oldMembre['photo'])) {
                    unlink(__DIR__ . '/../../uploads/photos/' . $oldMembre['photo']);
                }
            } else {
                flash('error', $uploadResult['message']);
                header('Location: ' . base_url('membre/profil'));
                exit;
            }
        }
        
        // Mettre à jour l'email dans la table User
        $email = trim(post('email', ''));
        if (!empty($email)) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                flash('error', 'Adresse email invalide');
                header('Location: ' . base_url('membre/profil'));
                exit;
            }
            
            // Mettre à jour l'email dans User
            $userId = session('user_id');
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("UPDATE User SET email = ? WHERE id = ?");
            $stmt->execute([$email, $userId]);
            
            // Mettre à jour la session
            $_SESSION['email'] = $email;
        }
        
        // Mise à jour du membre
        if ($this->membreModel->update($this->membreId, $data)) {
            flash('success', 'Profil mis à jour avec succès');
        } else {
            flash('error', 'Erreur lors de la mise à jour du profil');
        }
        
    } catch (Exception $e) {
        error_log("Erreur updateProfil: " . $e->getMessage());
        flash('error', 'Une erreur est survenue lors de la mise à jour');
    }
    
    header('Location: ' . base_url('membre/profil'));
    exit;
}

/**
 * Changer le mot de passe 
 */
public function changePassword() {
    AuthController::requireMembre();
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ' . base_url('membre/profil'));
        exit;
    }
    
    try {
        $currentPassword = post('current_password', '');
        $newPassword = post('new_password', '');
        $confirmPassword = post('confirm_password', '');
        
        // Validation
        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            flash('error', 'Tous les champs sont obligatoires');
            header('Location: ' . base_url('membre/profil'));
            exit;
        }
        
        if (strlen($newPassword) < 6) {
            flash('error', 'Le mot de passe doit contenir au moins 6 caractères');
            header('Location: ' . base_url('membre/profil'));
            exit;
        }
        
        if ($newPassword !== $confirmPassword) {
            flash('error', 'Les mots de passe ne correspondent pas');
            header('Location: ' . base_url('membre/profil'));
            exit;
        }
        
        // Vérifier le mot de passe actuel
        $userId = session('user_id');
        $db = Database::getInstance()->getConnection();
        
        $stmt = $db->prepare("SELECT password FROM User WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if (!$user || !password_verify($currentPassword, $user['password'])) {
            flash('error', 'Mot de passe actuel incorrect');
            header('Location: ' . base_url('membre/profil'));
            exit;
        }
        
        // Mettre à jour le mot de passe
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $db->prepare("UPDATE User SET password = ? WHERE id = ?");
        
        if ($stmt->execute([$hashedPassword, $userId])) {
            flash('success', 'Mot de passe modifié avec succès');
        } else {
            flash('error', 'Erreur lors de la modification du mot de passe');
        }
        
    } catch (Exception $e) {
        error_log("Erreur changePassword: " . $e->getMessage());
        flash('error', 'Une erreur est survenue');
    }
    
    header('Location: ' . base_url('membre/profil'));
    exit;
}
/**
 * Gérer l'upload de photo
 */
private function handlePhotoUpload($file) {
    // Vérifier le type de fichier
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    $fileType = $file['type'];
    
    if (!in_array($fileType, $allowedTypes)) {
        return [
            'success' => false,
            'message' => 'Type de fichier non autorisé. Utilisez JPG, PNG ou GIF.'
        ];
    }
    
    // Vérifier la taille (max 5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        return [
            'success' => false,
            'message' => 'Le fichier ne doit pas dépasser 5 MB'
        ];
    }
    
    // Créer le dossier si nécessaire
    $uploadDir = __DIR__ . '/../../uploads/photos/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Générer un nom unique
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'membre_' . $this->membreId . '_' . time() . '.' . $extension;
    $filepath = $uploadDir . $filename;
    
    // Déplacer le fichier
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return [
            'success' => true,
            'filename' => $filename
        ];
    }
    
    return [
        'success' => false,
        'message' => 'Erreur lors de l\'upload du fichier'
    ];
}
}
?>