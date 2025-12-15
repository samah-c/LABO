<?php
/**
 * Vue détaillée d'un projet
 * À placer dans : /TDW_project/views/admin/projets/view.php
 */

require_once __DIR__ . '/../../../lib/helpers.php';
require_once __DIR__ . '/../../../lib/ViewComponents.php';

ViewComponents::renderHeader([
    'title' => 'Détails du projet',
    'username' => session('username'),
    'role' => 'admin',
    'additionalJs' => [
        base_url('assets/js/ui.js'),
        base_url('assets/js/admin/projets-handler.js')
    ]
]);
?>

<div class="container">
    <?php ViewComponents::renderBreadcrumbs([
        ['label' => 'Dashboard', 'url' => base_url('admin/dashboard')],
        ['label' => 'Projets', 'url' => base_url('admin/projets/projets')],
        ['label' => e($projet['titre'] ?? 'Détails')]
    ]); ?>
    
    <!-- En-tête projet -->
    <div class="page-header">
        <div>
            <h1><?= e($projet['titre']) ?></h1>
            <p style="color: #6B7280; margin-top: 8px;">
                <?= e($projet['thematique']) ?> &bull; 
                <?= LabHelpers::getProjetStatusBadge($projet['status']) ?>
            </p>
        </div>
        <div class="page-actions">
            <button class="btn-secondary" onclick="editItem(<?= $projet['id'] ?>)">
                Modifier
            </button>
            <button class="btn-secondary" onclick="projets.genererRapport(<?= $projet['id'] ?>)">
                Rapport
            </button>
            <button class="btn-secondary" onclick="deleteItem(<?= $projet['id'] ?>)">
                Supprimer
            </button>
        </div>
    </div>
    
    <div class="grid-2-cols">
        <!-- Informations générales -->
        <div class="card">
            <div class="card-header">
                <h2> Informations générales</h2>
            </div>
            <div class="card-body">
                <div class="info-list">
                    <div class="info-item">
                        <span class="info-label">Titre</span>
                        <span class="info-value"><?= e($projet['titre']) ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Thématique</span>
                        <span class="info-value">
                            <?= e($projet['thematique']) ?>
                        </span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">statut</span>
                        <span class="info-value">
                         <?= LabHelpers::getProjetStatusBadge($projet['status'] ?? 'en_cours') ?>
                        </span>
                    </div>
                    <div class="info-item">
    <span class="info-label">Responsable</span>
    <span class="info-value">
        <?php if ($responsable): ?>
            <?php 
            // Get the user_id for the link
            $userId = $responsable['user_id'] ?? null;
            if (!$userId && !empty($responsable['id'])) {
                // If we only have membre_id, we need to fetch the user_id
                // For now, just display without link or use membre_id
                $userId = $responsable['id'];
            }
            ?>
            <?php if ($userId): ?>
                <a href="<?= base_url('admin/users/users/view/' . $userId) ?>" style="color: #5B7FFF; text-decoration: none;">
                    <?= e($responsable['username'] ?? $responsable['responsable_username'] ?? 'Non défini') ?>
                    <?php if (!empty($responsable['grade'])): ?>
                        - <?= e($responsable['grade']) ?>
                    <?php endif; ?>
                </a>
            <?php else: ?>
                <?= e($responsable['username'] ?? $responsable['responsable_username'] ?? 'Non défini') ?>
                <?php if (!empty($responsable['grade'])): ?>
                    - <?= e($responsable['grade']) ?>
                <?php endif; ?>
            <?php endif; ?>
        <?php else: ?>
            <em style="color: #9CA3AF;">Non assigné</em>
        <?php endif; ?>
    </span>
