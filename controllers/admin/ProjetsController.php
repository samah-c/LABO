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

require_once __DIR__ . '/../../views/admin/projets/ProjetsListView.php';
require_once __DIR__ . '/../../views/admin/projets/ProjetDetailView.php';
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
        'annee' => get('annee'),  
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
    $view = new ProjetsListView($projets, $pagination);
    $view->render();
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
               $view = new ProjetDetailView(
                           $projet,
                           $responsable,
                           $membres,
                           $publications,
                           $stats
                );
                $view->render();
    }
    
    /**
     * Formulaire d'ajout/édition de projet (AJAX)
     */
public function form($id = null) {
    // Récupérer le projet si édition
    $projet = $id ? $this->projetModel->getById($id) : null;
    
    // Déterminer quels membres afficher pour le select du responsable
    if ($id) {
        // En mode ÉDITION : afficher uniquement les membres du projet
        $membresProjet = $this->projetModel->getMembres($id);
        $membres = [];
        
        // Récupérer les détails complets de chaque membre
        foreach ($membresProjet as $membreProjet) {
            $membreDetails = $this->membreModel->getById($membreProjet['id']);
            if ($membreDetails) {
                // Combiner les infos du projet avec les détails du membre
                $membres[] = array_merge($membreDetails, [
                    'username' => $membreProjet['username'] ?? $membreDetails['username'],
                    'grade' => $membreProjet['grade'] ?? $membreDetails['grade'] ?? ''
                ]);
            }
        }
        
        // Ajouter le responsable actuel s'il n'est pas déjà dans la liste
        if (!empty($projet['responsable_id'])) {
            $responsableActuel = $this->membreModel->getById($projet['responsable_id']);
            $responsableExists = false;
            
            foreach ($membres as $m) {
                if ($m['id'] == $projet['responsable_id']) {
                    $responsableExists = true;
                    break;
                }
            }
            
            if (!$responsableExists && $responsableActuel) {
                array_unshift($membres, $responsableActuel);
            }
        }
    } else {
        // En mode CRÉATION : afficher tous les membres disponibles
        $membres = $this->membreModel->getAllMembresWithUser();
    }
    
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
            <label for="description">Description *</label>
            <textarea name="description" 
                      id="description" 
                      rows="4" 
                      required 
                      placeholder="Description détaillée du projet"><?= e($projet['description'] ?? '') ?></textarea>
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
                <label for="status">Status *</label>
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
            <label for="responsable_id">
                Responsable scientifique *
                <?php if ($id): ?>
                    <small style="color: #6B7280; font-weight: normal;">
                        (Sélectionner parmi les membres du projet)
                    </small>
                <?php endif; ?>
            </label>
            <select name="responsable_id" id="responsable_id" required>
                <option value="">-- Sélectionner un responsable --</option>
                <?php if (empty($membres)): ?>
                    <option value="" disabled>
                        <?= $id ? 'Aucun membre dans ce projet' : 'Aucun membre disponible' ?>
                    </option>
                <?php else: ?>
                    <?php foreach ($membres as $membre): ?>
                        <option value="<?= $membre['id'] ?>" 
                                <?= ($projet['responsable_id'] ?? '') == $membre['id'] ? 'selected' : '' ?>>
                            <?= e($membre['username']) ?> 
                            <?php if (!empty($membre['grade'])): ?>
                                - <?= e($membre['grade']) ?>
                            <?php endif; ?>
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
            <?php if ($id && empty($membres)): ?>
                <p style="color: #EF4444; font-size: 13px; margin-top: 8px;">
                    Aucun membre n'est assigné à ce projet. Ajoutez d'abord des membres avant de changer le responsable.
                </p>
            <?php endif; ?>
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
                'description' => Utils::sanitize($_POST['description']),
                'thematique' => Utils::sanitize($_POST['thematique']),
                'status' => Utils::sanitize($_POST['status']),
                'responsable_id' => (int)$_POST['responsable_id'],
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
     * Exporter les projets en CSV
     */
    public function export() {
    // Récupérer les filtres appliqués
    $filters = [
        'thematique' => get('thematique'),
        'status' => get('status'),
        'annee' => get('annee'),
        'search' => get('search')
    ];
    
    // Récupérer tous les projets avec les filtres appliqués
    $projets = $this->projetModel->getAllFiltered($filters);
    
    // Préparer les données pour l'export
    $data = [];
    
    // En-têtes du CSV
    $data[] = [
        'Titre', 
        'Thématique', 
        'Responsable', 
        'Statut',  // Correction: utilisez 'Statut' au lieu de 'status'
        'Date début', 
        'Date fin', 
        'Nb membres'
    ];
    
    // Données des projets
    foreach ($projets as $projet) {
        // Compter les membres pour ce projet
        $membres = $this->projetModel->getMembres($projet['id']);
        $nbMembres = count($membres);
        
        $data[] = [
            $projet['titre'] ?? '',
            $projet['thematique'] ?? '',
            $projet['responsable_username'] ?? $projet['responsable_nom'] ?? 'Non assigné',
            $projet['status'] ?? 'en_cours',  // Valeur par défaut si status absent
            !empty($projet['date_debut']) ? format_date($projet['date_debut'], 'd/m/Y') : '',
            !empty($projet['date_fin']) ? format_date($projet['date_fin'], 'd/m/Y') : '',
            $nbMembres
        ];
    }
    
    // Générer et télécharger le fichier CSV
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

    /**
 * Récupérer les membres disponibles pour un projet (AJAX)
 */
public function getMembresDisponibles($projetId) {
    // Vérifier que c'est une requête AJAX
    if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || 
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
        header('HTTP/1.1 400 Bad Request');
        json(['success' => false, 'message' => 'Requête invalide']);
        return;
    }
    
    try {
        // Vérifier que le projet existe
        $projet = $this->projetModel->getById($projetId);
        if (!$projet) {
            json(['success' => false, 'message' => 'Projet non trouvé']);
            return;
        }
        
        // Récupérer tous les membres avec leurs détails utilisateur
        $tousMembres = $this->membreModel->getAllMembresWithUser();
        
        // Log pour debug (à retirer en production)
        error_log("Tous les membres: " . count($tousMembres));
        
        // Récupérer les membres déjà dans le projet
        $membresProjet = $this->projetModel->getMembres($projetId);
        $membresProjetIds = array_column($membresProjet, 'id');
        
        // Log pour debug
        error_log("Membres du projet: " . implode(', ', $membresProjetIds));
        
        // Récupérer aussi le responsable du projet pour l'exclure
        if (!empty($projet['responsable_id'])) {
            $membresProjetIds[] = (int)$projet['responsable_id'];
        }
        
        // Log pour debug
        error_log("IDs à exclure: " . implode(', ', $membresProjetIds));
        
        // Filtrer les membres disponibles
        $membresDisponibles = array_filter($tousMembres, function($membre) use ($membresProjetIds) {
            $membreId = (int)$membre['id'];
            $isAvailable = !in_array($membreId, $membresProjetIds);
            
            // Log pour debug
            error_log("Membre {$membreId} ({$membre['username']}): " . ($isAvailable ? 'disponible' : 'déjà dans le projet'));
            
            return $isAvailable;
        });
        
        // Réindexer le tableau
        $membresDisponibles = array_values($membresDisponibles);
        
        // Log final
        error_log("Membres disponibles: " . count($membresDisponibles));
        
        json([
            'success' => true, 
            'membres' => $membresDisponibles,
            'debug' => [
                'total_membres' => count($tousMembres),
                'membres_projet' => count($membresProjet),
                'membres_disponibles' => count($membresDisponibles)
            ]
        ]);
        
    } catch (Exception $e) {
        error_log("Erreur getMembresDisponibles: " . $e->getMessage());
        json(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()]);
    }
}

