<?php
/**
 * PublicationsController.php
 * Gestion complète des publications avec validation et rapports
 */

require_once __DIR__ . '/../../models/PublicationModel.php';
require_once __DIR__ . '/../../models/MembreModel.php';
require_once __DIR__ . '/../../models/ProjetModel.php';
require_once __DIR__ . '/../../lib/helpers.php';
require_once __DIR__ . '/../../lib/ViewComponents.php';

class PublicationsController {
    private $publicationModel;
    private $membreModel;
    private $projetModel;
    
    public function __construct() {
        $this->publicationModel = new PublicationModel();
        $this->membreModel = new MembreModel();
        $this->projetModel = new ProjetModel();
    }
    
    // ========================================
    // AFFICHAGE DE LA LISTE
    // ========================================
    
    public function index() {
        // Récupérer les filtres
        $filters = [
            'type_publication' => $_GET['type'] ?? null,
            'domaine' => $_GET['domaine'] ?? null,
            'annee' => $_GET['annee'] ?? null,
            'projet_id' => $_GET['projet_id'] ?? null,
            'search' => $_GET['search'] ?? null,
            'statut' => $_GET['statut'] ?? null // Pour filtrer par statut de validation
        ];
        
        // Récupérer les publications filtrées
        $publications = $this->publicationModel->getAllFiltered($filters);
        
        // Enrichir avec les noms des auteurs
        foreach ($publications as &$pub) {
            $auteurs = $this->publicationModel->getAuteurs($pub['id']);
            $pub['auteurs_noms'] = implode(', ', array_column($auteurs, 'username'));
        }
        
        // Récupérer les options pour les filtres
        $types = $this->publicationModel->getTypes();
        $domaines = $this->publicationModel->getDomaines();
        $annees = $this->publicationModel->getAnnees();
        $projets = $this->publicationModel->getProjets();
        
        // Charger la vue
        require_once __DIR__ . '/../../views/admin/publications/publications.php';
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
        
        $publication = null;
        $auteurs = [];
        
        if ($id) {
            $publication = $this->publicationModel->getById($id);
            if (!$publication) {
                echo '<p class="error">Publication introuvable</p>';
                return;
            }
            $auteurs = $this->publicationModel->getAuteurs($id);
        }
        
        // Récupérer les listes pour les selects
        $projets = $this->projetModel->getAll();
        $membres = $this->membreModel->getAllMembresWithUser();
        
        // Générer le formulaire
        $this->renderForm($publication, $auteurs, $projets, $membres);
    }
    
