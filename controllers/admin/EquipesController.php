<?php
/**
 * EquipesController.php - Contrôleur complet pour la gestion des équipes
 * Gère toutes les opérations CRUD + gestion des membres
 */

require_once __DIR__ . '/../../models/EquipeModel.php';
require_once __DIR__ . '/../../models/MembreModel.php';
require_once __DIR__ . '/../../models/PublicationModel.php';
require_once __DIR__ . '/../../lib/helpers.php';
require_once __DIR__ . '/../auth/AuthController.php';

require_once __DIR__ . '/../../views/admin/equipes/EquipesListView.php';
require_once __DIR__ . '/../../views/admin/equipes/EquipeDetailView.php';

class EquipesController {
    private $equipeModel;
    private $membreModel;
    private $publicationModel;
    
    public function __construct() {
        AuthController::requireAdmin();
        $this->equipeModel = new EquipeModel();
        $this->membreModel = new MembreModel();
        $this->publicationModel = new PublicationModel();
    }
    
    /**
     * Liste des équipes avec filtres et recherche
     */
    public function index() {
        // Récupérer les filtres
        $filters = [
            'domaine' => get('domaine'),
            'search' => get('search')
        ];
        
        // Récupérer les équipes filtrées
        $equipes = $this->equipeModel->getAllFiltered($filters);
        
        // Pagination
        $page = (int) get('page', 1);
        $perPage = 10;
        $pagination = Utils::paginate(count($equipes), $perPage, $page);
        $equipes = array_slice($equipes, $pagination['offset'], $perPage);
        
      
       // Vue liste des équipes
       $view = new EquipesListView($equipes, $pagination);
       $view->render();
    }
    
    /**
     * Vue détaillée d'une équipe
     */
    public function view($id) {
        // Récupérer l'équipe complète
        $equipe = $this->equipeModel->getEquipeComplete($id);
        
        if (!$equipe) {
            $_SESSION['error'] = 'Équipe non trouvée';
            redirect(base_url('admin/equipes/equipes'));
        }
        
        // Récupérer les publications de l'équipe
        $publications = $this->publicationModel->getByEquipe($id);
        
        // Récupérer les ressources/équipements alloués
        require_once __DIR__ . '/../../models/EquipementModel.php';
        $equipementModel = new EquipementModel();
        $ressources = $equipementModel->getByEquipe($id);
        
         // Vue détaillée d'une équipe
       $view = new EquipeDetailView($equipe, $publications, $ressources);
       $view->render();
    }
    
    /**
     * Formulaire d'ajout/édition d'équipe (AJAX)
     */
    public function form($id = null) {
        // Récupérer l'équipe si édition
        $equipe = $id ? $this->equipeModel->getById($id) : null;
        
        // Récupérer tous les membres pour le select du chef
        $membres = $this->membreModel->getAllMembresWithUser();
        
        // Générer le formulaire
        ?>
        <form id="equipe-form" method="POST" action="<?= base_url('admin/equipes/equipes/save') ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= $equipe['id'] ?? '' ?>">
            
            <div class="form-group">
                <label for="nom">Nom de l'équipe *</label>
                <input type="text" 
                       name="nom" 
                       id="nom" 
                       value="<?= e($equipe['nom'] ?? '') ?>" 
                       required 
                       placeholder="Ex: Équipe Intelligence Artificielle">
            </div>
            
            <div class="form-group">
                <label for="description">Description *</label>
                <textarea name="description" 
                          id="description" 
                          rows="4" 
                          required 
                          placeholder="Description de l'équipe et de ses objectifs"><?= e($equipe['description'] ?? '') ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="domaine">Domaine de recherche *</label>
                <select name="domaine" id="domaine" required>
                    <option value="">-- Sélectionner un domaine --</option>
                    <option value="Intelligence Artificielle" <?= ($equipe['domaine'] ?? '') === 'Intelligence Artificielle' ? 'selected' : '' ?>>Intelligence Artificielle</option>
                    <option value="Sécurité Informatique" <?= ($equipe['domaine'] ?? '') === 'Sécurité Informatique' ? 'selected' : '' ?>>Sécurité Informatique</option>
                    <option value="Cloud Computing" <?= ($equipe['domaine'] ?? '') === 'Cloud Computing' ? 'selected' : '' ?>>Cloud Computing</option>
                    <option value="Réseaux" <?= ($equipe['domaine'] ?? '') === 'Réseaux' ? 'selected' : '' ?>>Réseaux</option>
                    <option value="Systèmes Embarqués" <?= ($equipe['domaine'] ?? '') === 'Systèmes Embarqués' ? 'selected' : '' ?>>Systèmes Embarqués</option>
                    <option value="Big Data" <?= ($equipe['domaine'] ?? '') === 'Big Data' ? 'selected' : '' ?>>Big Data</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="chef_id">Chef d'équipe</label>
                <select name="chef_id" id="chef_id">
                    <option value="">-- Aucun chef assigné --</option>
                    <?php foreach ($membres as $membre): ?>
                        <option value="<?= $membre['id'] ?>" 
                                <?= ($equipe['chef_id'] ?? '') == $membre['id'] ? 'selected' : '' ?>>
                            <?= e($membre['username']) ?> 
                            <?php if (!empty($membre['grade'])): ?>
                                - <?= e($membre['grade']) ?>
                            <?php endif; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="date_creation">Date de création</label>
                <input type="date" 
                       name="date_creation" 
                       id="date_creation" 
                       value="<?= $equipe['date_creation'] ?? date('Y-m-d') ?>">
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="CrudHandler.closeModal()">
                    Annuler
                </button>
                <button type="submit" class="btn-primary">
                    <?= $id ? 'Mettre à jour' : 'Créer l\'équipe' ?>
                </button>
            </div>
        </form>
        <?php
    }
    
