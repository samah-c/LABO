<?php
/**
 * EvenementsController.php
 * Gestion complète des événements et communications
 */

require_once __DIR__ . '/../../models/EvenementModel.php';
require_once __DIR__ . '/../../models/MembreModel.php';
require_once __DIR__ . '/../../lib/helpers.php';
require_once __DIR__ . '/../../lib/ViewComponents.php';

class EvenementsController {
    private $evenementModel;
    private $membreModel;
    
    public function __construct() {
        $this->evenementModel = new EvenementModel();
        $this->membreModel = new MembreModel();
    }
    
    // ========================================
    // AFFICHAGE DE LA LISTE
    // ========================================
    
    public function index() {
        $filters = [
            'type_evenement' => $_GET['type_evenement'] ?? null,
            'statut' => $_GET['statut'] ?? null,
            'search' => $_GET['search'] ?? null
        ];
        
        $evenements = $this->evenementModel->getAllWithOrganisateurs();
        
        // Filtrer les événements
        if (!empty($filters['type_evenement'])) {
            $evenements = array_filter($evenements, function($e) use ($filters) {
                return $e['type_evenement'] === $filters['type_evenement'];
            });
        }
        
        if (!empty($filters['statut'])) {
            $evenements = array_filter($evenements, function($e) use ($filters) {
                $isUpcoming = strtotime($e['date_evenement']) > time();
                if ($filters['statut'] === 'a_venir') return $isUpcoming;
                if ($filters['statut'] === 'termine') return !$isUpcoming;
                return true;
            });
        }
        
        if (!empty($filters['search'])) {
            $search = strtolower($filters['search']);
            $evenements = array_filter($evenements, function($e) use ($search) {
                return strpos(strtolower($e['titre']), $search) !== false ||
                       strpos(strtolower($e['description'] ?? ''), $search) !== false;
            });
        }
        
        require_once __DIR__ . '/../../views/admin/evenements/evenements.php';
    }
    
    // ========================================
    // FORMULAIRE (AJAX)
    // ========================================
    
    public function form($id = null) {
        if (!isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            http_response_code(400);
            echo "Requête invalide";
            return;
        }
        
        $evenement = null;
        
        if ($id) {
            $evenement = $this->evenementModel->getById($id);
            if (!$evenement) {
                echo '<p class="error">Événement introuvable</p>';
                return;
            }
        }
        
        $membres = $this->membreModel->getAllMembresWithUser();
        
        $this->renderForm($evenement, $membres);
    }
    
