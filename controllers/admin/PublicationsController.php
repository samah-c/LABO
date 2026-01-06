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

require_once __DIR__ . '/../../views/admin/publications/PublicationsListView.php';
require_once __DIR__ . '/../../views/admin/publications/PublicationDetailView.php';

class PublicationsController {
    private $publicationModel;
    private $membreModel;
    private $projetModel;
    
    public function __construct() {
        $this->publicationModel = new PublicationModel();
        $this->membreModel = new MembreModel();
        $this->projetModel = new ProjetModel();
    }
    
    public function index() {
        $filters = [
            'type_publication' => $_GET['type'] ?? null,
            'domaine' => $_GET['domaine'] ?? null,
            'annee' => $_GET['annee'] ?? null,
            'projet_id' => $_GET['projet_id'] ?? null,
            'search' => $_GET['search'] ?? null,
            'statut' => $_GET['statut'] ?? null
        ];
        
        $publications = $this->publicationModel->getAllFiltered($filters);
        
        foreach ($publications as &$pub) {
            $auteurs = $this->publicationModel->getAuteurs($pub['id']);
            $pub['auteurs_noms'] = implode(', ', array_column($auteurs, 'username'));
        }
        
        $types = $this->publicationModel->getTypes();
        $domaines = $this->publicationModel->getDomaines();
        $annees = $this->publicationModel->getAnnees();
        $projets = $this->publicationModel->getProjets();
        
        $perPage = 10;
        $page = $_GET['page'] ?? 1;
        $pagination = Utils::paginate(count($publications), $perPage, $page);
        
        $view = new PublicationsListView(
            $publications,
            $types,
            $annees,
            $domaines,
            $projets,
            $pagination
        );
        $view->render();
    }
    
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
        
        $projets = $this->projetModel->getAll();
        $membres = $this->membreModel->getAllMembresWithUser();
        