    private function renderForm($publication, $auteurs, $projets, $membres) {
        $isEdit = !empty($publication);
        ?>
        <form id="publication-form" action="<?= base_url('admin/publications/publications/save') ?>" method="POST">
            <?php if ($isEdit): ?>
                <input type="hidden" name="id" value="<?= $publication['id'] ?>">
            <?php endif; ?>
            
            <!-- Titre -->
            <div class="form-group">
                <label for="titre">Titre *</label>
                <input type="text" 
                       id="titre" 
                       name="titre" 
                       value="<?= e($publication['titre'] ?? '') ?>" 
                       required 
                       placeholder="Titre de la publication">
            </div>
            
            <!-- Type et Domaine -->
            <div class="form-row">
                <div class="form-group">
                    <label for="type_publication">Type *</label>
                    <select id="type_publication" name="type_publication" required>
                        <option value="">-- Sélectionner --</option>
                        <option value="Article" <?= ($publication['type_publication'] ?? '') === 'Article' ? 'selected' : '' ?>>Article</option>
                        <option value="Conférence" <?= ($publication['type_publication'] ?? '') === 'Conférence' ? 'selected' : '' ?>>Conférence</option>
                        <option value="Thèse" <?= ($publication['type_publication'] ?? '') === 'Thèse' ? 'selected' : '' ?>>Thèse</option>
                        <option value="Rapport" <?= ($publication['type_publication'] ?? '') === 'Rapport' ? 'selected' : '' ?>>Rapport</option>
                        <option value="Livre" <?= ($publication['type_publication'] ?? '') === 'Livre' ? 'selected' : '' ?>>Livre</option>
                        <option value="Chapitre" <?= ($publication['type_publication'] ?? '') === 'Chapitre' ? 'selected' : '' ?>>Chapitre</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="domaine">Domaine</label>
                    <select id="domaine" name="domaine">
                        <option value="">-- Sélectionner --</option>
                        <option value="IA" <?= ($publication['domaine'] ?? '') === 'IA' ? 'selected' : '' ?>>Intelligence Artificielle</option>
                        <option value="Sécurité" <?= ($publication['domaine'] ?? '') === 'Sécurité' ? 'selected' : '' ?>>Sécurité</option>
                        <option value="Réseaux" <?= ($publication['domaine'] ?? '') === 'Réseaux' ? 'selected' : '' ?>>Réseaux</option>
                        <option value="Blockchain" <?= ($publication['domaine'] ?? '') === 'Blockchain' ? 'selected' : '' ?>>Blockchain</option>
                        <option value="IoT" <?= ($publication['domaine'] ?? '') === 'IoT' ? 'selected' : '' ?>>IoT</option>
                        <option value="Big Data" <?= ($publication['domaine'] ?? '') === 'Big Data' ? 'selected' : '' ?>>Big Data</option>
                    </select>
                </div>
            </div>
            
            <!-- Date et Projet -->
            <div class="form-row">
                <div class="form-group">
                    <label for="date_publication">Date de publication *</label>
                    <input type="date" 
                           id="date_publication" 
                           name="date_publication" 
                           value="<?= $publication['date_publication'] ?? '' ?>" 
                           required>
                </div>
                
                <div class="form-group">
                    <label for="projet_id">Projet associé</label>
                    <select id="projet_id" name="projet_id">
                        <option value="">-- Aucun --</option>
                        <?php foreach ($projets as $projet): ?>
                            <option value="<?= $projet['id'] ?>" 
                                    <?= ($publication['projet_id'] ?? '') == $projet['id'] ? 'selected' : '' ?>>
                                <?= e($projet['titre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <!-- Résumé -->
            <div class="form-group">
                <label for="resume">Résumé *</label>
                <textarea id="resume" 
                          name="resume" 
                          rows="4" 
                          required 
                          placeholder="Résumé de la publication"><?= e($publication['resume'] ?? '') ?></textarea>
            </div>
            
            <!-- DOI et Lien -->
            <div class="form-row">
                <div class="form-group">
                    <label for="doi">DOI</label>
                    <input type="text" 
                           id="doi" 
                           name="doi" 
                           value="<?= e($publication['doi'] ?? '') ?>" 
                           placeholder="10.1000/xyz123">
                </div>
                
                <div class="form-group">
                    <label for="lien">Lien de téléchargement</label>
                    <input type="url" 
                           id="lien" 
                           name="lien" 
                           value="<?= e($publication['lien'] ?? '') ?>" 
                           placeholder="https://...">
                </div>
            </div>
            
            <!-- Statut de validation -->
            <div class="form-group">
                <label for="statut_validation">Statut de validation *</label>
                <select id="statut_validation" name="statut_validation" required>
                    <option value="en_attente" <?= ($publication['statut_validation'] ?? 'en_attente') === 'en_attente' ? 'selected' : '' ?>>En attente</option>
                    <option value="valide" <?= ($publication['statut_validation'] ?? '') === 'valide' ? 'selected' : '' ?>>Validé</option>
                    <option value="rejete" <?= ($publication['statut_validation'] ?? '') === 'rejete' ? 'selected' : '' ?>>Rejeté</option>
                </select>
                <small>Les publications validées sont visibles publiquement</small>
            </div>
            
            <!-- Auteurs -->
            <div class="form-group">
                <label>Auteurs *</label>
                <div id="auteurs-container">
                    <?php if (!empty($auteurs)): ?>
                        <?php foreach ($auteurs as $index => $auteur): ?>
                            <div class="auteur-row" data-index="<?= $index ?>">
                                <select name="auteurs[]" required>
                                    <option value="">-- Sélectionner --</option>
                                    <?php foreach ($membres as $membre): ?>
                                        <option value="<?= $membre['id'] ?>" 
                                                <?= $auteur['id'] == $membre['id'] ? 'selected' : '' ?>>
                                            <?= e($membre['username']) ?> 
                                            <?= $membre['grade'] ? '- ' . e($membre['grade']) : '' ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="button" class="btn-icon btn-remove" onclick="removeAuteur(this)">🗑️</button>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="auteur-row" data-index="0">
                            <select name="auteurs[]" required>
                                <option value="">-- Sélectionner --</option>
                                <?php foreach ($membres as $membre): ?>
                                    <option value="<?= $membre['id'] ?>">
                                        <?= e($membre['username']) ?> 
                                        <?= $membre['grade'] ? '- ' . e($membre['grade']) : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="button" class="btn-icon btn-remove" onclick="removeAuteur(this)">🗑️</button>
                        </div>
                    <?php endif; ?>
                </div>
                <button type="button" class="btn-secondary btn-sm" onclick="addAuteur()">
                     Ajouter un auteur
                </button>
            </div>
            
            <!-- Boutons -->
            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="publications.closeModal()">
                    Annuler
                </button>
                <button type="submit" class="btn-primary">
                     Enregistrer
                </button>
            </div>
        </form>
        
<script>
(function() {
    let auteurIndex = <?= count($auteurs) ?>;
    const membresOptions = `
        <option value="">-- Sélectionner --</option>
        <?php foreach ($membres as $membre): ?>
            <option value="<?= $membre['id'] ?>">
                <?= e($membre['username']) ?> 
                <?= $membre['grade'] ? '- ' . e($membre['grade']) : '' ?>
            </option>
        <?php endforeach; ?>
    `;
    
    window.addAuteur = function() {
        const container = document.getElementById('auteurs-container');
        const row = document.createElement('div');
        row.className = 'auteur-row';
        row.dataset.index = auteurIndex++;
        row.innerHTML = `
            <select name="auteurs[]" required>
                ${membresOptions}
            </select>
            <button type="button" class="btn-icon btn-remove" onclick="removeAuteur(this)">X</button>
        `;
        container.appendChild(row);
    };
    
    window.removeAuteur = function(button) {
        const container = document.getElementById('auteurs-container');
        const rows = container.querySelectorAll('.auteur-row');
        
        if (rows.length > 1) {
            button.closest('.auteur-row').remove();
        } else {
            alert('Il doit y avoir au moins un auteur');
        }
    };
    
    // IMPORTANT: Attacher l'event IMMÉDIATEMENT
    const form = document.getElementById('publication-form');
    if (form) {
        // Retirer tous les anciens listeners
        const newForm = form.cloneNode(true);
        form.parentNode.replaceChild(newForm, form);
        
        // Attacher le nouveau listener
        newForm.addEventListener('submit', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            console.log('Form submit intercepté');
            console.log('window.publications existe?', !!window.publications);
            console.log('window.parent.publications existe?', !!window.parent.publications);
            
            const handler = window.publications || window.parent.publications;
            
            if (handler && typeof handler.submitForm === 'function') {
                handler.submitForm(this);
            } else {
                console.error('Handler publications introuvable');
                alert('Erreur: Handler non initialisé. Veuillez recharger la page.');
            }
            
            return false;
        });
        
        console.log('Event listener attaché au formulaire');
    } else {
        console.error('Formulaire publication-form introuvable');
    }
})();
</script>
        
        <style>
        .auteur-row {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
            align-items: center;
        }
        
        .auteur-row select {
            flex: 1;
        }
        
        .btn-icon {
            padding: 8px 12px;
            border: none;
            background: #EF4444;
            color: white;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
        }
        
        .btn-icon:hover {
            background: #DC2626;
        }
        
        .btn-sm {
            padding: 8px 16px;
            font-size: 14px;
        }
        
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
            
            // Validation
            $errors = $this->validate($_POST);
            if (!empty($errors)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Erreurs de validation',
                    'errors' => $errors
                ]);
                return;
            }
            
