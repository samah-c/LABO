<?php
/**
 * Vue de la page des paramètres système
 */
require_once __DIR__ . '/../../../lib/components/HeaderComponent.php';
require_once __DIR__ . '/../../../lib/components/NavigationComponent.php';
require_once __DIR__ . '/../../../lib/components/PageHeaderComponent.php';
require_once __DIR__ . '/../../../lib/components/FormComponent.php';
require_once __DIR__ . '/../../../lib/components/ModalComponent.php';
require_once __DIR__ . '/../../../lib/components/FooterComponent.php';

class ParametresView
{
    private array $settings;
    private array $backups;

    public function __construct(array $settings, array $backups = [])
    {
        $this->settings = $settings;
        $this->backups = $backups;
    }

    /**
     * Rendu complet de la vue
     */
    public function render(): void
    {
        $this->renderHeader();
        $this->renderNavigation();
        echo '<div class="container">';
        $this->renderBreadcrumbs();
        $this->renderPageHeader();
        echo '<div class="settings-container">';
        $this->renderLabInfoSection();
        $this->renderSocialSection();
        $this->renderDatabaseSection();
        $this->renderMaintenanceSection();
        echo '</div>';
        echo '</div>';
        $this->renderRestoreModal();
        $this->renderScripts();
        $this->renderStyles();
        $this->renderFooter();
    }

    /**
     * Rendu de l'en-tête
     */
    private function renderHeader(): void
    {
        HeaderComponent::render([
            'title' => 'Paramètres',
            'username' => session('username'),
            'role' => 'admin',
            'additionalJs' => [base_url('assets/js/parametres.js')]
        ]);
    }

    /**
     * Rendu de la navigation
     */
    private function renderNavigation(): void
    {
        NavigationComponent::renderSidebar('admin');
    }

    /**
     * Rendu du fil d'Ariane
     */
    private function renderBreadcrumbs(): void
    {
        NavigationComponent::renderBreadcrumbs([
            ['label' => 'Dashboard', 'url' => base_url('admin/dashboard')],
            ['label' => 'Paramètres']
        ]);
    }

    /**
     * Rendu de l'en-tête de page
     */
    private function renderPageHeader(): void
    {
        PageHeaderComponent::render([
            'title' => 'Paramètres du Système',
            'subtitle' => 'Configuration générale du laboratoire'
        ]);
    }

