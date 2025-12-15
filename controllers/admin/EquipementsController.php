<?php
/**
 * EquipementsController.php - Contrôleur complet pour la gestion des équipements
 * Gère toutes les opérations CRUD + réservations, maintenances, et rapports
 */

require_once __DIR__ . '/../../models/EquipementModel.php';
require_once __DIR__ . '/../../models/CreneauModel.php';
require_once __DIR__ . '/../../models/MembreModel.php';
require_once __DIR__ . '/../../models/EquipeModel.php';
require_once __DIR__ . '/../../lib/helpers.php';
require_once __DIR__ . '/../auth/AuthController.php';

class EquipementsController {
    private $equipementModel;
    private $creneauModel;
    private $membreModel;
    private $equipeModel;
    
    public function __construct() {
        AuthController::requireAdmin();
        $this->equipementModel = new EquipementModel();
        $this->creneauModel = new CreneauModel();
        $this->membreModel = new MembreModel();
        $this->equipeModel = new EquipeModel();
    }
    
    /**
     * Liste des équipements avec filtres et recherche
     */
    public function index() {
        // Récupérer les filtres
        $filters = [
            'type_equipement' => get('type_equipement'),
            'etat' => get('etat'),
            'localisation' => get('localisation'),
            'equipe_id' => get('equipe_id'),
            'search' => get('search')
        ];
        
        // Export CSV si demandé
        if (get('export') === 'csv') {
            $this->export();
            return;
        }
        
        // Récupérer les équipements filtrés
        $equipements = $this->equipementModel->getAllFiltered($filters);
        
        // Pagination
        $page = (int) get('page', 1);
        $perPage = 15;
        $pagination = Utils::paginate(count($equipements), $perPage, $page);
        $equipements = array_slice($equipements, $pagination['offset'], $perPage);
        
        // Charger la vue
        require_once __DIR__ . '/../../views/admin/equipements/equipements.php';
    }
    
    /**
     * Vue détaillée d'un équipement
     */
    public function view($id) {
        // Récupérer l'équipement complet
        $equipement = $this->equipementModel->getWithDetails($id);
        
        if (!$equipement) {
            $_SESSION['error'] = 'Équipement non trouvé';
            redirect(base_url('admin/equipements/equipements'));
        }
        
        // Récupérer les créneaux/réservations
        $creneaux = $this->equipementModel->getCreneaux($id);
        
        // Statistiques
        $stats = [
            'nb_reservations_total' => count($creneaux),
            'nb_reservations_actives' => count(array_filter($creneaux, function($c) {
                return $c['statut'] === 'confirme' && strtotime($c['date_fin']) > time();
            })),
            'taux_utilisation' => $this->calculateTauxUtilisation($id)
        ];
        
        // Charger la vue détaillée
        require_once __DIR__ . '/../../views/admin/equipements/view.php';
    }
    
