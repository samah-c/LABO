<?php
/**
 * Tableau de bord des équipements
 */

require_once __DIR__ . '/../../../lib/helpers.php';
require_once __DIR__ . '/../../../lib/ViewComponents.php';

ViewComponents::renderHeader([
    'title' => 'Tableau de bord des équipements',
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
        ['label' => 'Tableau de bord']
    ]); ?>
    
    <div class="page-header">
        <h1>Tableau de bord des équipements</h1>
        <div class="page-actions">
            <button class="btn-secondary" onclick="window.location.href='<?= base_url('admin/equipements/equipements/rapport') ?>'">
                Générer un rapport
            </button>
            <button class="btn-secondary" onclick="window.location.href='<?= base_url('admin/equipements/equipements/historique') ?>'">
                Historique complet
            </button>
        </div>
    </div>
    
    <!-- Statistiques globales -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-value"><?= $stats['total'] ?></div>
                <div class="stat-label">Équipements totaux</div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-value"><?= count($libres) ?></div>
                <div class="stat-label">Équipements libres</div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-value"><?= count($reserves) ?></div>
                <div class="stat-label">Équipements réservés</div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-value"><?= count($maintenance) ?></div>
                <div class="stat-label">En maintenance</div>
            </div>
        </div>
    </div>
    
    <!-- Répartition par type -->
    <div class="card" style="margin-top: 24px;">
        <div class="card-header">
            <h2>Répartition par type</h2>
        </div>
        <div class="card-body">
            <div class="chart-container">
                <?php foreach ($stats['par_type'] as $type): ?>
                    <div class="chart-bar-item">
                        <div class="chart-bar-label"><?= e($type['type_equipement']) ?></div>
                        <div class="chart-bar-wrapper">
                            <div class="chart-bar" 
                                 style="width: <?= ($type['count'] / $stats['total']) * 100 ?>%; background: #5B7FFF;">
                                <span class="chart-bar-value"><?= $type['count'] ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <div class="grid-2-cols">
        <!-- Répartition par état -->
        <div class="card">
            <div class="card-header">
                <h2>Répartition par état</h2>
            </div>
            <div class="card-body">
                <div class="pie-chart-legend">
                    <?php 
                    $colors = [
                        'libre' => '#10B981',
                        'reserve' => '#3B82F6',
                        'en_maintenance' => '#F59E0B',
                        'hors_service' => '#EF4444'
                    ];
                    foreach ($stats['par_etat'] as $etat): 
                    ?>
                        <div class="legend-item">
                            <div class="legend-color" style="background: <?= $colors[$etat['etat']] ?? '#6B7280' ?>"></div>
                            <div class="legend-label"><?= e($etat['etat']) ?></div>
                            <div class="legend-value"><?= $etat['count'] ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <!-- Répartition par équipe -->
        <div class="card">
            <div class="card-header">
                <h2> Répartition par équipe</h2>
            </div>
            <div class="card-body">
                <div class="pie-chart-legend">
                    <?php foreach ($stats['par_equipe'] as $equipe): ?>
                        <div class="legend-item">
                            <div class="legend-color" style="background: #5B7FFF"></div>
                            <div class="legend-label"><?= e($equipe['nom'] ?: 'Non assigné') ?></div>
                            <div class="legend-value"><?= $equipe['count'] ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Conflits de réservation -->
    <?php if (!empty($conflits)): ?>
    <div class="card" style="margin-top: 24px;">
        <div class="card-header">
            <h2>Conflits de réservation détectés</h2>
        </div>
        <div class="card-body">
            <div class="alert alert-warning">
                <strong>Attention!</strong> <?= count($conflits) ?> conflit(s) de réservation détecté(s).
            </div>
            <div class="conflicts-list">
                <?php foreach ($conflits as $conflit): ?>
                    <div class="conflict-item">
                        <div class="conflict-info">
                            <strong><?= e($conflit['equipement_nom']) ?></strong>
                            <div style="color: #6B7280; font-size: 14px; margin-top: 4px;">
                                Conflit entre <strong><?= e($conflit['membre1']) ?></strong> 
                                et <strong><?= e($conflit['membre2']) ?></strong>
                            </div>
                            <div style="color: #EF4444; font-size: 13px; margin-top: 4px;">
                                Période: <?= format_date($conflit['date_debut'], 'd/m/Y H:i') ?> 
                                - <?= format_date($conflit['date_fin'], 'd/m/Y H:i') ?>
                            </div>
                        </div>
                        <button class="btn-secondary btn-sm" 
                                onclick="window.location.href='<?= base_url('admin/equipements/equipements/view/' . $conflit['equipement_id']) ?>'">
                            Résoudre
                        </button>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Équipements en maintenance -->
    <?php if (!empty($maintenance)): ?>
    <div class="card" style="margin-top: 24px;">
        <div class="card-header">
            <h2> Équipements en maintenance</h2>
        </div>
        <div class="card-body">
            <div class="maintenance-list">
                <?php foreach ($maintenance as $eq): ?>
                    <div class="maintenance-item">
                        <div class="maintenance-info">
                            <strong><?= e($eq['nom']) ?></strong>
                            <div style="color: #6B7280; font-size: 14px; margin-top: 4px;">
                                <?= e($eq['type_equipement']) ?> 
                                <?php if (!empty($eq['localisation'])): ?>
                                   <?= e($eq['localisation']) ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        <button class="btn-secondary btn-sm" 
                                onclick="window.location.href='<?= base_url('admin/equipements/equipements/view/' . $eq['id']) ?>'">
                            Voir détails
                        </button>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 24px;
}