            // Préparer les données
            $data = [
                'titre' => trim($_POST['titre']),
                'type_publication' => $_POST['type_publication'],
                'domaine' => $_POST['domaine'] ?: null,
                'date_publication' => $_POST['date_publication'],
                'projet_id' => $_POST['projet_id'] ?: null,
                'resume' => trim($_POST['resume']),
                'doi' => trim($_POST['doi']) ?: null,
                'lien' => trim($_POST['lien']) ?: null,
                'statut_validation' => $_POST['statut_validation'] ?? 'en_attente'
            ];
            
            // Créer ou mettre à jour
            if ($id) {
                $this->publicationModel->update($id, $data);
                $publicationId = $id;
                $message = 'Publication modifiée avec succès';
            } else {
                $publicationId = $this->publicationModel->create($data);
                $message = 'Publication créée avec succès';
            }
            
            // Gérer les auteurs
            if (isset($_POST['auteurs']) && is_array($_POST['auteurs'])) {
                // Supprimer les anciens auteurs
                $stmt = $this->publicationModel->getConnection()->prepare("DELETE FROM Publication_Auteur WHERE publication_id = ?");
                $stmt->execute([$publicationId]);
                
                // Ajouter les nouveaux auteurs
                foreach ($_POST['auteurs'] as $ordre => $membreId) {
                    if (!empty($membreId)) {
                        $this->publicationModel->addAuteur($publicationId, $membreId, $ordre + 1);
                    }
                }
            }
            
