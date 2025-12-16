<?php
/**
 * Vue Historique des réservations d'équipements
 */

require_once __DIR__ . '/../../../lib/helpers.php';
require_once __DIR__ . '/../../../lib/ViewComponents.php';

ViewComponents::renderHeader([
    'title' => 'Historique des réservations',
    'username' => session('username'),
    'role' => 'admin',
    'additionalJs' => [
        base_url('assets/js/ui.js')
    ]
]);
?>

<div class="container">
    <?php ViewComponents::renderBreadcrumbs([
        ['label' => 'Dashboard', 'url' => base_url('admin/dashboard')],
        ['label' => 'Équipements', 'url' => base_url('admin/equipements/equipements')],
        ['label' => $equipement ? e($equipement['nom']) : 'Historique global', 'url' => $equipement ? base_url('admin/equipements/equipements/view/' . $equipement['id']) : null],
        ['label' => 'Historique']
    ]); ?>
    
    <div class="page-header">
        <h1>
             Historique des réservations
            <?php if ($equipement): ?>
                - <?= e($equipement['nom']) ?>
            <?php endif; ?>
        </h1>
        <div class="page-actions">
            <?php if ($equipement): ?>
                <button class="btn-secondary" onclick="window.location.href='<?= base_url('admin/equipements/equipements/view/' . $equipement['id']) ?>'">
                     Voir l'équipement
                </button>
            <?php endif; ?>
            <button class="btn-secondary" onclick="window.location.href='<?= base_url('admin/equipements/equipements') ?>'">
                 Retour à la liste
            </button>
            <button class="btn-secondary" onclick="window.print()">
                Imprimer
            </button>
        </div>
    </div>
    
    <!-- Statistiques rapides -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-value"><?= count($creneaux) ?></div>
                <div class="stat-label">Réservations totales</div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-value">
                    <?= count(array_filter($creneaux, function($c) { 
                        return $c['statut'] === 'confirme'; 
                    })) ?>
                </div>
                <div class="stat-label">Confirmées</div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-value">
                    <?= count(array_filter($creneaux, function($c) { 
                        return $c['statut'] === 'en_attente'; 
                    })) ?>
                </div>
                <div class="stat-label">En attente</div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-value">
                    <?= count(array_filter($creneaux, function($c) { 
                        return $c['statut'] === 'annule'; 
                    })) ?>
                </div>
                <div class="stat-label">Annulées</div>
            </div>
        </div>
    </div>
    
    <!-- Filtres -->
    <div class="card" style="margin-top: 24px;">
        <div class="card-header">
            <h2> Filtrer l'historique</h2>
        </div>
        <div class="card-body">
            <form method="GET" class="filter-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="date_debut">Date début</label>
                        <input type="date" 
                               name="date_debut" 
                               id="date_debut" 
                               value="<?= $_GET['date_debut'] ?? '' ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="date_fin">Date fin</label>
                        <input type="date" 
                               name="date_fin" 
                               id="date_fin" 
                               value="<?= $_GET['date_fin'] ?? '' ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="statut">Statut</label>
                        <select name="statut" id="statut">
                            <option value="">-- Tous les statuts --</option>
                            <option value="confirme" <?= ($_GET['statut'] ?? '') === 'confirme' ? 'selected' : '' ?>>Confirmé</option>
                            <option value="en_attente" <?= ($_GET['statut'] ?? '') === 'en_attente' ? 'selected' : '' ?>>En attente</option>
                            <option value="annule" <?= ($_GET['statut'] ?? '') === 'annule' ? 'selected' : '' ?>>Annulé</option>
                            <option value="termine" <?= ($_GET['statut'] ?? '') === 'termine' ? 'selected' : '' ?>>Terminé</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn-primary" style="width: 100%;">
                            Filtrer
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Liste des réservations -->
    <div class="card" style="margin-top: 24px;">
        <div class="card-header">
            <h2>Réservations (<?= count($creneaux) ?>)</h2>
        </div>
        <div class="card-body">
            <?php if (empty($creneaux)): ?>
                <p style="text-align: center; color: #9CA3AF; padding: 40px;">
                    Aucune réservation trouvée
                </p>
            <?php else: ?>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <?php if (!$equipement): ?>
                                    <th>Équipement</th>
                                <?php endif; ?>
                                <th>Membre</th>
                                <th>Date début</th>
                                <th>Date fin</th>
                                <th>Durée</th>
                                <th>Motif</th>
                                <th>Statut</th>
                                <th style="text-align: center;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($creneaux as $creneau): 
                                $debut = new DateTime($creneau['date_debut']);
                                $fin = new DateTime($creneau['date_fin']);
                                $duree = $debut->diff($fin);
                                $heures = ($duree->days * 24) + $duree->h;
                            ?>
                                <tr>
                                    <?php if (!$equipement): ?>
                                        <td>
                                            <strong><?= e($creneau['equipement_nom']) ?></strong>
                                            <div style="font-size: 12px; color: #6B7280;">
                                                <?= e($creneau['type_equipement'] ?? '') ?>
                                            </div>
                                        </td>
                                    <?php endif; ?>
                                    
                                    <td>
                                        <strong><?= e($creneau['membre_nom']) ?></strong>
                                        <?php if (!empty($creneau['membre_poste'])): ?>
                                            <div style="font-size: 12px; color: #6B7280;">
                                                <?= e($creneau['membre_poste']) ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <td><?= format_date($creneau['date_debut'], 'd/m/Y H:i') ?></td>
                                    <td><?= format_date($creneau['date_fin'], 'd/m/Y H:i') ?></td>
                                    
                                    <td>
                                        <?php if ($heures > 24): ?>
                                            <?= $duree->days ?> jour(s)
                                        <?php else: ?>
                                            <?= $heures ?>h <?= $duree->i ?>min
                                        <?php endif; ?>
                                    </td>
                                    
                                    <td>
                                        <div style="max-width: 200px; overflow: hidden; text-overflow: ellipsis;">
                                            <?= e($creneau['motif'] ?? '-') ?>
                                        </div>
                                    </td>
                                    
                                    <td>
                                        <?php
                                        $badges = [
                                            'confirme' => '<span class="badge badge-success">✓ Confirmé</span>',
                                            'en_attente' => '<span class="badge badge-warning"> En attente</span>',
                                            'annule' => '<span class="badge badge-danger">✗ Annulé</span>',
                                            'termine' => '<span class="badge badge-secondary">✓ Terminé</span>'
                                        ];
                                        echo $badges[$creneau['statut']] ?? '<span class="badge">' . e($creneau['statut']) . '</span>';
                                        ?>
                                    </td>
                                    
                                    <td style="text-align: center;">
                                        <button class="btn-action btn-view" 
                                                onclick="voirDetails(<?= $creneau['id'] ?>)"
                                                title="Voir détails">
                                            voir
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php 
    if (isset($pagination)) {
        echo Utils::renderPagination(
            $pagination, 
            $equipement 
                ? base_url('admin/equipements/equipements/historique/' . $equipement['id']) 
                : base_url('admin/equipements/equipements/historique')
        );
    }
    ?>
</div>

<style>
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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

.filter-form .form-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    align-items: end;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
}

.data-table thead {
    background: #F9FAFB;
}

.data-table th,
.data-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #E5E7EB;
}

.data-table th {
    font-weight: 600;
    color: #374151;
    font-size: 14px;
}

.data-table tbody tr:hover {
    background: #F9FAFB;
}

@media print {
    .page-actions,
    .breadcrumbs,
    .card:first-of-type {
        display: none !important;
    }
}

@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .filter-form .form-row {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
function voirDetails(creneauId) {
    alert('Détails du créneau #' + creneauId + ' - À implémenter');
    // TODO: Ouvrir une modale avec les détails complets
}
</script>

<?php ViewComponents::renderFooter(['role' => 'admin']); ?>