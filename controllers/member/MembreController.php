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
require_once __DIR__ . '/../../views/member/ProjetsView.php';
require_once __DIR__ . '/../../views/member/ProjetDetail.php';
require_once __DIR__ . '/../../views/member/PublicationsView.php';
require_once __DIR__ . '/../../views/member/ReservationsListView.php';
require_once __DIR__ . '/../../views/member/EvenementsListView.php';
require_once __DIR__ . '/../../views/member/ProfilView.php';
require_once __DIR__ . '/../../views/member/MembreDashboardView.php';


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

        $username = $membre['prenom'] . ' ' . $membre['nom'];
        
        // Charger la vue
       $dashboard = new MembreDashboardView(
            $stats,
            $membre,
            $mesProjets,
            $mesPublications,
            $mesReservations,
            $evenements,
            $username
        );
        $dashboard->render();
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

        // ProjetsView.php
           $view = new ProjetsView($projets, $pagination);
           $view->render();
    }
    
        /**
     * Détail d'un projet
     */
    public function projetDetail($id) {
        $projet = $this->projetModel->getById($id);
        
        if (!$projet) {
            $_SESSION['error'] = 'Projet non trouvé';
            redirect(base_url('projets'));
        }
        
        $membres = $this->projetModel->getMembres($id);
        $publications = $this->projetModel->getPublications($id);
        
        $responsable = null;
        if (!empty($projet['responsable_id'])) {
            $responsable = $this->membreModel->getById($projet['responsable_id']);
            
            if ($responsable) {
                $user = $this->membreModel->getUserByMembreId($responsable['id']);
                if ($user) {
                    $responsable['username'] = $user['username'];
                    $responsable['email'] = $user['email'];
                }
            }
        }
        
        $stats = [
            'nb_membres' => count($membres),
            'nb_publications' => count($publications),
            'progression' => LabHelpers::calculateProjectProgress(
                $projet['date_debut'], 
                $projet['date_fin'] ?? null
            )
        ];
        
        // ProjetDetail.php
           $view = new ProjetDetail($projet, $membres, $publications, $responsable, $stats);
           $view->render(); 
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

        // PublicationsView.php
           $view = new PublicationsView($publications, $pagination);
           $view->render();
    }
    
    /**
     * Soumettre une nouvelle publication
     */
  public function soumettrePublication() {
    // Redirect to the new dedicated controller
    require_once __DIR__ . '/PublicationController.php';
    $controller = new PublicationController();
    return $controller->createPublication();
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
        
        
            // ReservationsListView.php
            $view = new ReservationsListView($actives, $historique, $equipements);
            $view->render();
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
        
        // EvenementsListView.php
           $view = new EvenementsListView($evenements);
            $view->render();
    }

    public function profil() {
    AuthController::requireMembre();
    
    // Récupérer le membre avec toutes ses informations
    $membre = $this->membreModel->getWithDetails($this->membreId);
    $user = $this->membreModel->getUserByMembreId($this->membreId);
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
    
    // ProfilView.php
        $view = new ProfilView($user, $membre, $stats);
        $view->render();

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
    
    error_log("=== UPDATE PROFIL START ===");
    error_log("POST data: " . print_r($_POST, true));
    error_log("FILES data: " . print_r($_FILES, true));
    
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
        
        error_log("Data to update: " . print_r($data, true));
        
        // Validation des champs obligatoires
        if (empty($data['nom']) || empty($data['prenom'])) {
            error_log("ERROR: Nom ou prénom vide");
            flash('error', 'Le nom et le prénom sont obligatoires');
            header('Location: ' . base_url('membre/profil'));
            exit;
        }
        
        // Gestion de la photo
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            error_log("Photo file detected, processing upload...");
            $uploadResult = $this->handlePhotoUpload($_FILES['photo']);
            
          if ($uploadResult['success']) {
                // Save with 'photos/' prefix to match database
                $data['photo'] = $uploadResult['filename']; // Just the filename
                
                // Delete old photo
                $oldMembre = $this->membreModel->getById($this->membreId);
                if (!empty($oldMembre['photo'])) {
                    $oldPhotoPath = realpath(__DIR__ . '/../../') . '/uploads/photos/' . $oldMembre['photo'];
                    if (file_exists($oldPhotoPath)) {
                        unlink($oldPhotoPath);
                    }
                }
            }else {
                error_log("Photo upload failed: " . $uploadResult['message']);
                flash('error', $uploadResult['message']);
                header('Location: ' . base_url('membre/profil'));
                exit;
            }
        } else if (isset($_FILES['photo']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE) {
            error_log("Photo upload error: " . $_FILES['photo']['error']);
        }
        
        // Mettre à jour l'email dans la table User
        $email = trim(post('email', ''));
        if (!empty($email)) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                error_log("ERROR: Invalid email format");
                flash('error', 'Adresse email invalide');
                header('Location: ' . base_url('membre/profil'));
                exit;
            }
            
            // Mettre à jour l'email dans User
            $userId = session('user_id');
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("UPDATE User SET email = ? WHERE id = ?");
            if ($stmt->execute([$email, $userId])) {
                error_log("Email updated successfully");
                $_SESSION['email'] = $email;
            }
        }
        
        // Mise à jour du membre - FIXED: Use updateProfil instead of update
        error_log("Calling updateProfil with membre_id: " . $this->membreId);
        if ($this->membreModel->updateProfil($this->membreId, $data)) {
            error_log("=== UPDATE PROFIL SUCCESS ===");
            flash('success', 'Profil mis à jour avec succès');
        } else {
            error_log("=== UPDATE PROFIL FAILED ===");
            flash('error', 'Erreur lors de la mise à jour du profil');
        }
        
    } catch (Exception $e) {
        error_log("=== UPDATE PROFIL EXCEPTION ===");
        error_log("Exception: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        flash('error', 'Une erreur est survenue: ' . $e->getMessage());
    }
    
    header('Location: ' . base_url('membre/profil'));
    exit;
}