            echo json_encode([
                'success' => true,
                'message' => $message,
                'id' => $publicationId
            ]);
            
        } catch (Exception $e) {
            error_log("Erreur save publication: " . $e->getMessage());
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
        
        if (empty($data['type_publication'] ?? '')) {
            $errors['type_publication'] = 'Le type est requis';
        }
        
        if (empty($data['date_publication'] ?? '')) {
            $errors['date_publication'] = 'La date est requise';
        }
        
        if (empty(trim($data['resume'] ?? ''))) {
            $errors['resume'] = 'Le résumé est requis';
        }
        
        if (empty($data['auteurs']) || !is_array($data['auteurs']) || count(array_filter($data['auteurs'])) === 0) {
            $errors['auteurs'] = 'Au moins un auteur est requis';
        }
        
        return $errors;
    }
    
    // ========================================
    // VUE DÉTAILLÉE
    // ========================================
    
    public function view($id) {
        $publication = $this->publicationModel->getById($id);
        
        if (!$publication) {
            $_SESSION['error'] = 'Publication introuvable';
            redirect(base_url('admin/publications/publications'));
            return;
        }
        
        // Récupérer les auteurs
        $auteurs = $this->publicationModel->getAuteurs($id);
        
        // Récupérer le projet si associé
        $projet = null;
        if ($publication['projet_id']) {
            $projet = $this->projetModel->getById($publication['projet_id']);
        }
        
        require_once __DIR__ . '/../../views/admin/publications/publication-detail.php';
    }
    
    // ========================================
    // RÉCUPÉRATION (API)
    // ========================================
    
    public function get($id) {
        header('Content-Type: application/json');
        
        $publication = $this->publicationModel->getById($id);
        
        if (!$publication) {
            echo json_encode([
                'success' => false,
                'message' => 'Publication introuvable'
            ]);
            return;
        }
        
        // Récupérer les auteurs
        $auteurs = $this->publicationModel->getAuteurs($id);
        $publication['auteurs'] = $auteurs;
        
        echo json_encode([
            'success' => true,
            'publication' => $publication
        ]);
    }
    
    // ========================================
    // SUPPRESSION
    // ========================================
    
    public function delete($id) {
        header('Content-Type: application/json');
        
        try {
            $publication = $this->publicationModel->getById($id);
            
            if (!$publication) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Publication introuvable'
                ]);
                return;
            }
            
            // Supprimer les auteurs associés
            $stmt = $this->publicationModel->getConnection()->prepare("DELETE FROM Publication_Auteur WHERE publication_id = ?");
            $stmt->execute([$id]);
            
            // Supprimer la publication
            $this->publicationModel->delete($id);
            
            echo json_encode([
                'success' => true,
                'message' => 'Publication supprimée avec succès'
            ]);
            
        } catch (Exception $e) {
            error_log("Erreur delete publication: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Erreur lors de la suppression'
            ]);
        }
    }
    
    // ========================================
    // VALIDATION DES PUBLICATIONS
    // ========================================
     public function valider($id) {
    error_log("Validation demandée pour publication ID: $id");
    header('Content-Type: application/json');
    
    try {
        $result = $this->publicationModel->update($id, ['statut_validation' => 'valide']);
        error_log("Résultat update: " . ($result ? "success" : "failed"));
        
        echo json_encode([
            'success' => true,
            'message' => 'Publication validée'
        ]);
    } catch (Exception $e) {
        error_log("Erreur validation: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Erreur lors de la validation: ' . $e->getMessage()
        ]);
    }
}