</div>
                    <div class="info-item">
                        <span class="info-label">Période</span>
                        <span class="info-value">
                            <?= format_date($projet['date_debut']) ?> → 
                            <?= $projet['date_fin'] ? format_date($projet['date_fin']) : 'Non définie' ?>
                        </span>
                    </div>
                    <?php if (!empty($projet['budget'])): ?>
                    <div class="info-item">
                        <span class="info-label">Budget</span>
                        <span class="info-value">
                            <?= number_format($projet['budget'], 2, ',', ' ') ?> DZD
                        </span>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($projet['source_financement'])): ?>
                    <div class="info-item">
                        <span class="info-label">Source de financement</span>
                        <span class="info-value"><?= e($projet['source_financement']) ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Statistiques -->
        <div class="card">
            <div class="card-header">
                <h2> Statistiques</h2>
            </div>
            <div class="card-body">
                <div class="stats-list">
                    <div class="stat-item">
                        <span class="stat-label">Membres</span>
                        <span class="stat-value"><?= $stats['nb_membres'] ?? 0 ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Publications</span>
                        <span class="stat-value"><?= $stats['nb_publications'] ?? 0 ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Progression</span>
                        <span class="stat-value" style="font-size: 18px;">
                            <?= $stats['progression'] ?? 0 ?>%
                        </span>
                    </div>
                </div>
                
                <div style="margin-top: 20px;">
                    <div class="progress" style="height: 12px;">
                        <div class="progress-bar" 
                             style="width: <?= $stats['progression'] ?? 0 ?>%; background: linear-gradient(90deg, #5B7FFF, #667eea);">
                        </div>
                    </div>
                    <p style="text-align: center; color: #6B7280; font-size: 12px; margin-top: 8px;">
                        Avancement du projet
                    </p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Description détaillée -->
    <div class="card" style="margin-top: 24px;">
        <div class="card-header">
            <h2> Description</h2>
        </div>
        <div class="card-body">
            <p style="line-height: 1.6; color: #374151;">
                <?= nl2br(e($projet['description'])) ?>
            </p>
        </div>
    </div>
    
    <!-- Membres du projet -->
    <div class="card" style="margin-top: 24px;">
        <div class="card-header">
            <h2>Membres du projet</h2>
            <button class="btn-sm btn-primary" style="width: fit-content;" onclick="projets.openAddMembreModal(<?= $projet['id'] ?>)">
                 Ajouter un membre
            </button>
        </div>
        <div class="card-body">
            <?php if (!empty($membres)): ?>
                <div class="membres-grid">
                    <?php foreach ($membres as $membre): ?>
                        <div class="membre-card">
                            <div class="membre-info">
                                <div class="membre-avatar">
                                    <?= strtoupper(substr($membre['username'], 0, 2)) ?>
                                </div>
                                <div class="membre-details">
                                    <strong><?= e($membre['username']) ?></strong>
                                    <?php if (!empty($membre['grade'])): ?>
                                        <span class="membre-grade"><?= e($membre['grade']) ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($membre['role'])): ?>
                                        <span class="badge badge-blue" style="font-size: 11px; margin-top: 4px;">
                                            <?= e($membre['role']) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php if ($membre['id'] != $projet['responsable_id']): ?>
                                <button class="btn-icon btn-remove" 
                                        onclick="projets.removeMembre(<?= $projet['id'] ?>, <?= $membre['id'] ?>, '<?= e($membre['username']) ?>')"
                                        title="Retirer du projet">
                                    ✕
                                </button>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p style="text-align: center; color: #9CA3AF; padding: 30px;">
                    Aucun membre assigné à ce projet
                </p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Publications liées -->
    <div class="card" style="margin-top: 24px;">
        <div class="card-header">
            <h2>Publications liées</h2>
        </div>
        <div class="card-body">
            <?php if (!empty($publications)): ?>
                <div class="publications-list">
                    <?php foreach ($publications as $pub): ?>
                        <div class="publication-item">
                            <div class="publication-header">
                                <a href="<?= base_url('admin/publications/publications/view/' . $pub['id']) ?>" 
                                   class="publication-title">
                                    <?= e($pub['titre']) ?>
                                </a>
                              <a href="<?= base_url('admin/publications/publications/view/' . $pub['id']) ?>" 
                               class="btn-secondary btn-sm">
                                 Voir
                            </a>
                            </div>
                            <div class="publication-meta">
                               <span> <?= e($pub['type_publication'] ?? 'Article') ?></span>
                            
                                <span>•</span>
                                <span> <?= format_date($pub['date_publication']) ?></span>
                                <?php if (!empty($pub['auteurs'])): ?>
                                    <span>•</span>
                                    <span> <?= e($pub['auteurs']) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p style="text-align: center; color: #9CA3AF; padding: 30px;">
                    Aucune publication liée à ce projet
                </p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modale -->
