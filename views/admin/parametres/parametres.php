<?php
/**
 * Vue paramètres généraux (admin)
 */

require_once __DIR__ . '/../../../lib/helpers.php';
require_once __DIR__ . '/../../../lib/ViewComponents.php';

ViewComponents::renderHeader([
    'title' => 'Paramètres',
    'username' => session('username'),
    'role' => 'admin',
    'additionalJs' => [base_url('assets/js/parametres.js')]
]);
?>

<div class="container">
    <?php ViewComponents::renderBreadcrumbs([
        ['label' => 'Dashboard', 'url' => base_url('admin/dashboard')],
        ['label' => 'Paramètres']
    ]); ?>
    
    <div class="page-header">
        <h1> Paramètres du Système</h1>
    </div>
    
    <div class="settings-container">
        <!-- Informations générales -->
        <div class="settings-section">
            <h2>Informations du Laboratoire</h2>
            <form action="<?= base_url('admin/parametres/save-general') ?>" method="POST" enctype="multipart/form-data">
                <?= csrf_field() ?>
                
                <div class="form-group">
                    <label for="lab_name">Nom du laboratoire</label>
                    <input type="text" name="lab_name" id="lab_name" 
                           value="<?= e($settings['lab_name'] ?? 'Laboratoire TDW') ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="lab_description">Description</label>
                    <textarea name="lab_description" id="lab_description" rows="4"><?= e($settings['lab_description'] ?? '') ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="lab_email">Email de contact</label>
                    <input type="email" name="lab_email" id="lab_email" 
                           value="<?= e($settings['lab_email'] ?? '') ?>">
                </div>
                
                <div class="form-group">
                    <label for="lab_phone">Téléphone</label>
                    <input type="tel" name="lab_phone" id="lab_phone" 
                           value="<?= e($settings['lab_phone'] ?? '') ?>">
                </div>
                
                <div class="form-group">
                    <label for="lab_address">Adresse</label>
                    <textarea name="lab_address" id="lab_address" rows="3"><?= e($settings['lab_address'] ?? '') ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="logo">Logo du laboratoire</label>
                    <input type="file" name="logo" id="logo" accept="image/*">
                    <?php if (!empty($settings['lab_logo'])): ?>
                        <div class="current-logo">
                            <img src="<?= base_url('uploads/' . $settings['lab_logo']) ?>" 
                                 alt="Logo actuel" style="max-width: 200px; margin-top: 10px;">
                        </div>
                    <?php endif; ?>
                </div>
                
                <button type="submit" class="btn-primary"> Enregistrer</button>
            </form>
        </div>
        
        <!-- Réseaux sociaux -->
        <div class="settings-section">
            <h2>Réseaux Sociaux</h2>
            <form action="<?= base_url('admin/parametres/save-social') ?>" method="POST">
                <?= csrf_field() ?>
                
                <div class="form-group">
                    <label for="facebook_url">Facebook</label>
                    <input type="url" name="facebook_url" id="facebook_url" 
                           value="<?= e($settings['facebook_url'] ?? '') ?>"
                           placeholder="https://facebook.com/...">
                </div>
                
                <div class="form-group">
                    <label for="twitter_url">Twitter / X</label>
                    <input type="url" name="twitter_url" id="twitter_url" 
                           value="<?= e($settings['twitter_url'] ?? '') ?>"
                           placeholder="https://twitter.com/...">
                </div>
                
                <div class="form-group">
                    <label for="linkedin_url">LinkedIn</label>
                    <input type="url" name="linkedin_url" id="linkedin_url" 
                           value="<?= e($settings['linkedin_url'] ?? '') ?>"
                           placeholder="https://linkedin.com/company/...">
                </div>
                
                <div class="form-group">
                    <label for="website_url">Site web officiel</label>
                    <input type="url" name="website_url" id="website_url" 
                           value="<?= e($settings['website_url'] ?? '') ?>"
                           placeholder="https://...">
                </div>
                
                <button type="submit" class="btn-primary"> Enregistrer</button>
            </form>
        </div>
        
        <!-- Thème et apparence -->
        <div class="settings-section">
            <h2>Apparence</h2>
            <form action="<?= base_url('admin/parametres/save-theme') ?>" method="POST">
                <?= csrf_field() ?>
                
                <div class="form-group">
                    <label for="primary_color">Couleur principale</label>
                    <input type="color" name="primary_color" id="primary_color" 
                           value="<?= e($settings['primary_color'] ?? '#2563eb') ?>">
                </div>
                
                <div class="form-group">
                    <label for="secondary_color">Couleur secondaire</label>
                    <input type="color" name="secondary_color" id="secondary_color" 
                           value="<?= e($settings['secondary_color'] ?? '#64748b') ?>">
                </div>
                
                <div class="form-group">
                    <label for="theme_mode">Mode</label>
                    <select name="theme_mode" id="theme_mode">
                        <option value="light" <?= ($settings['theme_mode'] ?? 'light') === 'light' ? 'selected' : '' ?>>Clair</option>
                        <option value="dark" <?= ($settings['theme_mode'] ?? 'light') === 'dark' ? 'selected' : '' ?>>Sombre</option>
                    </select>
                </div>
                
                <button type="submit" class="btn-primary"> Enregistrer</button>
            </form>
        </div>
        
        <!-- Base de données -->
        <div class="settings-section">
            <h2> Base de Données</h2>
            
            <div class="db-actions">
                <button class="btn-primary" onclick="backupDatabase()">
                     Sauvegarder la base de données
                </button>
                
                <button class="btn-secondary" onclick="showRestoreModal()">
                     Restaurer une sauvegarde
                </button>
                
                <button class="btn-danger" onclick="clearCache()">
                     Vider le cache
                </button>
            </div>
            
            <div class="db-info">
                <h3>Dernières sauvegardes</h3>
                <ul class="backup-list">
                    <?php 
                    $backups = $backups ?? [];
                    if (empty($backups)): 
                    ?>
                        <li>Aucune sauvegarde disponible</li>
                    <?php else: ?>
                        <?php foreach ($backups as $backup): ?>
                            <li>
                                <span><?= e($backup['filename']) ?></span>
                                <span><?= format_date($backup['date']) ?></span>
                                <span><?= Utils::formatFileSize($backup['size']) ?></span>
                                <button class="btn-small" onclick="downloadBackup('<?= e($backup['filename']) ?>')">
                                     Télécharger
                                </button>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
        
        <!-- Maintenance -->
        <div class="settings-section">
            <h2>Maintenance</h2>
            
            <div class="form-group">
                <label>
                    <input type="checkbox" id="maintenance_mode" 
                           <?= !empty($settings['maintenance_mode']) ? 'checked' : '' ?>>
                    Activer le mode maintenance
                </label>
                <p class="help-text">Le site sera inaccessible aux visiteurs (sauf administrateurs)</p>
            </div>
            
            <div class="form-group">
                <label for="maintenance_message">Message de maintenance</label>
                <textarea id="maintenance_message" rows="3"><?= e($settings['maintenance_message'] ?? 'Site en maintenance, revenez bientôt.') ?></textarea>
            </div>
            
            <button class="btn-primary" onclick="saveMaintenanceSettings()">
                Enregistrer
            </button>
        </div>
    </div>
