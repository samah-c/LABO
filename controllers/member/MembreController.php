<?php
/**
 * MembreController.php - Contrôleur pour l'espace membre
 * FIXED VERSION - Proper redirects
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
        
        $stats = [
            'mes_projets' => $this->projetModel->countByMembre($this->membreId),
            'mes_publications' => $this->publicationModel->countByMembre($this->membreId),
            'reservations_actives' => $this->equipementModel->countReservationsByMembre($this->membreId),
            'evenements_a_venir' => $this->evenementModel->countUpcoming()
        ];
        
        $mesProjets = $this->projetModel->getByMembre($this->membreId, 5);
        $mesPublications = $this->publicationModel->getByMembre($this->membreId, 5);
        $mesReservations = $this->equipementModel->getReservationsByMembre($this->membreId);
        $evenements = $this->evenementModel->getUpcoming(5);
        $membre = $this->membre;
        $username = $membre['prenom'] . ' ' . $membre['nom'];
        
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
        
        $filters = [
            'statut' => get('statut'),
            'search' => get('search')
        ];
        
        $projets = $this->projetModel->getByMembre($this->membreId);
        
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
        
        $page = (int) get('page', 1);
        $perPage = 10;
        $pagination = Utils::paginate(count($projets), $perPage, $page);
        $projets = array_slice($projets, $pagination['offset'], $perPage);

        $view = new ProjetsView($projets, $pagination);
        $view->render();
    }
    
    /**
     * Détail d'un projet
     */
    public function projetDetail($id) {
        $projet = $this->projetModel->getById($id);
        
        if (!$projet) {
            flash('error', 'Projet non trouvé');
            redirect(base_url('projets'));
            return;
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
        
        $view = new ProjetDetail($projet, $membres, $publications, $responsable, $stats);
        $view->render(); 
    }
    
    /**
     * Mes publications
     */
    public function publications() {
        AuthController::requireMembre();
        
        $filters = [
            'statut' => get('statut'),
            'type' => get('type'),
            'search' => get('search')
        ];
        
        $publications = $this->publicationModel->getByMembre($this->membreId);
        
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
        
        $page = (int) get('page', 1);
        $perPage = 10;
        $pagination = Utils::paginate(count($publications), $perPage, $page);
        $publications = array_slice($publications, $pagination['offset'], $perPage);

        $view = new PublicationsView($publications, $pagination);
        $view->render();
    }
    
    /**
     * Soumettre une nouvelle publication
     */
    public function soumettrePublication() {
        require_once __DIR__ . '/PublicationController.php';
        $controller = new PublicationController();
        return $controller->createPublication();
    }
    
    /**
     * Réservations d'équipements - FIXED VERSION WITH DATE CHECK
     */
    public function reservations() {
        AuthController::requireMembre();
        
        error_log("=== RESERVATIONS METHOD START ===");
        error_log("Membre ID: " . $this->membreId);
        
        // Mettre à jour automatiquement les réservations expirées
        $this->updateExpiredReservations();
        
        // Récupérer les réservations
        $reservations = $this->equipementModel->getReservationsByMembre($this->membreId);
        error_log("Total reservations: " . count($reservations));
        
        $now = new DateTime();
        
        // Séparer par statut ET par date
        $actives = array_filter($reservations, function($r) use ($now) {
            // Une réservation est active si :
            // 1. Statut = 'confirme' ou 'en_attente'
            // 2. Date de fin >= maintenant (pas encore terminée)
            if (!in_array($r['statut'], ['confirme', 'en_attente'])) {
                return false;
            }
            
            $dateFin = new DateTime($r['date_fin']);
            return $dateFin >= $now;
        });
        
        $historique = array_filter($reservations, function($r) use ($now) {
        
            if (in_array($r['statut'], ['terminee', 'annule'])) {
                return true;
            }
            
            // Vérifier si la réservation est passée
            $dateFin = new DateTime($r['date_fin']);
            return $dateFin < $now;
        });
        
        error_log("Active reservations: " . count($actives));
        error_log("Historic reservations: " . count($historique));
        
        // Debug : afficher les réservations
        foreach ($reservations as $r) {
            error_log("Reservation ID {$r['id']}: statut={$r['statut']}, date_fin={$r['date_fin']}");
        }
        
        // Récupérer les équipements disponibles
        $db = Database::getInstance()->getConnection();
        $stmt = $db->query("
            SELECT * FROM Equipement 
            WHERE etat != 'en_maintenance'
            ORDER BY nom
        ");
        $equipements = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        error_log("Available equipements: " . count($equipements));
        
        // Créer la vue
        $view = new ReservationsListView($actives, $historique, $equipements);
        $view->render();
        
        error_log("=== RESERVATIONS METHOD END ===");
    }
    
    /**
     * Mettre à jour automatiquement les réservations expirées
     */
    private function updateExpiredReservations() {
        try {
            $db = Database::getInstance()->getConnection();
            
            // Mettre à jour les réservations dont la date de fin est passée
            $stmt = $db->prepare("
                UPDATE Creneau 
                SET statut = 'terminee' 
                WHERE statut IN ('confirme', 'en_attente')
                AND date_fin < NOW()
                AND membre_id = ?
            ");
            
            $result = $stmt->execute([$this->membreId]);
            
            if ($result) {
                $count = $stmt->rowCount();
                if ($count > 0) {
                    error_log("Updated $count expired reservations to 'terminee'");
                }
            }
        } catch (Exception $e) {
            error_log("Error updating expired reservations: " . $e->getMessage());
        }
    }

    /**
     * Créer une réservation - FIXED VERSION WITH PROPER REDIRECT
     */
    public function creerReservation() {
        AuthController::requireMembre();
        
        error_log("=== CREATE RESERVATION START ===");
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            error_log("ERROR: Not a POST request");
            redirect(base_url('membre/reservations'));
            return;
        }
        
        $data = [
            'equipement_id' => post('equipement_id'),
            'membre_id' => $this->membreId,
            'date_debut' => post('date_debut'),
            'date_fin' => post('date_fin'),
            'motif' => post('motif'),
            'statut' => 'en_attente'
        ];
        
        error_log("Reservation data: " . print_r($data, true));
        
        // Validation
        if (empty($data['equipement_id']) || empty($data['date_debut']) || empty($data['date_fin'])) {
            error_log("ERROR: Missing required fields");
            flash('error', 'Veuillez remplir tous les champs obligatoires');
            redirect(base_url('membre/reservations'));
            return;
        }
        
        // Vérifier que date_fin > date_debut
        if (strtotime($data['date_fin']) <= strtotime($data['date_debut'])) {
            error_log("ERROR: End date must be after start date");
            flash('error', 'La date de fin doit être postérieure à la date de début');
            redirect(base_url('membre/reservations'));
            return;
        }
        
        // Vérifier la disponibilité
        if (!$this->equipementModel->isAvailable($data['equipement_id'], $data['date_debut'], $data['date_fin'])) {
            error_log("ERROR: Equipment not available for this period");
            flash('error', 'Équipement non disponible pour cette période');
            redirect(base_url('membre/reservations'));
            return;
        }
        
        // Créer la réservation
        try {
            if ($this->equipementModel->createReservation($data)) {
                error_log("SUCCESS: Reservation created");
                flash('success', 'Réservation créée avec succès');
            } else {
                error_log("ERROR: Failed to create reservation");
                flash('error', 'Erreur lors de la création de la réservation');
            }
        } catch (Exception $e) {
            error_log("EXCEPTION: " . $e->getMessage());
            flash('error', 'Erreur lors de la création: ' . $e->getMessage());
        }
        
        error_log("=== CREATE RESERVATION END ===");
        redirect(base_url('membre/reservations'));
    }
    
    /**
     * Annuler une réservation - FIXED VERSION
     */
    public function annulerReservation($id) {
        AuthController::requireMembre();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(base_url('membre/reservations'));
            return;
        }
        
        // Vérifier que la réservation appartient au membre
        $reservation = $this->equipementModel->getReservationById($id);
        
        if (!$reservation || $reservation['membre_id'] != $this->membreId) {
            flash('error', 'Réservation introuvable');
            redirect(base_url('membre/reservations'));
            return;
        }
        
        // Annuler la réservation
        if ($this->equipementModel->updateReservation($id, ['statut' => 'annule'])) {
            flash('success', 'Réservation annulée');
        } else {
            flash('error', 'Erreur lors de l\'annulation');
        }
        
        redirect(base_url('membre/reservations'));
    }
    
    /**
     * Événements
     */
    public function evenements() {
        AuthController::requireMembre();
        
        $evenements = $this->evenementModel->getUpcoming();
        
        $type = get('type');
        if (!empty($type)) {
            $evenements = array_filter($evenements, function($e) use ($type) {
                return $e['type_evenement'] === $type;
            });
        }
        
        $view = new EvenementsListView($evenements);
        $view->render();
    }

    /**
     * Profil
     */
    public function profil() {
        AuthController::requireMembre();
        
        $membre = $this->membreModel->getWithDetails($this->membreId);
        $user = $this->membreModel->getUserByMembreId($this->membreId);
        
        if (!$membre) {
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
        
        $stats = [
            'total_projets' => $this->projetModel->countByMembre($this->membreId),
            'total_publications' => $this->publicationModel->countByMembre($this->membreId),
            'projets_en_cours' => $this->projetModel->countByMembreAndStatus($this->membreId, 'en_cours'),
            'publications_validees' => $this->publicationModel->countByMembreAndStatus($this->membreId, 'valide')
        ];
        
        $view = new ProfilView($user, $membre, $stats);
        $view->render();
    }

    /**
     * Mettre à jour le profil - FIXED VERSION
     */
    public function updateProfil() {
        AuthController::requireMembre();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(base_url('membre/profil'));
            return;
        }
        
        error_log("=== UPDATE PROFIL START ===");
        
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
            
            // Validation
            if (empty($data['nom']) || empty($data['prenom'])) {
                flash('error', 'Le nom et le prénom sont obligatoires');
                redirect(base_url('membre/profil'));
                return;
            }
            
            // Gestion de la photo
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                $uploadResult = $this->handlePhotoUpload($_FILES['photo']);
                
                if ($uploadResult['success']) {
                    $data['photo'] = $uploadResult['filename'];
                    
                    // Supprimer l'ancienne photo
                    $oldMembre = $this->membreModel->getById($this->membreId);
                    if (!empty($oldMembre['photo'])) {
                        $oldPhotoPath = realpath(__DIR__ . '/../../') . '/uploads/photos/' . $oldMembre['photo'];
                        if (file_exists($oldPhotoPath)) {
                            unlink($oldPhotoPath);
                        }
                    }
                } else {
                    flash('error', $uploadResult['message']);
                    redirect(base_url('membre/profil'));
                    return;
                }
            }
            
            // Mettre à jour l'email
            $email = trim(post('email', ''));
            if (!empty($email)) {
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    flash('error', 'Adresse email invalide');
                    redirect(base_url('membre/profil'));
                    return;
                }
                
                $userId = session('user_id');
                $db = Database::getInstance()->getConnection();
                $stmt = $db->prepare("UPDATE User SET email = ? WHERE id = ?");
                if ($stmt->execute([$email, $userId])) {
                    $_SESSION['email'] = $email;
                }
            }
            
            // Mise à jour du profil
            if ($this->membreModel->updateProfil($this->membreId, $data)) {
                flash('success', 'Profil mis à jour avec succès');
            } else {
                flash('error', 'Erreur lors de la mise à jour du profil');
            }
            
        } catch (Exception $e) {
            error_log("Exception: " . $e->getMessage());
            flash('error', 'Une erreur est survenue: ' . $e->getMessage());
        }
        
        redirect(base_url('membre/profil'));
    }

    /**
     * Gérer l'upload de photo
     */
    private function handlePhotoUpload($file) {
        if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return ['success' => false, 'message' => 'Fichier temporaire invalide'];
        }
        
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (!in_array($extension, $allowedExtensions)) {
            return ['success' => false, 'message' => 'Type de fichier non autorisé'];
        }
        
        if ($file['size'] > 5 * 1024 * 1024) {
            return ['success' => false, 'message' => 'Le fichier ne doit pas dépasser 5 MB'];
        }
        
        $projectRoot = realpath(__DIR__ . '/../../');
        $uploadDir = $projectRoot . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'photos' . DIRECTORY_SEPARATOR;
        
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0777, true)) {
                return ['success' => false, 'message' => 'Impossible de créer le dossier d\'upload'];
            }
            chmod($uploadDir, 0777);
        }
        
        if (!is_writable($uploadDir)) {
            @chmod($uploadDir, 0777);
            if (!is_writable($uploadDir)) {
                return ['success' => false, 'message' => 'Le dossier n\'est pas accessible en écriture'];
            }
        }
        
        $filename = 'membre_' . $this->membreId . '_' . time() . '.' . $extension;
        $filepath = $uploadDir . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            if (file_exists($filepath)) {
                chmod($filepath, 0644);
                return ['success' => true, 'filename' => $filename];
            }
        }
        
        return ['success' => false, 'message' => 'Erreur lors du téléchargement du fichier'];
    }

    /**
     * Changer le mot de passe - FIXED VERSION
     */
    public function changePassword() {
        AuthController::requireMembre();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(base_url('membre/profil'));
            return;
        }
        
        try {
            $currentPassword = post('current_password', '');
            $newPassword = post('new_password', '');
            $confirmPassword = post('confirm_password', '');
            
            // Validation
            if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
                flash('error', 'Tous les champs sont obligatoires');
                redirect(base_url('membre/profil'));
                return;
            }
            
            if (strlen($newPassword) < 6) {
                flash('error', 'Le mot de passe doit contenir au moins 6 caractères');
                redirect(base_url('membre/profil'));
                return;
            }
            
            if ($newPassword !== $confirmPassword) {
                flash('error', 'Les mots de passe ne correspondent pas');
                redirect(base_url('membre/profil'));
                return;
            }
            
            // Vérifier le mot de passe actuel
            $userId = session('user_id');
            $db = Database::getInstance()->getConnection();
            
            $stmt = $db->prepare("SELECT password FROM User WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            
            if (!$user || !password_verify($currentPassword, $user['password'])) {
                flash('error', 'Mot de passe actuel incorrect');
                redirect(base_url('membre/profil'));
                return;
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
        
        redirect(base_url('membre/profil'));
    }

    /**
 * Formulaire d'édition de projet (AJAX) - Uniquement pour le responsable
 */
public function projetForm($id) {
    AuthController::requireMembre();
    
    // Récupérer le projet
    $projet = $this->projetModel->getById($id);
    
    if (!$projet) {
        http_response_code(404);
        echo '<p style="color: red; text-align: center; padding: 40px;">Projet non trouvé</p>';
        return;
    }
    
    // Vérifier que le membre connecté est bien le responsable
    if ($projet['responsable_id'] != $this->membreId) {
        http_response_code(403);
        echo '<p style="color: red; text-align: center; padding: 40px;">Vous n\'êtes pas autorisé à modifier ce projet</p>';
        return;
    }
    
    // Récupérer tous les membres pour le select (si besoin de changer le responsable)
    $membres = $this->membreModel->getAllMembresWithUser();
    
    // Générer le formulaire
    ?>
    <form id="projet-form" method="POST" action="<?= base_url('membre/projets/save') ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= $projet['id'] ?>">
        
        <div class="form-group">
            <label for="titre">Titre du projet *</label>
            <input type="text" 
                   name="titre" 
                   id="titre" 
                   value="<?= e($projet['titre']) ?>" 
                   required 
                   placeholder="Ex: Système de détection d'intrusion par IA">
        </div>
        
        <div class="form-group">
            <label for="description">Description *</label>
            <textarea name="description" 
                      id="description" 
                      rows="4" 
                      required 
                      placeholder="Description détaillée du projet"><?= e($projet['description']) ?></textarea>
        </div>
        
        <div class="form-row">
                            <div class="form-group">
                    <label for="thematique">Thématique *</label>
                    <select name="thematique" id="thematique" required>
                        <option value="">-- Sélectionner --</option>
                        <option value="IA" <?= ($projet['thematique'] ?? '') === 'IA' ? 'selected' : '' ?>>Intelligence Artificielle</option>
                        <option value="Securite" <?= ($projet['thematique'] ?? '') === 'Securite' ? 'selected' : '' ?>>Sécurité Informatique</option>
                        <option value="Cloud" <?= ($projet['thematique'] ?? '') === 'Cloud' ? 'selected' : '' ?>>Cloud Computing</option>
                        <option value="Reseaux" <?= ($projet['thematique'] ?? '') === 'Reseaux' ? 'selected' : '' ?>>Réseaux</option>
                        <option value="Systemes_embarques" <?= ($projet['thematique'] ?? '') === 'Systemes_embarques' ? 'selected' : '' ?>>Systèmes Embarqués</option>
                        <option value="Autre" <?= ($projet['thematique'] ?? '') === 'Autre' ? 'selected' : '' ?>>Autre</option>
                    </select>
                </div>
            
            <div class="form-group">
                <label for="status">Statut *</label>
                <select name="status" id="status" required>
                   <option value="en_cours" <?= $projet['status'] === 'en_cours' ? 'selected' : '' ?>>En cours</option>
                   <option value="termine" <?= $projet['status'] === 'termine' ? 'selected' : '' ?>>Terminé</option>
                   <option value="soumis" <?= $projet['status'] === 'soumis' ? 'selected' : '' ?>>Soumis</option>
                   <option value="approuvé" <?= $projet['status'] === 'approuvé' ? 'selected' : '' ?>>Approuvé</option>
                   <option value="rejeté" <?= $projet['status'] === 'rejeté' ? 'selected' : '' ?>>Rejeté</option>
                </select>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="date_debut">Date de début *</label>
                <input type="date" 
                       name="date_debut" 
                       id="date_debut" 
                       value="<?= $projet['date_debut'] ?>"
                       required>
            </div>
            
            <div class="form-group">
                <label for="date_fin">Date de fin prévue</label>
                <input type="date" 
                       name="date_fin" 
                       id="date_fin" 
                       value="<?= $projet['date_fin'] ?? '' ?>">
            </div>
        </div>
    
        
        <div class="modal-footer">
            <button type="button" class="btn-secondary" onclick="projets.closeModal()">
                Annuler
            </button>
            <button type="submit" class="btn-primary">
                Mettre à jour
            </button>
        </div>
    </form>
    
    <style>
    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
    }
    
    @media (max-width: 768px) {
        .form-row {
            grid-template-columns: 1fr;
        }
    }
    </style>
    <?php
}

