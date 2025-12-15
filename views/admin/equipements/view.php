<?php
/**
 * Vue détaillée d'un équipement
 */

require_once __DIR__ . '/../../../lib/helpers.php';
require_once __DIR__ . '/../../../lib/ViewComponents.php';

ViewComponents::renderHeader([
    'title' => 'Détails de l\'équipement',
    'username' => session('username'),
    'role' => 'admin',
    'additionalJs' => [
        base_url('assets/js/ui.js'),
        base_url('assets/js/admin/equipements-handler.js')
    ]
]);
?>

<div class="container">
    <?php ViewComponents::renderBreadcrumbs([
        ['label' => 'Dashboard', 'url' => base_url('admin/dashboard')],
        ['label' => 'Équipements', 'url' => base_url('admin/equipements/equipements')],
        ['label' => e($equipement['nom'] ?? 'Détails')]
    ]); ?>
    
    <!-- En-tête de l'équipement -->
    <div class="page-header">
        <div>
            <h1> <?= e($equipement['nom']) ?></h1>
            <p style="color: #6B7280; margin-top: 8px;">
                <?= e($equipement['type_equipement']) ?> 
                <?php if (!empty($equipement['numero_serie'])): ?>
                    • N° série: <code><?= e($equipement['numero_serie']) ?></code>
                <?php endif; ?>
            </p>
        </div>
        <div class="page-actions">
            <button class="btn-secondary" onclick="equipements.edit(<?= $equipement['id'] ?>)">
                Modifier
            </button>
            <button class="btn-secondary" onclick="equipements.openMaintenanceModal(<?= $equipement['id'] ?>)">
               Maintenance
            </button>
            <button class="btn-secondary" onclick="equipements.delete(<?= $equipement['id'] ?>)">
                 Supprimer
            </button>
        </div>
    </div>
    
    <div class="grid-2-cols">
        <!-- Informations générales -->
        <div class="card">
            <div class="card-header">
                <h2>Informations générales</h2>
            </div>
            <div class="card-body">
                <div class="info-list">
                    <div class="info-item">
                        <span class="info-label">État</span>
                        <span class="info-value">
                            <?php
                            $badges = [
                                'libre' => '<span class="badge badge-success">✓ Libre</span>',
                                'reserve' => '<span class="badge badge-info"> Réservé</span>',
                                'en_maintenance' => '<span class="badge badge-warning"> Maintenance</span>',
                                'hors_service' => '<span class="badge badge-danger">✗ Hors service</span>'
                            ];
                            echo $badges[$equipement['etat']] ?? '<span class="badge badge-secondary">' . e($equipement['etat']) . '</span>';
                            ?>
                        </span>
                    </div>
                    
                    <?php if (!empty($equipement['localisation'])): ?>
                    <div class="info-item">
                        <span class="info-label">Localisation</span>
                        <span class="info-value"><?= e($equipement['localisation']) ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($equipement['equipe_nom'])): ?>
                    <div class="info-item">
                        <span class="info-label">Équipe assignée</span>
                        <span class="info-value">
                            <a href="<?= base_url('admin/equipes/equipes/view/' . $equipement['equipe_id']) ?>">
                                <?= e($equipement['equipe_nom']) ?>
                            </a>
                        </span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($equipement['date_acquisition'])): ?>
                    <div class="info-item">
                        <span class="info-label">Date d'acquisition</span>
                        <span class="info-value"><?= format_date($equipement['date_acquisition']) ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Statistiques -->
        <div class="card">
            <div class="card-header">
                <h2> Statistiques d'utilisation</h2>
            </div>
            <div class="card-body">
                <div class="stats-list">
                    <div class="stat-item">
                        <span class="stat-label">Réservations totales</span>
                        <span class="stat-value"><?= $stats['nb_reservations_total'] ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Réservations actives</span>
                        <span class="stat-value"><?= $stats['nb_reservations_actives'] ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Taux d'utilisation</span>
                        <span class="stat-value"><?= $stats['taux_utilisation'] ?>%</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Description -->
    <?php if (!empty($equipement['description'])): ?>
    <div class="card" style="margin-top: 24px;">
        <div class="card-header">
            <h2>Description</h2>
        </div>
        <div class="card-body">
            <p style="line-height: 1.6; color: #374151;">
                <?= nl2br(e($equipement['description'])) ?>
            </p>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Historique des réservations -->
    <div class="card" style="margin-top: 24px;">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
            <h2>Historique des réservations (<?= count($creneaux) ?>)</h2>
            <a href="<?= base_url('admin/equipements/equipements/historique/' . $equipement['id']) ?>" 
               class="btn-secondary btn-sm">
                Voir tout l'historique
            </a>
        </div>
        <div class="card-body">
            <?php if (empty($creneaux)): ?>
                <p style="color: #9CA3AF; text-align: center; padding: 20px;">
                    Aucune réservation pour cet équipement
                </p>
            <?php else: ?>
                <div class="reservations-list">
                    <?php 
                    $creneaux_recents = array_slice($creneaux, 0, 10);
                    foreach ($creneaux_recents as $creneau): 
                    ?>
                        <div class="reservation-item">
                            <div class="reservation-info">
                                <div>
                                    <strong><?= e($creneau['membre_nom']) ?></strong>
                                    <?php if (!empty($creneau['membre_poste'])): ?>
                                        <span style="color: #6B7280;"> • <?= e($creneau['membre_poste']) ?></span>
                                    <?php endif; ?>
                                </div>
                                <div style="color: #6B7280; font-size: 14px; margin-top: 4px;">
                                    Du <?= format_date($creneau['date_debut'], 'd/m/Y H:i') ?> 
                                    au <?= format_date($creneau['date_fin'], 'd/m/Y H:i') ?>
                                </div>
                                <?php if (!empty($creneau['motif'])): ?>
                                    <div style="color: #6B7280; font-size: 13px; margin-top: 4px;">
                                        <?= e($creneau['motif']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div>
                                <?php
                                $statut_badges = [
                                    'confirme' => '<span class="badge badge-success">Confirmé</span>',
                                    'en_attente' => '<span class="badge badge-warning">En attente</span>',
                                    'annule' => '<span class="badge badge-danger">Annulé</span>',
                                    'termine' => '<span class="badge badge-secondary">Terminé</span>'
                                ];
                                echo $statut_badges[$creneau['statut']] ?? '<span class="badge">' . e($creneau['statut']) . '</span>';
                                ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modale pour maintenance -->
<?php ViewComponents::renderModal([
    'id' => 'equipement-modal',
    'title' => 'Planifier une maintenance',
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

.info-list, .stats-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.info-item, .stat-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px;
    background: #F9FAFB;
    border-radius: 8px;
}

.info-label, .stat-label {
    color: #6B7280;
    font-weight: 500;
}

.info-value {
    color: #111827;
    font-weight: 600;
}

.stat-value {
    font-size: 24px;
    font-weight: 700;
    color: #5B7FFF;
}

.reservations-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.reservation-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px;
    background: #F9FAFB;
    border-radius: 8px;
    border: 1px solid #E5E7EB;
}

.reservation-info {
    flex: 1;
}

.btn-sm {
    padding: 6px 14px;
    font-size: 13px;
}
</style>

<?php ViewComponents::renderFooter(); ?>