    /**
     * Rendu de la section informations du laboratoire
     */
    private function renderLabInfoSection(): void
    {
        ?>
        <div class="settings-section">
            <h2>Informations du Laboratoire</h2>
            <?php
            FormComponent::render([
                'action' => base_url('admin/parametres/save-general'),
                'method' => 'POST',
                'enctype' => 'multipart/form-data',
                'fields' => [
                    [
                        'type' => 'text',
                        'name' => 'lab_name',
                        'label' => 'Nom du laboratoire',
                        'value' => $this->settings['lab_name'] ?? 'Laboratoire TDW',
                        'required' => true
                    ],
                    [
                        'type' => 'textarea',
                        'name' => 'lab_description',
                        'label' => 'Description',
                        'value' => $this->settings['lab_description'] ?? '',
                        'attributes' => ['rows' => 4]
                    ],
                    [
                        'type' => 'email',
                        'name' => 'lab_email',
                        'label' => 'Email de contact',
                        'value' => $this->settings['lab_email'] ?? ''
                    ],
                    [
                        'type' => 'tel',
                        'name' => 'lab_phone',
                        'label' => 'Téléphone',
                        'value' => $this->settings['lab_phone'] ?? ''
                    ],
                    [
                        'type' => 'textarea',
                        'name' => 'lab_address',
                        'label' => 'Adresse',
                        'value' => $this->settings['lab_address'] ?? '',
                        'attributes' => ['rows' => 3]
                    ],
                    [
                        'type' => 'file',
                        'name' => 'logo',
                        'label' => 'Logo du laboratoire',
                        'attributes' => ['accept' => 'image/*']
                    ]
                ],
                'submitText' => 'Enregistrer'
            ]);
            
            if (!empty($this->settings['lab_logo'])): ?>
                <div class="current-logo">
                    <img src="<?= base_url('uploads/' . $this->settings['lab_logo']) ?>" 
                         alt="Logo actuel" style="max-width: 200px; margin-top: 10px;">
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Rendu de la section réseaux sociaux
     */
    private function renderSocialSection(): void
    {
        ?>
        <div class="settings-section">
            <h2>Réseaux Sociaux</h2>
            <?php
            FormComponent::render([
                'action' => base_url('admin/parametres/save-social'),
                'method' => 'POST',
                'fields' => [
                    [
                        'type' => 'url',
                        'name' => 'facebook_url',
                        'label' => 'Facebook',
                        'value' => $this->settings['facebook_url'] ?? '',
                        'placeholder' => 'https://facebook.com/...'
                    ],
                    [
                        'type' => 'url',
                        'name' => 'twitter_url',
                        'label' => 'Twitter / X',
                        'value' => $this->settings['twitter_url'] ?? '',
                        'placeholder' => 'https://twitter.com/...'
                    ],
                    [
                        'type' => 'url',
                        'name' => 'linkedin_url',
                        'label' => 'LinkedIn',
                        'value' => $this->settings['linkedin_url'] ?? '',
                        'placeholder' => 'https://linkedin.com/company/...'
                    ],
                    [
                        'type' => 'url',
                        'name' => 'website_url',
                        'label' => 'Site web officiel',
                        'value' => $this->settings['website_url'] ?? '',
                        'placeholder' => 'https://...'
                    ]
                ],
                'submitText' => 'Enregistrer'
            ]);
            ?>
        </div>
        <?php
    }

    /**
     * Rendu de la section base de données
     */
    private function renderDatabaseSection(): void
    {
        ?>
        <div class="settings-section">
            <h2> Base de Données</h2>
            
            <div class="db-actions">
                <button class="btn-primary" onclick="backupDatabase()">
                     Sauvegarder la base de données
                </button>
                
                <button class="btn-secondary" onclick="openModal('restore-modal')">
                     Restaurer une sauvegarde
                </button>
                
                <button class="btn-danger" onclick="clearCache()">
                     Vider le cache
                </button>
            </div>
            
            <div class="db-info">
                <h3>Dernières sauvegardes</h3>
                <ul class="backup-list">
                    <?php if (empty($this->backups)): ?>
                        <li>Aucune sauvegarde disponible</li>
                    <?php else: ?>
                        <?php foreach ($this->backups as $backup): ?>
                            <li>
                                <span><?= htmlspecialchars($backup['filename']) ?></span>
                                <span><?= format_date($backup['date']) ?></span>
                                <span><?= $this->formatFileSize($backup['size']) ?></span>
                                <button class="btn-small" onclick="downloadBackup('<?= htmlspecialchars($backup['filename']) ?>')">
                                    ⬇Télécharger
                                </button>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
        <?php
    }

    /**
     * Rendu de la section maintenance
     */
    private function renderMaintenanceSection(): void
    {
        ?>
        <div class="settings-section">
            <h2> Maintenance</h2>
            
            <div class="form-group">
                <label>
                    <input type="checkbox" id="maintenance_mode" 
                           <?= !empty($this->settings['maintenance_mode']) ? 'checked' : '' ?>>
                    Activer le mode maintenance
                </label>
                <p class="help-text">Le site sera inaccessible aux visiteurs (sauf administrateurs)</p>
            </div>
            
            <div class="form-group">
                <label for="maintenance_message">Message de maintenance</label>
                <textarea id="maintenance_message" rows="3"><?= htmlspecialchars($this->settings['maintenance_message'] ?? 'Site en maintenance, revenez bientôt.') ?></textarea>
            </div>
            
            <button class="btn-primary" onclick="saveMaintenanceSettings()">
                 Enregistrer
            </button>
        </div>
        <?php
    }

    /**
     * Rendu de la modale de restauration
     */
    private function renderRestoreModal(): void
    {
        ModalComponent::render([
            'id' => 'restore-modal',
            'title' => 'Restaurer une sauvegarde',
            'content' => '
                <form id="restore-form" enctype="multipart/form-data">
                    <div class="form-group">
                        <label>Fichier de sauvegarde (.sql)</label>
                        <input type="file" name="backup_file" accept=".sql" required>
                    </div>
                    <div class="warning-box">
                        ⚠️ <strong>Attention :</strong> Cette action remplacera toutes les données actuelles.
                    </div>
                </form>
            ',
            'footer' => '
                <button class="btn-secondary" onclick="closeModal(\'restore-modal\')">Annuler</button>
                <button class="btn-danger" onclick="restoreDatabase()">Restaurer</button>
            '
        ]);
    }

    /**
     * Rendu des scripts JavaScript
     */
    private function renderScripts(): void
    {
        ?>
        <script>
        function backupDatabase() {
            if (!confirm('Créer une sauvegarde de la base de données ?')) return;
            
            const btn = event.target;
            btn.disabled = true;
            btn.textContent = ' Sauvegarde en cours...';
            
            fetch('<?= base_url("api/admin/database/backup") ?>', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert(' Sauvegarde créée avec succès');
                    location.reload();
                } else {
                    alert(' Erreur : ' + data.message);
                }
            })
            .catch(error => {
                alert(' Erreur lors de la sauvegarde');
                console.error(error);
            })
            .finally(() => {
                btn.disabled = false;
                btn.textContent = ' Sauvegarder la base de données';
            });
        }