<?php ViewComponents::renderModal([
    'id' => 'projet-modal',
    'title' => 'Modifier le projet',
    'content' => '<div id="modal-form-container"></div>'
]); ?>

<style>
.grid-2-cols {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 24px;
    margin-bottom: 24px;
}

.card {
    background: white;
    border-radius: 12px;
    border: 1px solid #E5E7EB;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.card-header {
    padding: 20px 24px;
    border-bottom: 1px solid #E5E7EB;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.card-header h2 {
    font-size: 18px;
    font-weight: 600;
    color: #111827;
    margin: 0;
}

.card-body {
    padding: 24px;
}

.info-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.info-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px;
    background: #F9FAFB;
    border-radius: 8px;
    gap: 20px;
}

.info-label {
    color: #6B7280;
    font-weight: 500;
    min-width: 150px;
}

.info-value {
    font-weight: 600;
    color: #111827;
    text-align: right;
    flex: 1;
}

.stats-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.stat-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px;
    background: #F9FAFB;
    border-radius: 8px;
}

.stat-label {
    color: #6B7280;
    font-weight: 500;
}

.stat-value {
    font-size: 24px;
    font-weight: 700;
    color: #5B7FFF;
}

.progress {
    width: 100%;
    background: #E5E7EB;
    border-radius: 6px;
    overflow: hidden;
}

.progress-bar {
    height: 100%;
    transition: width 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 11px;
    font-weight: 600;
}

.membres-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 16px;
}

.membre-card {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px;
    background: #F9FAFB;
    border-radius: 8px;
    border: 1px solid #E5E7EB;
    transition: all 0.2s;
}

.membre-card:hover {
    border-color: #5B7FFF;
    box-shadow: 0 2px 8px rgba(91, 127, 255, 0.1);
}

.membre-info {
    display: flex;
    align-items: center;
    gap: 12px;
    flex: 1;
}

.membre-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, #5B7FFF, #667eea);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 14px;
}

.membre-details {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.membre-details strong {
    color: #111827;
    font-size: 14px;
}

.membre-grade {
    color: #6B7280;
    font-size: 12px;
}

.btn-icon {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    border: none;
    background: transparent;
    color: #9CA3AF;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}

.btn-icon:hover {
    background: #FEE2E2;
    color: #EF4444;
}

.btn-sm {
    padding: 6px 14px;
    font-size: 13px;
}

.publications-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.publication-item {
    padding: 16px;
    background: #F9FAFB;
    border-radius: 8px;
    border-left: 3px solid #5B7FFF;
}

.publication-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
    gap: 12px;
}

.publication-title {
    font-weight: 600;
    color: #111827;
    text-decoration: none;
    flex: 1;
}

.publication-title:hover {
    color: #5B7FFF;
}

.publication-meta {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #6B7280;
    font-size: 13px;
}

@media (max-width: 768px) {
    .grid-2-cols {
        grid-template-columns: 1fr;
    }
    
    .membres-grid {
        grid-template-columns: 1fr;
    }
    
    .info-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
    }
    
    .info-label {
        min-width: auto;
    }
    
    .info-value {
        text-align: left;
    }
}
</style>

<?php ViewComponents::renderFooter(); ?>