public function rejeter($id) {
    error_log("Rejet demandé pour publication ID: $id");
    header('Content-Type: application/json');
    
    try {
        $result = $this->publicationModel->update($id, ['statut_validation' => 'rejete']);
        error_log("Résultat update: " . ($result ? "success" : "failed"));
        
        echo json_encode([
            'success' => true,
            'message' => 'Publication rejetée'
        ]);
    } catch (Exception $e) {
        error_log("Erreur rejet: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Erreur lors du rejet: ' . $e->getMessage()
        ]);
    }
}
    // ========================================
    // RAPPORTS BIBLIOGRAPHIQUES
    // ========================================
    
    public function rapport() {
        $type = $_GET['type'] ?? 'annee';
        $format = $_GET['format'] ?? 'html';
        $annee = $_GET['annee'] ?? null;
        $auteur = $_GET['auteur'] ?? null;
        
        if ($type === 'annee') {
            $data = $this->genererRapportAnnuel($annee);
        } elseif ($type === 'auteur') {
            $data = $this->genererRapportAuteur($auteur);
        } else {
            $data = $this->genererRapportComplet();
        }
        
        if ($format === 'pdf') {
            $this->exporterRapportPDF($data, $type);
        } elseif ($format === 'csv') {
            $this->exporterRapportCSV($data);
        } else {
            require_once __DIR__ . '/../../views/admin/publications/rapport-publications.php';
        }
    }
    
    private function genererRapportAnnuel($annee = null) {
        if (!$annee) {
            $annee = date('Y');
        }
        
        $publications = $this->publicationModel->getAllFiltered(['annee' => $annee]);
        
        // Enrichir avec les auteurs
        foreach ($publications as &$pub) {
            $auteurs = $this->publicationModel->getAuteurs($pub['id']);
            $pub['auteurs_noms'] = implode(', ', array_column($auteurs, 'username'));
        }
        
        $rapport = [
            'titre' => "Rapport bibliographique $annee",
            'annee' => $annee,
            'total' => count($publications),
            'par_type' => [],
            'par_domaine' => [],
            'publications' => $publications
        ];
        
        // Regrouper par type
        foreach ($publications as $pub) {
            $type = $pub['type_publication'];
            if (!isset($rapport['par_type'][$type])) {
                $rapport['par_type'][$type] = 0;
            }
            $rapport['par_type'][$type]++;
            
            // Par domaine
            if ($pub['domaine']) {
                $domaine = $pub['domaine'];
                if (!isset($rapport['par_domaine'][$domaine])) {
                    $rapport['par_domaine'][$domaine] = 0;
                }
                $rapport['par_domaine'][$domaine]++;
            }
        }
        
        return $rapport;
    }
    
    private function genererRapportAuteur($membreId = null) {
        if (!$membreId) {
            return ['error' => 'Membre non spécifié'];
        }
        
        $membre = $this->membreModel->getById($membreId);
        $publications = $this->publicationModel->getByAuteur($membreId);
        
        // Enrichir avec les auteurs
        foreach ($publications as &$pub) {
            $auteurs = $this->publicationModel->getAuteurs($pub['id']);
            $pub['auteurs_noms'] = implode(', ', array_column($auteurs, 'username'));
        }
        
        // Récupérer le username du membre
        if ($membre) {
            $db = $this->publicationModel->getConnection();
            $userStmt = $db->prepare("SELECT username FROM User WHERE id = ?");
            $userStmt->execute([$membre['user_id']]);
            $user = $userStmt->fetch();
            $membre['username'] = $user['username'] ?? 'Inconnu';
        }
        
        $rapport = [
            'titre' => "Publications de " . ($membre['username'] ?? 'Inconnu'),
            'membre' => $membre,
            'total' => count($publications),
            'par_annee' => [],
            'par_type' => [],
            'publications' => $publications
        ];
        
        foreach ($publications as $pub) {
            $annee = date('Y', strtotime($pub['date_publication']));
            if (!isset($rapport['par_annee'][$annee])) {
                $rapport['par_annee'][$annee] = 0;
            }
            $rapport['par_annee'][$annee]++;
            
            $type = $pub['type_publication'];
            if (!isset($rapport['par_type'][$type])) {
                $rapport['par_type'][$type] = 0;
            }
            $rapport['par_type'][$type]++;
        }
        
        return $rapport;
    }
    
    private function genererRapportComplet() {
        $stats = $this->publicationModel->getStats();
        $publications = $this->publicationModel->getAll();
        
        // Enrichir avec les auteurs
        foreach ($publications as &$pub) {
            $auteurs = $this->publicationModel->getAuteurs($pub['id']);
            $pub['auteurs_noms'] = implode(', ', array_column($auteurs, 'username'));
        }
        
        return [
            'titre' => 'Rapport bibliographique complet',
            'stats' => $stats,
            'publications' => $publications
        ];
    }
    
    private function exporterRapportPDF($data, $type) {
        // Pour l'instant, rediriger vers HTML avec suggestion d'impression
        // Une vraie implémentation nécessiterait TCPDF ou DOMPDF
        
        header('Content-Type: text/html; charset=utf-8');
        
        echo '<!DOCTYPE html>';
        echo '<html><head>';
        echo '<meta charset="UTF-8">';
        echo '<title>' . htmlspecialchars($data['titre']) . '</title>';
        echo '<style>';
        echo 'body { font-family: Arial, sans-serif; margin: 40px; }';
        echo 'h1 { color: #1F2937; border-bottom: 3px solid #3B82F6; padding-bottom: 10px; }';
        echo 'h2 { color: #3B82F6; margin-top: 30px; }';
        echo '.publication { margin: 20px 0; padding: 15px; background: #F9FAFB; border-left: 4px solid #3B82F6; }';
        echo '.meta { color: #6B7280; font-size: 14px; }';
        echo '@media print { body { margin: 20px; } }';
        echo '</style>';
        echo '<script>window.onload = function() { window.print(); }</script>';
        echo '</head><body>';
        
        echo '<h1>' . htmlspecialchars($data['titre']) . '</h1>';
        echo '<p class="meta">Généré le ' . date('d/m/Y à H:i') . '</p>';
        
        if (!empty($data['publications'])) {
            echo '<h2>Publications (' . count($data['publications']) . ')</h2>';
            
            foreach ($data['publications'] as $pub) {
                echo '<div class="publication">';
                echo '<strong>' . htmlspecialchars($pub['titre']) . '</strong><br>';
                echo '<span class="meta">';
                echo htmlspecialchars($pub['type_publication']) . ' - ';
                echo date('Y', strtotime($pub['date_publication']));
                if (!empty($pub['auteurs_noms'])) {
                    echo ' - ' . htmlspecialchars($pub['auteurs_noms']);
                }
                echo '</span>';
                if (!empty($pub['resume'])) {
                    echo '<p>' . nl2br(htmlspecialchars($pub['resume'])) . '</p>';
                }
                echo '</div>';
            }
        }
        
        echo '</body></html>';
        exit;
    }
    
    private function exporterRapportCSV($data) {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="rapport_publications_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM
        
        // En-têtes
        fputcsv($output, ['Titre', 'Type', 'Date', 'Auteurs', 'DOI', 'Domaine']);
        
        // Données
        foreach ($data['publications'] as $pub) {
            fputcsv($output, [
                $pub['titre'],
                $pub['type_publication'],
                $pub['date_publication'],
                $pub['auteurs_noms'] ?? '',
                $pub['doi'] ?? '',
                $pub['domaine'] ?? ''
            ]);
        }
        
        fclose($output);
        exit;
    }
    
    // ========================================
    // EXPORT
    // ========================================
    
    public function export() {
        $filters = [
            'type_publication' => $_GET['type'] ?? null,
            'domaine' => $_GET['domaine'] ?? null,
            'annee' => $_GET['annee'] ?? null,
            'search' => $_GET['search'] ?? null
        ];
        
        $publications = $this->publicationModel->getAllFiltered($filters);
        
        foreach ($publications as &$pub) {
            $auteurs = $this->publicationModel->getAuteurs($pub['id']);
            $pub['auteurs_noms'] = implode(', ', array_column($auteurs, 'username'));
        }
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="publications_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        fputcsv($output, ['ID', 'Titre', 'Type', 'Date', 'Auteurs', 'DOI', 'Domaine', 'Statut']);
        
        foreach ($publications as $pub) {
            fputcsv($output, [
                $pub['id'],
                $pub['titre'],
                $pub['type_publication'],
                $pub['date_publication'],
                $pub['auteurs_noms'],
                $pub['doi'] ?? '',
                $pub['domaine'] ?? '',
                $pub['statut_validation'] ?? 'en_attente'
            ]);
        }
        
        fclose($output);
        exit;
    }
}