</div>

<!-- Modale de restauration -->
<?php ViewComponents::renderModal([
    'id' => 'restore-modal',
    'title' => 'Restaurer une sauvegarde',
    'content' => '
        <form id="restore-form" enctype="multipart/form-data">
            <div class="form-group">
                <label>Fichier de sauvegarde (.sql)</label>
                <input type="file" name="backup_file" accept=".sql" required>
            </div>
            <div class="warning-box">
                 <strong>Attention :</strong> Cette action remplacera toutes les données actuelles.
            </div>
        </form>
    ',
    'footer' => '
        <button class="btn-secondary" onclick="closeRestoreModal()">Annuler</button>
        <button class="btn-danger" onclick="restoreDatabase()">Restaurer</button>
    '
]); ?>

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
            alert('  Erreur : ' + data.message);
        }
    })
    .catch(error => {
        alert('  Erreur lors de la sauvegarde');
        console.error(error);
    })
    .finally(() => {
        btn.disabled = false;
        btn.textContent = ' Sauvegarder la base de données';
    });
}

function showRestoreModal() {
    document.getElementById('restore-modal').style.display = 'block';
}

function closeRestoreModal() {
    document.getElementById('restore-modal').style.display = 'none';
}

function restoreDatabase() {
    if (!confirm(' ATTENTION : Cela remplacera TOUTES les données actuelles. Continuer ?')) return;
    
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
            alert('  Erreur : ' + data.message);
        }
    })
    .catch(error => {
        alert('  Erreur lors de la restauration');
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
            alert('  Erreur');
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
            alert('  Paramètres de maintenance enregistrés');
        } else {
            alert('  Erreur');
        }
    });
}

// Afficher les messages de succès
<?php if (session('success')): ?>
    alert('<?= e(session('success')) ?>');
<?php endif; ?>
</script>

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
</style>

<?php ViewComponents::renderFooter(); ?>