.stat-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    border: 1px solid #E5E7EB;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
    gap: 16px;
}

.stat-icon {
    width: 56px;
    height: 56px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    flex-shrink: 0;
}

.stat-content {
    flex: 1;
}

.stat-value {
    font-size: 32px;
    font-weight: 700;
    color: #111827;
    line-height: 1;
}

.stat-label {
    color: #6B7280;
    font-size: 14px;
    margin-top: 4px;
}

.grid-2-cols {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 24px;
    margin-top: 24px;
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

.chart-container {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.chart-bar-item {
    display: flex;
    align-items: center;
    gap: 12px;
}

.chart-bar-label {
    min-width: 120px;
    color: #374151;
    font-weight: 500;
    font-size: 14px;
}

.chart-bar-wrapper {
    flex: 1;
    background: #F3F4F6;
    border-radius: 8px;
    height: 32px;
    position: relative;
}

.chart-bar {
    height: 100%;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    padding-right: 12px;
    transition: width 0.3s ease;
}

.chart-bar-value {
    color: white;
    font-weight: 600;
    font-size: 14px;
}

.pie-chart-legend {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 8px;
    background: #F9FAFB;
    border-radius: 6px;
}

.legend-color {
    width: 16px;
    height: 16px;
    border-radius: 4px;
    flex-shrink: 0;
}

.legend-label {
    flex: 1;
    color: #374151;
    font-weight: 500;
}

.legend-value {
    color: #111827;
    font-weight: 700;
    font-size: 18px;
}

.alert {
    padding: 16px;
    border-radius: 8px;
    margin-bottom: 16px;
}

.alert-warning {
    background: #FEF3C7;
    border: 1px solid #F59E0B;
    color: #92400E;
}

.conflicts-list, .maintenance-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.conflict-item, .maintenance-item {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 16px;
    background: #FEF3C7;
    border-radius: 8px;
    border: 1px solid #F59E0B;
}

.maintenance-item {
    background: #F9FAFB;
    border: 1px solid #E5E7EB;
}

.conflict-icon {
    font-size: 32px;
    flex-shrink: 0;
}

.conflict-info, .maintenance-info {
    flex: 1;
}

.btn-sm {
    padding: 6px 14px;
    font-size: 13px;
}
</style>

<?php ViewComponents::renderFooter();