/**
     * Générer un rapport PDF pour un projet
     */
    public function genererRapport($id) {
        // IMPORTANT: Désactiver l'affichage des erreurs pour éviter les outputs
        error_reporting(0);
        ini_set('display_errors', '0');
        
        // Nettoyer tous les buffers de sortie existants
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        // Démarrer un buffer propre
        ob_start();
        
        try {
            $projet = $this->projetModel->getById($id);
            
            if (!$projet) {
                throw new Exception('Projet non trouvé');
            }
            
            // Récupérer toutes les données nécessaires
            $membres = $this->projetModel->getMembres($id);
            $publications = $this->projetModel->getPublications($id);
            $responsable = null;
            
            if (!empty($projet['responsable_id'])) {
                $responsable = $this->membreModel->getWithDetails($projet['responsable_id']);
            }
            
            // Si pas de responsable trouvé, utiliser les données du projet
            if (!$responsable && !empty($projet['responsable_username'])) {
                $responsable = [
                    'id' => $projet['responsable_id'],
                    'username' => $projet['responsable_username']
                ];
            }
            
            // Charger la classe d'export PDF
            require_once __DIR__ . '/../../views/admin/projets/RapportProjetPDF.php';
            
            // Créer l'instance
            $pdfExport = new ProjetPdfExportView(
                $projet,
                $responsable,
                $membres,
                $publications
            );
            
            // Générer le PDF (la méthode gère elle-même l'output et exit)
            $pdfExport->generate();
            
        } catch (Exception $e) {
            // En cas d'erreur, nettoyer et afficher un message
            while (ob_get_level()) {
                ob_end_clean();
            }
            
            header('Content-Type: text/html; charset=utf-8');
            echo '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body>';
            echo '<h1>Erreur lors de la génération du PDF</h1>';
            echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
            echo '<p><a href="' . base_url('admin/projets/projets/view/' . $id) . '">← Retour au projet</a></p>';
            echo '</body></html>';
            exit;
        }
    }
}
?>