    /**
     * Sauvegarder une équipe (création ou mise à jour)
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
                redirect(base_url('admin/equipes/equipes'));
            }
        }
        
        try {
            $data = [
                'nom' => Utils::sanitize($_POST['nom']),
                'description' => Utils::sanitize($_POST['description']),
                'domaine' => Utils::sanitize($_POST['domaine']),
                'chef_id' => !empty($_POST['chef_id']) ? (int)$_POST['chef_id'] : null,
                'date_creation' => $_POST['date_creation'] ?? date('Y-m-d')
            ];
            
            // Validation
            $errors = [];
            
            if (empty($data['nom'])) {
                $errors['nom'] = 'Le nom est requis';
            }
            
            if (empty($data['description'])) {
                $errors['description'] = 'La description est requise';
            }
            
            if (empty($data['domaine'])) {
                $errors['domaine'] = 'Le domaine est requis';
            }
            
            if (!empty($errors)) {
                if ($isAjax) {
                    json(['success' => false, 'message' => 'Erreurs de validation', 'errors' => $errors]);
                } else {
                    $_SESSION['validation_errors'] = $errors;
                    $_SESSION['old_input'] = $_POST;
                    redirect(base_url('admin/equipes/equipes'));
                }
            }
            
            // Créer ou mettre à jour
            if (!empty($_POST['id'])) {
                // Mise à jour
                $id = (int)$_POST['id'];
                $success = $this->equipeModel->update($id, $data);
                $message = 'Équipe mise à jour avec succès';
            } else {
                // Création
                $id = $this->equipeModel->create($data);
                $success = $id > 0;
                $message = 'Équipe créée avec succès';
            }
            
            if ($success) {
                Utils::log("Équipe sauvegardée par " . session('username'));
                
                if ($isAjax) {
                    json(['success' => true, 'message' => $message, 'id' => $id]);
                } else {
                    $_SESSION['success'] = $message;
                    redirect(base_url('admin/equipes/equipes'));
                }
            } else {
                if ($isAjax) {
                    json(['success' => false, 'message' => 'Erreur lors de l\'enregistrement']);
                } else {
                    $_SESSION['error'] = 'Erreur lors de l\'enregistrement';
                    redirect(base_url('admin/equipes/equipes'));
                }
            }
            
        } catch (Exception $e) {
            Utils::log("Erreur sauvegarde équipe: " . $e->getMessage(), 'ERROR');
            
            if ($isAjax) {
                json(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()]);
            } else {
                $_SESSION['error'] = 'Erreur serveur: ' . $e->getMessage();
                redirect(base_url('admin/equipes/equipes'));
            }
        }
    }
    
    /**
     * Supprimer une équipe
     */
    public function delete($id) {
        try {
            // Vérifier si l'équipe a des membres
            $membres = $this->membreModel->getByEquipe($id);
            
            if (count($membres) > 0) {
                json([
                    'success' => false, 
                    'message' => 'Impossible de supprimer une équipe avec des membres. Veuillez d\'abord réassigner les membres.'
                ]);
            }
            
            // Supprimer l'équipe
            $success = $this->equipeModel->delete($id);
            
            if ($success) {
                Utils::log("Équipe #$id supprimée par " . session('username'));
                json(['success' => true, 'message' => 'Équipe supprimée avec succès']);
            } else {
                json(['success' => false, 'message' => 'Erreur lors de la suppression']);
            }
            
        } catch (Exception $e) {
            Utils::log("Erreur suppression équipe: " . $e->getMessage(), 'ERROR');
            json(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Ajouter un membre à une équipe
     */
    public function addMembre() {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            $equipeId = (int)($input['equipe_id'] ?? 0);
            $membreId = (int)($input['membre_id'] ?? 0);
            
            if (!$equipeId || !$membreId) {
                json(['success' => false, 'message' => 'Données invalides']);
            }
            
            // Vérifier que l'équipe existe
            $equipe = $this->equipeModel->getById($equipeId);
            if (!$equipe) {
                json(['success' => false, 'message' => 'Équipe non trouvée']);
            }
            
            // Vérifier que le membre existe
            $membre = $this->membreModel->getById($membreId);
            if (!$membre) {
                json(['success' => false, 'message' => 'Membre non trouvé']);
            }
            
            // Mettre à jour le membre
            $success = $this->membreModel->update($membreId, ['equipe_id' => $equipeId]);
            
            if ($success) {
                Utils::log("Membre #$membreId ajouté à l'équipe #$equipeId par " . session('username'));
                json(['success' => true, 'message' => 'Membre ajouté à l\'équipe avec succès']);
            } else {
                json(['success' => false, 'message' => 'Erreur lors de l\'ajout']);
            }
            
        } catch (Exception $e) {
            Utils::log("Erreur ajout membre: " . $e->getMessage(), 'ERROR');
            json(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Retirer un membre d'une équipe
     */
    public function removeMembre() {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            $membreId = (int)($input['membre_id'] ?? 0);
            
            if (!$membreId) {
                json(['success' => false, 'message' => 'Données invalides']);
            }
            
            // Vérifier que le membre existe
            $membre = $this->membreModel->getById($membreId);
            if (!$membre) {
                json(['success' => false, 'message' => 'Membre non trouvé']);
            }
            
            // Retirer le membre de l'équipe
            $success = $this->membreModel->update($membreId, ['equipe_id' => null]);
            
            if ($success) {
                Utils::log("Membre #$membreId retiré de son équipe par " . session('username'));
                json(['success' => true, 'message' => 'Membre retiré de l\'équipe avec succès']);
            } else {
                json(['success' => false, 'message' => 'Erreur lors du retrait']);
            }
            
        } catch (Exception $e) {
            Utils::log("Erreur retrait membre: " . $e->getMessage(), 'ERROR');
            json(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Exporter les équipes en CSV
     */
    public function export() {
        $equipes = $this->equipeModel->getAllWithChefs();
        
        $data = [];
        $data[] = ['Nom', 'Domaine', 'Chef d\'équipe', 'Nombre de membres', 'Date de création'];
        
        foreach ($equipes as $equipe) {
            $data[] = [
                $equipe['nom'],
                $equipe['domaine'],
                $equipe['chef_nom'] ?? 'Non assigné',
                $equipe['nb_membres'] ?? 0,
                format_date($equipe['date_creation'])
            ];
        }
        
        LabHelpers::exportToCsv($data, 'equipes_' . date('Y-m-d') . '.csv');
    }
    
    /**
     * API - Récupérer une équipe par ID
     */
    public function get($id) {
        $equipe = $this->equipeModel->getById($id);
        
        if ($equipe) {
            json(['success' => true, 'equipe' => $equipe]);
        } else {
            json(['success' => false, 'message' => 'Équipe non trouvée']);
        }
    }
}
?>