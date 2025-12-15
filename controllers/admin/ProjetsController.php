<?php
/**
 * ProjetsController.php - Contrôleur complet pour la gestion des projets
 * Gère toutes les opérations CRUD + gestion des membres et partenaires
 */

require_once __DIR__ . '/../../models/ProjetModel.php';
require_once __DIR__ . '/../../models/MembreModel.php';
require_once __DIR__ . '/../../models/PublicationModel.php';
require_once __DIR__ . '/../../lib/helpers.php';
require_once __DIR__ . '/../auth/AuthController.php';

class ProjetsController {
    private $projetModel;
    private $membreModel;
    private $publicationModel;
    
    public function __construct() {
        AuthController::requireAdmin();
        $this->projetModel = new ProjetModel();
        $this->membreModel = new MembreModel();
        $this->publicationModel = new PublicationModel();
    }
    
    /**
     * Liste des projets avec filtres et recherche
     */
    public function index() {
        // Récupérer les filtres
        $filters = [
            'thematique' => get('thematique'),
            'status' => get('status'),
            'search' => get('search')
        ];
        
        // Récupérer les projets filtrés
        $projets = $this->projetModel->getAllFiltered($filters);
        
        // Pagination
        $page = (int) get('page', 1);
        $perPage = 10;
        $pagination = Utils::paginate(count($projets), $perPage, $page);
        $projets = array_slice($projets, $pagination['offset'], $perPage);
        
        // Charger la vue
        require_once __DIR__ . '/../../views/admin/projets/projets.php';
    }
    
    /**
     * Vue détaillée d'un projet
     */
    public function view($id) {
        // Récupérer le projet
        $projet = $this->projetModel->getById($id);
        
        if (!$projet) {
            $_SESSION['error'] = 'Projet non trouvé';
            redirect(base_url('admin/projets/projets'));
        }
        
        // Récupérer les membres du projet
        $membres = $this->projetModel->getMembres($id);
        
        // Récupérer les publications du projet
        $publications = $this->projetModel->getPublications($id);
        
        // Récupérer le responsable
       // Récupérer le responsable
       $responsable = null;
       if (!empty($projet['responsable_id'])) {
       $responsable = $this->membreModel->getWithDetails($projet['responsable_id']);
          }
       // Sinon utiliser les données déjà dans $projet
       if (!$responsable && !empty($projet['responsable_username'])) {
       $responsable = [
        'id' => $projet['responsable_id'],
        'username' => $projet['responsable_username']
                     ];
       }
        
        // Statistiques du projet
        $stats = [
            'nb_membres' => count($membres),
            'nb_publications' => count($publications),
            'progression' => LabHelpers::calculateProjectProgress(
                $projet['date_debut'], 
                $projet['date_fin']
            )
        ];
        
        // Charger la vue détaillée
        require_once __DIR__ . '/../../views/admin/projets/view.php';
    }
    