    /**
     * Formulaire d'ajout/édition d'équipement (AJAX)
     */
    public function form($id = null) {
        // Récupérer l'équipement si édition
        $equipement = $id ? $this->equipementModel->getById($id) : null;
        
        // Récupérer toutes les équipes pour le select
        $equipes = $this->equipeModel->getAll();
        
        // Générer le formulaire
        ?>
        <form id="equipement-form" method="POST" action="<?= base_url('admin/equipements/equipements/save') ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= $equipement['id'] ?? '' ?>">
            
            <div class="form-group">
                <label for="nom">Nom de l'équipement *</label>
                <input type="text" 
                       name="nom" 
                       id="nom" 
                       value="<?= e($equipement['nom'] ?? '') ?>" 
                       required 
                       placeholder="Ex: Serveur Dell PowerEdge R740">
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="type_equipement">Type d'équipement *</label>
                    <select name="type_equipement" id="type_equipement" required>
                        <option value="">-- Sélectionner un type --</option>
                        <option value="Ordinateur" <?= ($equipement['type_equipement'] ?? '') === 'Ordinateur' ? 'selected' : '' ?>>Ordinateur</option>
                        <option value="Serveur" <?= ($equipement['type_equipement'] ?? '') === 'Serveur' ? 'selected' : '' ?>>Serveur</option>
                        <option value="Imprimante" <?= ($equipement['type_equipement'] ?? '') === 'Imprimante' ? 'selected' : '' ?>>Imprimante</option>
                        <option value="Scanner" <?= ($equipement['type_equipement'] ?? '') === 'Scanner' ? 'selected' : '' ?>>Scanner</option>
                        <option value="Réseau" <?= ($equipement['type_equipement'] ?? '') === 'Réseau' ? 'selected' : '' ?>>Équipement réseau</option>
                        <option value="Laboratoire" <?= ($equipement['type_equipement'] ?? '') === 'Laboratoire' ? 'selected' : '' ?>>Équipement de labo</option>
                        <option value="robot" <?= ($equipement['type_equipement'] ?? '') === 'robot' ? 'selected' : '' ?>>Robot</option>
                        <option value="salle" <?= ($equipement['type_equipement'] ?? '') === 'salle' ? 'selected' : '' ?>>Salle</option>
                        <option value="Autre" <?= ($equipement['type_equipement'] ?? '') === 'Autre' ? 'selected' : '' ?>>Autre</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="numero_serie">Numéro de série</label>
                    <input type="text" 
                           name="numero_serie" 
                           id="numero_serie" 
                           value="<?= e($equipement['numero_serie'] ?? '') ?>" 
                           placeholder="Ex: SN123456789">
                </div>
            </div>
            
            <div class="form-group">
                <label for="description">Description</label>
                <textarea name="description" 
                          id="description" 
                          rows="3" 
                          placeholder="Description détaillée de l'équipement"><?= e($equipement['description'] ?? '') ?></textarea>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="etat">État *</label>
                    <select name="etat" id="etat" required>
                        <option value="libre" <?= ($equipement['etat'] ?? 'libre') === 'libre' ? 'selected' : '' ?>>Libre</option>
                        <option value="reserve" <?= ($equipement['etat'] ?? '') === 'reserve' ? 'selected' : '' ?>>Réservé</option>
                        <option value="en_maintenance" <?= ($equipement['etat'] ?? '') === 'en_maintenance' ? 'selected' : '' ?>>En maintenance</option>
                        <option value="hors_service" <?= ($equipement['etat'] ?? '') === 'hors_service' ? 'selected' : '' ?>>Hors service</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="localisation">Localisation</label>
                    <input type="text" 
                           name="localisation" 
                           id="localisation" 
                           value="<?= e($equipement['localisation'] ?? '') ?>" 
                           placeholder="Ex: Bâtiment A, 1er étage">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="equipe_id">Équipe assignée</label>
                    <select name="equipe_id" id="equipe_id">
                        <option value="">-- Aucune équipe --</option>
                        <?php foreach ($equipes as $equipe): ?>
                            <option value="<?= $equipe['id'] ?>" 
                                    <?= ($equipement['equipe_id'] ?? '') == $equipe['id'] ? 'selected' : '' ?>>
                                <?= e($equipe['nom']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="date_acquisition">Date d'acquisition</label>
                    <input type="date" 
                           name="date_acquisition" 
                           id="date_acquisition" 
                           value="<?= $equipement['date_acquisition'] ?? '' ?>">
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="equipements.closeModal()">
                    Annuler
                </button>
                <button type="submit" class="btn-primary">
                    <?= $id ? 'Mettre à jour' : 'Créer l\'équipement' ?>
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
     * Sauvegarder un équipement (création ou mise à jour)
     */
    public function save() {
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                  strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        
        if (!isset($_POST['csrf_token']) || !Utils::verifyCsrfToken($_POST['csrf_token'])) {
            if ($isAjax) {
                json(['success' => false, 'message' => 'Token CSRF invalide']);
            } else {
                $_SESSION['error'] = 'Token CSRF invalide';
                redirect(base_url('admin/equipements/equipements'));
            }
        }
        
        try {
            $data = [
                'nom' => Utils::sanitize($_POST['nom']),
                'type_equipement' => Utils::sanitize($_POST['type_equipement']),
                'numero_serie' => Utils::sanitize($_POST['numero_serie'] ?? ''),
                'description' => Utils::sanitize($_POST['description'] ?? ''),
                'etat' => Utils::sanitize($_POST['etat']),
                'localisation' => Utils::sanitize($_POST['localisation'] ?? ''),
                'equipe_id' => !empty($_POST['equipe_id']) ? (int)$_POST['equipe_id'] : null,
                'date_acquisition' => $_POST['date_acquisition'] ?? null
            ];
            
            // Validation
            $errors = [];
            
            if (empty($data['nom'])) {
                $errors['nom'] = 'Le nom est requis';
            }
            
            if (empty($data['type_equipement'])) {
                $errors['type_equipement'] = 'Le type est requis';
            }
            
            if (empty($data['etat'])) {
                $errors['etat'] = 'L\'état est requis';
            }
            
            if (!empty($errors)) {
                if ($isAjax) {
                    json(['success' => false, 'message' => 'Erreurs de validation', 'errors' => $errors]);
                } else {
                    $_SESSION['validation_errors'] = $errors;
                    $_SESSION['old_input'] = $_POST;
                    redirect(base_url('admin/equipements/equipements'));
                }
            }
            
            // Créer ou mettre à jour
            if (!empty($_POST['id'])) {
                $id = (int)$_POST['id'];
                $success = $this->equipementModel->update($id, $data);
                $message = 'Équipement mis à jour avec succès';
            } else {
                $id = $this->equipementModel->create($data);
                $success = $id > 0;
                $message = 'Équipement créé avec succès';
            }
            
            if ($success) {
                Utils::log("Équipement sauvegardé par " . session('username'));
                
                if ($isAjax) {
                    json(['success' => true, 'message' => $message, 'id' => $id]);
                } else {
                    $_SESSION['success'] = $message;
                    redirect(base_url('admin/equipements/equipements'));
                }
            } else {
                if ($isAjax) {
                    json(['success' => false, 'message' => 'Erreur lors de l\'enregistrement']);
                } else {
                    $_SESSION['error'] = 'Erreur lors de l\'enregistrement';
                    redirect(base_url('admin/equipements/equipements'));
                }
            }
            
        } catch (Exception $e) {
            Utils::log("Erreur sauvegarde équipement: " . $e->getMessage(), 'ERROR');
            
            if ($isAjax) {
                json(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()]);
            } else {
                $_SESSION['error'] = 'Erreur serveur: ' . $e->getMessage();
                redirect(base_url('admin/equipements/equipements'));
            }
        }
    }
    
    /**
     * Supprimer un équipement
     */
    public function delete($id) {
        try {
            // Vérifier si l'équipement a des réservations futures
            if ($this->creneauModel->hasFutureReservations($id)) {
                json([
                    'success' => false, 
                    'message' => 'Impossible de supprimer un équipement avec des réservations futures. Veuillez d\'abord annuler les réservations.'
                ]);
            }
            
            $success = $this->equipementModel->delete($id);
            
            if ($success) {
                Utils::log("Équipement #$id supprimé par " . session('username'));
                json(['success' => true, 'message' => 'Équipement supprimé avec succès']);
            } else {
                json(['success' => false, 'message' => 'Erreur lors de la suppression']);
            }
            
        } catch (Exception $e) {
            Utils::log("Erreur suppression équipement: " . $e->getMessage(), 'ERROR');
            json(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Tableau de bord des équipements
     */
    public function dashboard() {
        // Statistiques générales
        $stats = $this->equipementModel->getStats();
        
        // Équipements par état
        $libres = $this->equipementModel->getDisponibles();
        $reserves = $this->equipementModel->getReserves();
        $maintenance = $this->equipementModel->getEnMaintenance();
        
        // Conflits de réservation
        $conflits = $this->creneauModel->getConflits();
        
        // Prochaines maintenances (à implémenter dans MaintenanceModel si nécessaire)
        $maintenances_prevues = [];
        
        require_once __DIR__ . '/../../views/admin/equipements/dashboard.php';
    }
    
    /**
     * Historique des réservations
     */
    public function historique($equipementId = null) {
        if ($equipementId) {
            // Historique d'un équipement spécifique
            $equipement = $this->equipementModel->getById($equipementId);
            $creneaux = $this->creneauModel->getByEquipement($equipementId);
        } else {
            // Historique global
            $equipement = null;
            $creneaux = $this->creneauModel->getAll();
        }
        
        // Pagination
        $page = (int) get('page', 1);
        $perPage = 20;
        $pagination = Utils::paginate(count($creneaux), $perPage, $page);
        $creneaux = array_slice($creneaux, $pagination['offset'], $perPage);
        
        require_once __DIR__ . '/../../views/admin/equipements/historique.php';
    }
    
    /**
     * Génération de rapports d'utilisation
     */
    public function rapport() {
        $dateDebut = get('date_debut', date('Y-m-01')); // Début du mois
        $dateFin = get('date_fin', date('Y-m-d')); // Aujourd'hui
        
        // Statistiques globales
        $nbReservations = $this->creneauModel->countBetween($dateDebut, $dateFin);
        
        // Taux d'occupation par équipement
        $equipements = $this->equipementModel->getAll();
        $tauxOccupation = [];
        
        if (!empty($equipements)) {
            foreach ($equipements as $equipement) {
                $taux = $this->calculateTauxUtilisation($equipement['id'], $dateDebut, $dateFin);
                $tauxOccupation[] = [
                    'id' => $equipement['id'],
                    'nom' => $equipement['nom'],
                    'taux' => $taux
                ];
            }
        }
        
        // Top utilisateurs
        $statsParMembre = $this->creneauModel->getStatsParMembre($dateDebut, $dateFin);
        
        // S'assurer que les variables sont des tableaux
        if (!is_array($tauxOccupation)) {
            $tauxOccupation = [];
        }
        if (!is_array($statsParMembre)) {
            $statsParMembre = [];
        }
        
        require_once __DIR__ . '/../../views/admin/equipements/rapport.php';
    }
    
    /**
     * Planifier une maintenance
     */
    public function planifierMaintenance() {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            $equipementId = (int)($input['equipement_id'] ?? 0);
            
            if (!$equipementId) {
                json(['success' => false, 'message' => 'Données invalides']);
            }
            
            // Mettre à jour l'état de l'équipement
            $success = $this->equipementModel->updateEtat($equipementId, 'en_maintenance');
            
            // TODO: Créer une entrée dans une table Maintenance si elle existe
            
            if ($success) {
                Utils::log("Maintenance planifiée pour équipement #$equipementId par " . session('username'));
                json(['success' => true, 'message' => 'Maintenance planifiée avec succès']);
            } else {
                json(['success' => false, 'message' => 'Erreur lors de la planification']);
            }
            
        } catch (Exception $e) {
            Utils::log("Erreur planification maintenance: " . $e->getMessage(), 'ERROR');
            json(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Exporter les équipements en CSV
     */
    public function export() {
        $equipements = $this->equipementModel->getAll();
        
        $data = [];
        $data[] = ['Nom', 'Type', 'N° Série', 'État', 'Localisation', 'Équipe', 'Date acquisition'];
        
        foreach ($equipements as $eq) {
            $data[] = [
                $eq['nom'],
                $eq['type_equipement'],
                $eq['numero_serie'] ?? '',
                $eq['etat'],
                $eq['localisation'] ?? '',
                $eq['equipe_nom'] ?? 'Non assigné',
                format_date($eq['date_acquisition'] ?? '')
            ];
        }
        
        LabHelpers::exportToCsv($data, 'equipements_' . date('Y-m-d') . '.csv');
    }
    
    /**
     * API - Récupérer un équipement par ID
     */
    public function get($id) {
        $equipement = $this->equipementModel->getById($id);
        
        if ($equipement) {
            json(['success' => true, 'equipement' => $equipement]);
        } else {
            json(['success' => false, 'message' => 'Équipement non trouvé']);
        }
    }
    
    /**
     * Calculer le taux d'utilisation d'un équipement
     */
    private function calculateTauxUtilisation($equipementId, $dateDebut = null, $dateFin = null) {
        if (!$dateDebut) $dateDebut = date('Y-m-01');
        if (!$dateFin) $dateFin = date('Y-m-d');
        
        try {
            $creneaux = $this->creneauModel->getByEquipement($equipementId);
            
            if (empty($creneaux)) {
                return 0;
            }
            
            $heuresReservees = 0;
            foreach ($creneaux as $creneau) {
                if ($creneau['statut'] === 'confirme') {
                    $debut = max(strtotime($creneau['date_debut']), strtotime($dateDebut));
                    $fin = min(strtotime($creneau['date_fin']), strtotime($dateFin . ' 23:59:59'));
                    
                    if ($fin > $debut) {
                        $heuresReservees += ($fin - $debut) / 3600;
                    }
                }
            }
            
            $joursTotal = (strtotime($dateFin) - strtotime($dateDebut)) / 86400 + 1;
            $heuresDisponibles = $joursTotal * 24;
            
            return $heuresDisponibles > 0 ? round(($heuresReservees / $heuresDisponibles) * 100, 2) : 0;
        } catch (Exception $e) {
            Utils::log("Erreur calcul taux utilisation: " . $e->getMessage(), 'ERROR');
            return 0;
        }
    }
}
?>