        function restoreDatabase() {
            if (!confirm('⚠️ ATTENTION : Cela remplacera TOUTES les données actuelles. Continuer ?')) return;
            
            const form = document.getElementById('restore-form');
            const formData = new FormData(form);
            
            fetch('<?= base_url("api/admin/database/restore") ?>', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert(' Base de données restaurée avec succès');
                    location.reload();
                } else {
                    alert(' Erreur : ' + data.message);
                }
            })
            .catch(error => {
                alert(' Erreur lors de la restauration');
                console.error(error);
            });
        }

        function downloadBackup(filename) {
            window.location.href = '<?= base_url("admin/parametres/download-backup/") ?>' + filename;
        }

        function clearCache() {
            if (!confirm('Vider le cache ?')) return;
            
            fetch('<?= base_url("api/admin/cache/clear") ?>', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert(' Cache vidé avec succès');
                } else {
                    alert(' Erreur');
                }
            });
        }

        function saveMaintenanceSettings() {
            const mode = document.getElementById('maintenance_mode').checked;
            const message = document.getElementById('maintenance_message').value;
            
            fetch('<?= base_url("api/admin/maintenance/save") ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ mode, message })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert(' Paramètres de maintenance enregistrés');
                } else {
                    alert(' Erreur');
                }
            });
        }

        // Afficher les messages de succès
        <?php if (session('success')): ?>
            alert('<?= htmlspecialchars(session('success')) ?>');
        <?php endif; ?>
        </script>
        <?php
    }

    /**
     * Rendu des styles CSS
     */
    private function renderStyles(): void
    {
        ?>
        <style>
        .settings-container {
            display: grid;
            gap: 2rem;
        }

        .settings-section {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .settings-section h2 {
            margin-bottom: 1.5rem;
            color: #1e293b;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 0.5rem;
        }

        .db-actions {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }

        .backup-list {
            list-style: none;
            padding: 0;
        }

        .backup-list li {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            margin-bottom: 0.5rem;
        }

        .warning-box {
            background: #fef3c7;
            border: 1px solid #fbbf24;
            padding: 1rem;
            border-radius: 4px;
            margin-top: 1rem;
        }

        .help-text {
            font-size: 0.875rem;
            color: #64748b;
            margin-top: 0.25rem;
        }

        .btn-danger {
            background: #dc2626;
            color: white;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .btn-danger:hover {
            background: #b91c1c;
        }

        .btn-small {
            padding: 0.25rem 0.75rem;
            font-size: 0.875rem;
        }

        .current-logo {
            margin-top: 1rem;
            padding: 1rem;
            background: #f9fafb;
            border-radius: 4px;
        }
        </style>
        <?php
    }

    /**
     * Rendu du pied de page
     */
    private function renderFooter(): void
    {
        FooterComponent::render(['role' => 'admin']);
    }

    /**
     * Formater la taille d'un fichier
     */
    private function formatFileSize($bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}