/**
 * Gérer l'upload de photo - FIXED VERSION
 */
private function handlePhotoUpload($file) {
    error_log(">>> handlePhotoUpload START");
    error_log("File info: " . print_r($file, true));
    
    // Vérifications de base
    if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        error_log("✗ Fichier temporaire invalide");
        return [
            'success' => false,
            'message' => 'Fichier temporaire invalide'
        ];
    }
    
    // Vérifier l'extension
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
    
    error_log("Extension: " . $extension);
    
    if (!in_array($extension, $allowedExtensions)) {
        error_log("✗ Extension non autorisée: " . $extension);
        return [
            'success' => false,
            'message' => 'Type de fichier non autorisé. Utilisez JPG, PNG ou GIF.'
        ];
    }
    
    // Vérifier la taille (max 5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        error_log("✗ Fichier trop gros: " . $file['size'] . " bytes");
        return [
            'success' => false,
            'message' => 'Le fichier ne doit pas dépasser 5 MB'
        ];
    }
    
    error_log("✓ Taille OK: " . $file['size'] . " bytes");
    
    // FIXED: Get the project root directory properly
    // Go up from controllers/member/ to project root
    $projectRoot = realpath(__DIR__ . '/../../');
    error_log("Project root: " . $projectRoot);
    
    // Build path: project_root/uploads/photos/
    $uploadDir = $projectRoot . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'photos' . DIRECTORY_SEPARATOR;
    
    error_log("Upload directory: " . $uploadDir);
    
    // Créer le dossier si nécessaire
    if (!is_dir($uploadDir)) {
        error_log("Creating directory...");
        if (!mkdir($uploadDir, 0777, true)) {
            error_log("✗ Impossible de créer le dossier");
            return [
                'success' => false,
                'message' => 'Impossible de créer le dossier d\'upload'
            ];
        }
        chmod($uploadDir, 0777);
        error_log("✓ Directory created");
    }
    
    // Vérifier les permissions
    if (!is_writable($uploadDir)) {
        error_log("✗ Directory not writable");
        error_log("Permissions: " . substr(sprintf('%o', fileperms($uploadDir)), -4));
        
        // Try to fix permissions
        @chmod($uploadDir, 0777);
        
        if (!is_writable($uploadDir)) {
            return [
                'success' => false,
                'message' => 'Le dossier n\'est pas accessible en écriture'
            ];
        }
    }
    
    error_log("✓ Directory is writable");
    
    // Generate unique filename
    $filename = 'membre_' . $this->membreId . '_' . time() . '.' . $extension;
    $filepath = $uploadDir . $filename;
    
    error_log("Filename: " . $filename);
    error_log("Full path: " . $filepath);
    error_log("Temp file: " . $file['tmp_name']);
    
    // Move the file
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        error_log("✓✓✓ move_uploaded_file SUCCESS");
        
        // Verify file exists
        if (file_exists($filepath)) {
            // Set file permissions
            chmod($filepath, 0644);
            
            error_log("✓✓✓ File verified at: " . $filepath);
            error_log(">>> handlePhotoUpload SUCCESS");
            
            // Return JUST the filename (no path prefix)
            return [
                'success' => true,
                'filename' => $filename
            ];
        } else {
            error_log("✗✗✗ File doesn't exist after move_uploaded_file!");
            return [
                'success' => false,
                'message' => 'Le fichier n\'a pas pu être sauvegardé'
            ];
        }
    }
    
    $error = error_get_last();
    error_log("✗✗✗ move_uploaded_file FAILED");
    error_log("Last error: " . print_r($error, true));
    error_log(">>> handlePhotoUpload FAIL");
    
    return [
        'success' => false,
        'message' => 'Erreur lors du téléchargement du fichier'
    ];
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
}
?>