    /**
     * Formulaire d'ajout/édition de projet (AJAX)
     */
    public function form($id = null) {
        // Récupérer le projet si édition
        $projet = $id ? $this->projetModel->getById($id) : null;
        
        // Récupérer tous les membres pour le select du responsable
        $membres = $this->membreModel->getAllMembresWithUser();
        
        // Générer le formulaire
        ?>
        <form id="projet-form" method="POST" action="<?= base_url('admin/projets/projets/save') ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= $projet['id'] ?? '' ?>">
            
            <div class="form-group">
                <label for="titre">Titre du projet *</label>
                <input type="text" 
                       name="titre" 
                       id="titre" 
                       value="<?= e($projet['titre'] ?? '') ?>" 
                       required 
                       placeholder="Ex: Système de détection d'intrusion par IA">
            </div>
            
            <div class="form-group">
                <label for="descriptif">Description *</label>
                <textarea name="descriptif" 
                          id="descriptif" 
                          rows="4" 
                          required 
                          placeholder="Description détaillée du projet"><?= e($projet['descriptif'] ?? '') ?></textarea>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="thematique">Thématique *</label>
                    <select name="thematique" id="thematique" required>
                        <option value="">-- Sélectionner --</option>
                        <option value="Intelligence Artificielle" <?= ($projet['thematique'] ?? '') === 'Intelligence Artificielle' ? 'selected' : '' ?>>Intelligence Artificielle</option>
                        <option value="Sécurité Informatique" <?= ($projet['thematique'] ?? '') === 'Sécurité Informatique' ? 'selected' : '' ?>>Sécurité Informatique</option>
                        <option value="Cloud Computing" <?= ($projet['thematique'] ?? '') === 'Cloud Computing' ? 'selected' : '' ?>>Cloud Computing</option>
                        <option value="Réseaux" <?= ($projet['thematique'] ?? '') === 'Réseaux' ? 'selected' : '' ?>>Réseaux</option>
                        <option value="Systèmes Embarqués" <?= ($projet['thematique'] ?? '') === 'Systèmes Embarqués' ? 'selected' : '' ?>>Systèmes Embarqués</option>
                        <option value="Big Data" <?= ($projet['thematique'] ?? '') === 'Big Data' ? 'selected' : '' ?>>Big Data</option>
                        <option value="IoT" <?= ($projet['thematique'] ?? '') === 'IoT' ? 'selected' : '' ?>>Internet des Objets</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="status">status *</label>
                    <select name="status" id="status" required>
                       <option value="en_cours" <?= ($projet['status'] ?? '') === 'en_cours' ? 'selected' : '' ?>>En cours</option>
                       <option value="termine" <?= ($projet['status'] ?? '') === 'termine' ? 'selected' : '' ?>>Terminé</option>
                       <option value="soumis" <?= ($projet['status'] ?? '') === 'soumis' ? 'selected' : '' ?>>Soumis</option>
                       <option value="approuvé" <?= ($projet['status'] ?? '') === 'approuvé' ? 'selected' : '' ?>>Approuvé</option>
                       <option value="rejeté" <?= ($projet['status'] ?? '') === 'rejeté' ? 'selected' : '' ?>>Rejeté</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label for="responsable_id">Responsable scientifique *</label>
                <select name="responsable_id" id="responsable_id" required>
                    <option value="">-- Sélectionner un responsable --</option>
                    <?php foreach ($membres as $membre): ?>
                        <option value="<?= $membre['id'] ?>" 
                                <?= ($projet['responsable_id'] ?? '') == $membre['id'] ? 'selected' : '' ?>>
                            <?= e($membre['username']) ?> 
                            <?php if (!empty($membre['grade'])): ?>
                                - <?= e($membre['grade']) ?>
                            <?php endif; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="date_debut">Date de début *</label>
                    <input type="date" 
                           name="date_debut" 
                           id="date_debut" 
                           value="<?= $projet['date_debut'] ?? '' ?>"
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
            
            <div class="form-row">
                <div class="form-group">
                    <label for="budget">Budget (DZD)</label>
                    <input type="number" 
                           name="budget" 
                           id="budget" 
                           value="<?= $projet['budget'] ?? '' ?>"
                           step="0.01"
                           placeholder="Ex: 500000.00">
                </div>
                
                <div class="form-group">
                    <label for="source_financement">Source de financement</label>
                    <input type="text" 
                           name="source_financement" 
                           id="source_financement" 
                           value="<?= e($projet['source_financement'] ?? '') ?>"
                           placeholder="Ex: MESRS, DG-RSDT">
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="closeModal()">
                    Annuler
                </button>
                <button type="submit" class="btn-primary">
                    <?= $id ? 'Mettre à jour' : 'Créer le projet' ?>
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
     * Sauvegarder un projet (création ou mise à jour)
     */
    public function save() {
        // Vérifier si c'est une requête AJAX
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                  strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        
        // Vérifier le token CSRF
        if (!isset($_POST['csrf_token']) || !Utils::verifyCsrfToken($_POST['csrf_token'])) {
            if ($isAjax) {
                json(['success' => false, 'message' => 'Token CSRF invalide']);
            } else {
                $_SESSION['error'] = 'Token CSRF invalide';
                redirect(base_url('admin/projets/projets'));
            }
        }
        
        try {
            $data = [
                'titre' => Utils::sanitize($_POST['titre']),
                'descriptif' => Utils::sanitize($_POST['descriptif']),
                'thematique' => Utils::sanitize($_POST['thematique']),
                'status' => Utils::sanitize($_POST['status']),
                'responsable_id' => (int)$_POST['responsable_id'],
                'date_debut' => $_POST['date_debut'],
                'date_fin' => $_POST['date_fin'] ?? null,
                'budget' => !empty($_POST['budget']) ? floatval($_POST['budget']) : null,
                'source_financement' => Utils::sanitize($_POST['source_financement'] ?? '')
            ];
            
            // Validation
            $errors = [];
            
            if (empty($data['titre'])) {
                $errors['titre'] = 'Le titre est requis';
            }
            
            if (empty($data['descriptif'])) {
                $errors['descriptif'] = 'La description est requise';
            }
            
            if (empty($data['thematique'])) {
                $errors['thematique'] = 'La thématique est requise';
            }
            
            if (empty($data['status'])) {
                $errors['status'] = 'Le status est requis';
            }
            
            if (empty($data['responsable_id'])) {
                $errors['responsable_id'] = 'Le responsable est requis';
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
                    $_SESSION['validation_errors'] = $errors;
                    $_SESSION['old_input'] = $_POST;
                    redirect(base_url('admin/projets/projets'));
                }
            }
            
            // Créer ou mettre à jour
            if (!empty($_POST['id'])) {
                // Mise à jour
                $id = (int)$_POST['id'];
                $success = $this->projetModel->update($id, $data);
                $message = 'Projet mis à jour avec succès';
            } else {
                // Création
                $id = $this->projetModel->create($data);
                $success = $id > 0;
                $message = 'Projet créé avec succès';
            }
            
            if ($success) {
                Utils::log("Projet sauvegardé par " . session('username'));
                
                if ($isAjax) {
                    json(['success' => true, 'message' => $message, 'id' => $id]);
                } else {
                    $_SESSION['success'] = $message;
                    redirect(base_url('admin/projets/projets'));
                }
            } else {
                if ($isAjax) {
                    json(['success' => false, 'message' => 'Erreur lors de l\'enregistrement']);
                } else {
                    $_SESSION['error'] = 'Erreur lors de l\'enregistrement';
                    redirect(base_url('admin/projets/projets'));
                }
            }
            
        } catch (Exception $e) {
            Utils::log("Erreur sauvegarde projet: " . $e->getMessage(), 'ERROR');
            
            if ($isAjax) {
                json(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()]);
            } else {
                $_SESSION['error'] = 'Erreur serveur: ' . $e->getMessage();
                redirect(base_url('admin/projets/projets'));
            }
        }
    }
    
    /**
     * Supprimer un projet
     */
    public function delete($id) {
        try {
            // Vérifier si le projet a des publications
            $publications = $this->projetModel->getPublications($id);
            
            if (count($publications) > 0) {
                json([
                    'success' => false, 
                    'message' => 'Impossible de supprimer un projet avec des publications. Veuillez d\'abord réassigner les publications.'
                ]);
            }
            
            // Supprimer le projet
            $success = $this->projetModel->delete($id);
            
            if ($success) {
                Utils::log("Projet #$id supprimé par " . session('username'));
                json(['success' => true, 'message' => 'Projet supprimé avec succès']);
            } else {
                json(['success' => false, 'message' => 'Erreur lors de la suppression']);
            }
            
        } catch (Exception $e) {
            Utils::log("Erreur suppression projet: " . $e->getMessage(), 'ERROR');
            json(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Ajouter un membre à un projet
     */
    public function addMembre() {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            $projetId = (int)($input['projet_id'] ?? 0);
            $membreId = (int)($input['membre_id'] ?? 0);
            $role = Utils::sanitize($input['role'] ?? 'Participant');
            
            if (!$projetId || !$membreId) {
                json(['success' => false, 'message' => 'Données invalides']);
            }
            
            // Vérifier que le projet existe
            $projet = $this->projetModel->getById($projetId);
            if (!$projet) {
                json(['success' => false, 'message' => 'Projet non trouvé']);
            }
            
            // Vérifier que le membre existe
            $membre = $this->membreModel->getById($membreId);
            if (!$membre) {
                json(['success' => false, 'message' => 'Membre non trouvé']);
            }
            
            // Vérifier que le membre ne participe pas déjà
            if ($this->projetModel->membreParticipe($projetId, $membreId)) {
                json(['success' => false, 'message' => 'Ce membre participe déjà au projet']);
            }
            
            // Ajouter le membre
            $success = $this->projetModel->addMembre($projetId, $membreId, $role);
            
            if ($success) {
                Utils::log("Membre #$membreId ajouté au projet #$projetId par " . session('username'));
                json(['success' => true, 'message' => 'Membre ajouté au projet avec succès']);
            } else {
                json(['success' => false, 'message' => 'Erreur lors de l\'ajout']);
            }
            
        } catch (Exception $e) {
            Utils::log("Erreur ajout membre au projet: " . $e->getMessage(), 'ERROR');
            json(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Retirer un membre d'un projet
     */
    public function removeMembre() {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            $projetId = (int)($input['projet_id'] ?? 0);
            $membreId = (int)($input['membre_id'] ?? 0);
            
            if (!$projetId || !$membreId) {
                json(['success' => false, 'message' => 'Données invalides']);
            }
            
            // Retirer le membre du projet
            $success = $this->projetModel->removeMembre($projetId, $membreId);
            
            if ($success) {
                Utils::log("Membre #$membreId retiré du projet #$projetId par " . session('username'));
                json(['success' => true, 'message' => 'Membre retiré du projet avec succès']);
            } else {
                json(['success' => false, 'message' => 'Erreur lors du retrait']);
            }
            
        } catch (Exception $e) {
            Utils::log("Erreur retrait membre du projet: " . $e->getMessage(), 'ERROR');
            json(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Générer un rapport PDF pour un projet
     */
    public function genererRapport($id) {
        $projet = $this->projetModel->getById($id);
        
        if (!$projet) {
            $_SESSION['error'] = 'Projet non trouvé';
            redirect(base_url('admin/projets/projets'));
        }
        
        // Récupérer toutes les données nécessaires
        $membres = $this->projetModel->getMembres($id);
        $publications = $this->projetModel->getPublications($id);
        $responsable = $this->membreModel->getById($projet['responsable_id']);
        
        // Générer le rapport PDF (à implémenter avec une bibliothèque PDF)
        // Pour l'instant, on redirige vers une vue HTML imprimable
        require_once __DIR__ . '/../../views/admin/projets/rapport.php';
    }
    
    /**
     * Exporter les projets en CSV
     */
    public function export() {
        $projets = $this->projetModel->getAllWithResponsables();
        
        $data = [];
        $data[] = ['Titre', 'Thématique', 'Responsable', 'status', 'Date début', 'Date fin', 'Budget', 'Nb membres'];
        
        foreach ($projets as $projet) {
            $data[] = [
                $projet['titre'],
                $projet['thematique'],
                $projet['responsable_nom'] ?? 'Non assigné',
                $projet['status'],
                format_date($projet['date_debut']),
                format_date($projet['date_fin'] ?? ''),
                $projet['budget'] ?? '0',
                $projet['nb_membres'] ?? 0
            ];
        }
        
        LabHelpers::exportToCsv($data, 'projets_' . date('Y-m-d') . '.csv');
    }
    
    /**
     * API - Récupérer un projet par ID
     */
    public function get($id) {
        $projet = $this->projetModel->getById($id);
        
        if ($projet) {
            json(['success' => true, 'projet' => $projet]);
        } else {
            json(['success' => false, 'message' => 'Projet non trouvé']);
        }
    }
}
?>