/**
 * Sauvegarder un projet (mise à jour uniquement) - Pour responsable
 */
public function projetSave() {
    AuthController::requireMembre();
    
    // Vérifier si c'est une requête AJAX
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
              strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    
    // Vérifier le token CSRF
    if (!isset($_POST['csrf_token']) || !Utils::verifyCsrfToken($_POST['csrf_token'])) {
        if ($isAjax) {
            json(['success' => false, 'message' => 'Token CSRF invalide']);
        } else {
            flash('error', 'Token CSRF invalide');
            redirect(base_url('membre/projets'));
        }
        return;
    }
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        redirect(base_url('membre/projets'));
        return;
    }
    
    try {
        $projetId = (int)$_POST['id'];
        
        if (!$projetId) {
            if ($isAjax) {
                json(['success' => false, 'message' => 'ID projet manquant']);
            } else {
                flash('error', 'ID projet manquant');
                redirect(base_url('membre/projets'));
            }
            return;
        }
        
        // Récupérer le projet
        $projet = $this->projetModel->getById($projetId);
        
        if (!$projet) {
            if ($isAjax) {
                json(['success' => false, 'message' => 'Projet non trouvé']);
            } else {
                flash('error', 'Projet non trouvé');
                redirect(base_url('membre/projets'));
            }
            return;
        }
        
        // Vérifier que le membre est le responsable
        if ($projet['responsable_id'] != $this->membreId) {
            if ($isAjax) {
                json(['success' => false, 'message' => 'Vous n\'êtes pas autorisé à modifier ce projet']);
            } else {
                flash('error', 'Vous n\'êtes pas autorisé à modifier ce projet');
                redirect(base_url('membre/projets'));
            }
            return;
        }
        
        $data = [
            'titre' => Utils::sanitize($_POST['titre']),
            'description' => Utils::sanitize($_POST['description']),
            'thematique' => Utils::sanitize($_POST['thematique']),
            'status' => Utils::sanitize($_POST['status']),
            'date_debut' => $_POST['date_debut'],
            'date_fin' => $_POST['date_fin'] ?? null,
        ];
        
        // Validation
        $errors = [];
        
        if (empty($data['titre'])) {
            $errors['titre'] = 'Le titre est requis';
        }
        
        if (empty($data['description'])) {
            $errors['description'] = 'La description est requise';
        }
        
        if (empty($data['thematique'])) {
            $errors['thematique'] = 'La thématique est requise';
        }
        
        if (empty($data['status'])) {
            $errors['status'] = 'Le statut est requis';
        }
        
        if (empty($data['date_debut'])) {
            $errors['date_debut'] = 'La date de début est requise';
        }
        
        // Vérifier que la date de fin est après la date de début
        if (!empty($data['date_fin']) && !empty($data['date_debut'])) {
            if (strtotime($data['date_fin']) < strtotime($data['date_debut'])) {
                $errors['date_fin'] = 'La date de fin doit être après la date de début';
            }
        }
        
        if (!empty($errors)) {
            if ($isAjax) {
                json(['success' => false, 'message' => 'Erreurs de validation', 'errors' => $errors]);
            } else {
                flash('error', 'Erreurs de validation');
                redirect(base_url('membre/projets'));
            }
            return;
        }
        
        // Mise à jour
        $success = $this->projetModel->update($projetId, $data);
        
        if ($success) {
            Utils::log("Projet #$projetId mis à jour par le responsable " . session('username'));
            
            if ($isAjax) {
                json(['success' => true, 'message' => 'Projet mis à jour avec succès', 'id' => $projetId]);
            } else {
                flash('success', 'Projet mis à jour avec succès');
                redirect(base_url('membre/projets/' . $projetId));
            }
        } else {
            if ($isAjax) {
                json(['success' => false, 'message' => 'Erreur lors de la mise à jour']);
            } else {
                flash('error', 'Erreur lors de la mise à jour');
                redirect(base_url('membre/projets'));
            }
        }
        
    } catch (Exception $e) {
        Utils::log("Erreur sauvegarde projet membre: " . $e->getMessage(), 'ERROR');
        
        if ($isAjax) {
            json(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()]);
        } else {
            flash('error', 'Erreur serveur: ' . $e->getMessage());
            redirect(base_url('membre/projets'));
        }
    }
}
}
?>