    private function renderForm($evenement, $membres) {
        $isEdit = !empty($evenement);
        ?>
        <form id="evenement-form" action="<?= base_url('admin/evenements/evenements/save') ?>" method="POST">
            <?php if ($isEdit): ?>
                <input type="hidden" name="id" value="<?= $evenement['id'] ?>">
            <?php endif; ?>
            
            <!-- Titre -->
            <div class="form-group">
                <label for="titre">Titre *</label>
                <input type="text" 
                       id="titre" 
                       name="titre" 
                       value="<?= e($evenement['titre'] ?? '') ?>" 
                       required 
                       placeholder="Titre de l'événement">
            </div>
            
            <!-- Type et Date -->
            <div class="form-row">
                <div class="form-group">
                    <label for="type_evenement">Type *</label>
                    <select id="type_evenement" name="type_evenement" required>
                        <option value="">-- Sélectionner --</option>
                        <option value="conference" <?= ($evenement['type_evenement'] ?? '') === 'conference' ? 'selected' : '' ?>>Conférence</option>
                        <option value="atelier" <?= ($evenement['type_evenement'] ?? '') === 'atelier' ? 'selected' : '' ?>>Atelier</option>
                        <option value="seminaire" <?= ($evenement['type_evenement'] ?? '') === 'seminaire' ? 'selected' : '' ?>>Séminaire</option>
                        <option value="soutenance" <?= ($evenement['type_evenement'] ?? '') === 'soutenance' ? 'selected' : '' ?>>Soutenance</option>
                        <option value="autre" <?= ($evenement['type_evenement'] ?? '') === 'autre' ? 'selected' : '' ?>>Autre</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="date_evenement">Date et heure *</label>
                    <input type="datetime-local" 
                           id="date_evenement" 
                           name="date_evenement" 
                           value="<?= isset($evenement['date_evenement']) ? date('Y-m-d\TH:i', strtotime($evenement['date_evenement'])) : '' ?>" 
                           required>
                </div>
            </div>
            
            <!-- Lieu et Organisateur -->
            <div class="form-row">
                <div class="form-group">
                    <label for="lieu">Lieu *</label>
                    <input type="text" 
                           id="lieu" 
                           name="lieu" 
                           value="<?= e($evenement['lieu'] ?? '') ?>" 
                           required 
                           placeholder="Lieu de l'événement">
                </div>
                
                <div class="form-group">
                    <label for="organisateur_id">Organisateur</label>
                    <select id="organisateur_id" name="organisateur_id">
                        <option value="">-- Aucun --</option>
                        <?php foreach ($membres as $membre): ?>
                            <option value="<?= $membre['id'] ?>" 
                                    <?= ($evenement['organisateur_id'] ?? '') == $membre['id'] ? 'selected' : '' ?>>
                                <?= e($membre['username']) ?>
                                <?= $membre['grade'] ? ' - ' . e($membre['grade']) : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <!-- Description -->
            <div class="form-group">
                <label for="description">Description *</label>
                <textarea id="description" 
                          name="description" 
                          rows="4" 
                          required 
                          placeholder="Description de l'événement"><?= e($evenement['description'] ?? '') ?></textarea>
            </div>
            
            <!-- Lien d'inscription -->
            <div class="form-group">
                <label for="lien_inscription">Lien d'inscription</label>
                <input type="url" 
                       id="lien_inscription" 
                       name="lien_inscription" 
                       value="<?= e($evenement['lien_inscription'] ?? '') ?>" 
                       placeholder="https://...">
                <small>Optionnel - Pour les inscriptions externes</small>
            </div>
            
            <!-- Boutons -->
            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="evenements.closeModal()">
                    Annuler
                </button>
                <button type="submit" class="btn-primary">
                    Enregistrer
                </button>
            </div>
        </form>
        
        <script>
        (function() {
            const form = document.getElementById('evenement-form');
            if (form) {
                const newForm = form.cloneNode(true);
                form.parentNode.replaceChild(newForm, form);
                
                newForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    const handler = window.evenements || window.parent.evenements;
                    
                    if (handler && typeof handler.submitForm === 'function') {
                        handler.submitForm(this);
                    } else {
                        console.error('Handler evenements introuvable');
                        alert('Erreur: Handler non initialisé');
                    }
                    
                    return false;
                });
            }
        })();
        </script>
        
        <style>
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }
        </style>
        <?php
    }
    
    // ========================================
    // SAUVEGARDE
    // ========================================
    
    public function save() {
        header('Content-Type: application/json');
        
        try {
            $id = $_POST['id'] ?? null;
            
            $errors = $this->validate($_POST);
            if (!empty($errors)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Erreurs de validation',
                    'errors' => $errors
                ]);
                return;
            }
            
            $data = [
                'type_evenement' => $_POST['type_evenement'],
                'titre' => trim($_POST['titre']),
                'description' => trim($_POST['description']),
                'date_evenement' => $_POST['date_evenement'],
                'lieu' => trim($_POST['lieu']),
                'organisateur_id' => $_POST['organisateur_id'] ?: null,
                'lien_inscription' => trim($_POST['lien_inscription']) ?: null
            ];
            
            if ($id) {
                $this->evenementModel->update($id, $data);
                $message = 'Événement modifié avec succès';
            } else {
                $id = $this->evenementModel->create($data);
                $message = 'Événement créé avec succès';
            }
            
            echo json_encode([
                'success' => true,
                'message' => $message,
                'id' => $id
            ]);
            
        } catch (Exception $e) {
            error_log("Erreur save evenement: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Erreur lors de l\'enregistrement: ' . $e->getMessage()
            ]);
        }
    }
    
    private function validate($data) {
        $errors = [];
        
        if (empty(trim($data['titre'] ?? ''))) {
            $errors['titre'] = 'Le titre est requis';
        }
        
        if (empty($data['type_evenement'] ?? '')) {
            $errors['type_evenement'] = 'Le type est requis';
        }
        
        if (empty($data['date_evenement'] ?? '')) {
            $errors['date_evenement'] = 'La date est requise';
        }
        
        if (empty(trim($data['lieu'] ?? ''))) {
            $errors['lieu'] = 'Le lieu est requis';
        }
        
        if (empty(trim($data['description'] ?? ''))) {
            $errors['description'] = 'La description est requise';
        }
        
        return $errors;
    }
    
    // ========================================
    // VUE DÉTAILLÉE
    // ========================================
    
    public function view($id) {
        $evenement = $this->evenementModel->getById($id);
        
        if (!$evenement) {
            $_SESSION['error'] = 'Événement introuvable';
            redirect(base_url('admin/evenements/evenements'));
            return;
        }
        
        // Récupérer l'organisateur
        $organisateur = null;
        if ($evenement['organisateur_id']) {
            $organisateur = $this->membreModel->getById($evenement['organisateur_id']);
            if ($organisateur) {
                $db = $this->evenementModel->getConnection();
                $stmt = $db->prepare("SELECT username FROM User WHERE id = ?");
                $stmt->execute([$organisateur['user_id']]);
                $user = $stmt->fetch();
                $organisateur['username'] = $user['username'] ?? 'Inconnu';
            }
        }
        
        require_once __DIR__ . '/../../views/admin/evenements/evenement-detail.php';
    }
    
    // ========================================
    // RÉCUPÉRATION (API)
    // ========================================
    
    public function get($id) {
        header('Content-Type: application/json');
        
        $evenement = $this->evenementModel->getById($id);
        
        if (!$evenement) {
            echo json_encode([
                'success' => false,
                'message' => 'Événement introuvable'
            ]);
            return;
        }
        
        echo json_encode([
            'success' => true,
            'evenement' => $evenement
        ]);
    }
    
    // ========================================
    // SUPPRESSION
    // ========================================
    
    public function delete($id) {
        header('Content-Type: application/json');
        
        try {
            $evenement = $this->evenementModel->getById($id);
            
            if (!$evenement) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Événement introuvable'
                ]);
                return;
            }
            
            $this->evenementModel->delete($id);
            
            echo json_encode([
                'success' => true,
                'message' => 'Événement supprimé avec succès'
            ]);
            
        } catch (Exception $e) {
            error_log("Erreur delete evenement: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Erreur lors de la suppression'
            ]);
        }
    }
    
    // ========================================
    // EXPORT
    // ========================================
    
    public function export() {
        $evenements = $this->evenementModel->getAllWithOrganisateurs();
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="evenements_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        fputcsv($output, ['ID', 'Titre', 'Type', 'Date', 'Lieu', 'Organisateur']);
        
        foreach ($evenements as $ev) {
            fputcsv($output, [
                $ev['id'],
                $ev['titre'],
                $ev['type_evenement'],
                $ev['date_evenement'],
                $ev['lieu'],
                $ev['organisateur_nom'] ?? ''
            ]);
        }
        
        fclose($output);
        exit;
    }
}