        $this->renderForm($publication, $auteurs, $projets, $membres);
    }
    
    private function renderForm($publication, $auteurs, $projets, $membres) {
        $isEdit = !empty($publication);
        $typeActuel = $publication['type_publication'] ?? '';
        $domaineActuel = $publication['domaine'] ?? '';
        $statutActuel = $publication['statut_validation'] ?? 'en_attente';
        ?>
        <form id="publication-form" action="<?= base_url('admin/publications/publications/save') ?>" method="POST">
            <?php if ($isEdit): ?>
                <input type="hidden" name="id" value="<?= $publication['id'] ?>">
            <?php endif; ?>
            
            <div class="form-group">
                <label for="titre">Titre *</label>
                <input type="text" 
                       id="titre" 
                       name="titre" 
                       value="<?= e($publication['titre'] ?? '') ?>" 
                       required 
                       placeholder="Titre de la publication">
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="type_publication">Type *</label>
                    <select id="type_publication" name="type_publication" required>
                        <option value="">-- Sélectionner --</option>
                        <option value="Article" <?= $typeActuel === 'Article' ? 'selected' : '' ?>>Article</option>
                        <option value="Conférence" <?= $typeActuel === 'Conférence' ? 'selected' : '' ?>>Conférence</option>
                        <option value="Thèse" <?= $typeActuel === 'Thèse' ? 'selected' : '' ?>>Thèse</option>
                        <option value="Rapport" <?= $typeActuel === 'Rapport' ? 'selected' : '' ?>>Rapport</option>
                        <option value="Livre" <?= $typeActuel === 'Livre' ? 'selected' : '' ?>>Livre</option>
                        <option value="Chapitre" <?= $typeActuel === 'Chapitre' ? 'selected' : '' ?>>Chapitre</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="domaine">Domaine</label>
                    <select id="domaine" name="domaine">
                        <option value="">-- Sélectionner --</option>
                        <option value="IA" <?= $domaineActuel === 'IA' ? 'selected' : '' ?>>Intelligence Artificielle</option>
                        <option value="Sécurité" <?= $domaineActuel === 'Sécurité' ? 'selected' : '' ?>>Sécurité</option>
                        <option value="Réseaux" <?= $domaineActuel === 'Réseaux' ? 'selected' : '' ?>>Réseaux</option>
                        <option value="Blockchain" <?= $domaineActuel === 'Blockchain' ? 'selected' : '' ?>>Blockchain</option>
                        <option value="IoT" <?= $domaineActuel === 'IoT' ? 'selected' : '' ?>>IoT</option>
                        <option value="Big Data" <?= $domaineActuel === 'Big Data' ? 'selected' : '' ?>>Big Data</option>
                    </select>
                </div>
            </div>
            
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
            
            <div class="form-group">
                <label for="resume">Résumé *</label>
                <textarea id="resume" 
                          name="resume" 
                          rows="4" 
                          required 
                          placeholder="Résumé de la publication"><?= e($publication['resume'] ?? '') ?></textarea>
            </div>
            
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
            
            <div class="form-group">
                <label for="statut_validation">Statut de validation *</label>
                <select id="statut_validation" name="statut_validation" required>
                    <option value="en_attente" <?= $statutActuel === 'en_attente' ? 'selected' : '' ?>>En attente</option>
                    <option value="valide" <?= $statutActuel === 'valide' ? 'selected' : '' ?>>Validé</option>
                    <option value="rejete" <?= $statutActuel === 'rejete' ? 'selected' : '' ?>>Rejeté</option>
                </select>
                <small>Les publications validées sont visibles publiquement</small>
            </div>
            
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
                    <button type="button" class="btn-icon btn-remove" onclick="removeAuteur(this)">🗑️</button>
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
            
            const form = document.getElementById('publication-form');
            if (form) {
                const newForm = form.cloneNode(true);
                form.parentNode.replaceChild(newForm, form);
                
                newForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    const handler = window.publications || window.parent.publications;
                    
                    if (handler && typeof handler.submitForm === 'function') {
                        handler.submitForm(this);
                    } else {
                        console.error('Handler publications introuvable');
                        alert('Erreur: Handler non initialisé. Veuillez recharger la page.');
                    }
                    
                    return false;
                });
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
            
            if ($id) {
                $this->publicationModel->update($id, $data);
                $publicationId = $id;
                $message = 'Publication modifiée avec succès';
            } else {
                $publicationId = $this->publicationModel->create($data);
                $message = 'Publication créée avec succès';
            }
            
            if (isset($_POST['auteurs']) && is_array($_POST['auteurs'])) {
                $stmt = $this->publicationModel->getConnection()->prepare("DELETE FROM Publication_Auteur WHERE publication_id = ?");
                $stmt->execute([$publicationId]);
                
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
    
    public function view($id) {
        $publication = $this->publicationModel->getById($id);
        
        if (!$publication) {
            $_SESSION['error'] = 'Publication introuvable';
            redirect(base_url('admin/publications/publications'));
            return;
        }
        
        $auteurs = $this->publicationModel->getAuteurs($id);
        
        $projet = null;
        if ($publication['projet_id']) {
            $projet = $this->projetModel->getById($publication['projet_id']);
        }
        
        $view = new PublicationDetailView($publication, $auteurs, $projet);
        $view->render();
    }
    
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
        
        $auteurs = $this->publicationModel->getAuteurs($id);
        $publication['auteurs'] = $auteurs;
        
        echo json_encode([
            'success' => true,
            'publication' => $publication
        ]);
    }
    
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
            
            $stmt = $this->publicationModel->getConnection()->prepare("DELETE FROM Publication_Auteur WHERE publication_id = ?");
            $stmt->execute([$id]);
            
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
    
  
    
    public function valider($id) {
        header('Content-Type: application/json');
        error_log("Validation demandée pour publication ID: $id");
        
        try {
            $result = $this->publicationModel->update($id, ['statut_validation' => 'valide']);
            error_log("Résultat update: " . ($result ? "success" : "failed"));
            
            echo json_encode([
                'success' => true,
                'message' => 'Publication validée avec succès'
            ]);
        } catch (Exception $e) {
            error_log("Erreur validation: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Erreur lors de la validation: ' . $e->getMessage()
            ]);
        }
        
        exit; 
    }

    public function rejeter($id) {
        header('Content-Type: application/json');
        error_log("Rejet demandé pour publication ID: $id");
        
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
        
        exit; // ← LIGNE CRITIQUE
    }
    
    // Reste du code (rapport, export, etc.)
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
        
        if (class_exists('PublicationsPdfExportView')) {
            $pdfExport = new PublicationsPdfExportView($data, $type);
            
            if ($format === 'pdf') {
                $pdfExport->generate();
            } elseif ($format === 'csv') {
                $this->exporterRapportCSV($data);
            } else {
                $pdfExport->renderHtmlPreview();
            }
        }
    }
    
    private function genererRapportAnnuel($annee = null) {
        if (!$annee) $annee = date('Y');
        
        $publications = $this->publicationModel->getAllFiltered(['annee' => $annee]);
        
        foreach ($publications as &$pub) {
            $auteurs = $this->publicationModel->getAuteurs($pub['id']);
            $pub['auteurs_noms'] = implode(', ', array_column($auteurs, 'username'));
        }
        
        return [
            'titre' => "Rapport bibliographique $annee",
            'annee' => $annee,
            'total' => count($publications),
            'publications' => $publications
        ];
    }
    
    private function genererRapportAuteur($membreId = null) {
        if (!$membreId) return ['error' => 'Membre non spécifié'];
        
        $membre = $this->membreModel->getById($membreId);
        $publications = $this->publicationModel->getByAuteur($membreId);
        
        return [
            'titre' => "Publications de " . ($membre['username'] ?? 'Inconnu'),
            'membre' => $membre,
            'total' => count($publications),
            'publications' => $publications
        ];
    }
    
    private function genererRapportComplet() {
        $stats = $this->publicationModel->getStats();
        $publications = $this->publicationModel->getAll();
        
        return [
            'titre' => 'Rapport bibliographique complet',
            'stats' => $stats,
            'publications' => $publications
        ];
    }
    
    private function exporterRapportCSV($data) {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="rapport_publications_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        fputcsv($output, ['Titre', 'Type', 'Date', 'Auteurs', 'DOI', 'Domaine']);
        
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
?>