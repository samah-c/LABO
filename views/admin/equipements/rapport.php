<?php
/**
 * Génération de rapports d'utilisation des équipements
 */

require_once __DIR__ . '/../../../lib/helpers.php';
require_once __DIR__ . '/../../../lib/ViewComponents.php';

ViewComponents::renderHeader([
    'title' => 'Rapport d\'utilisation',
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
        ['label' => 'Rapport d\'utilisation']
    ]); ?>
    
    <div class="page-header">
        <h1>Rapport d'utilisation des équipements</h1>
        <div class="page-actions">
            <button class="btn-secondary" onclick="window.print()">
               Imprimer
            </button>
            <button class="btn-secondary" onclick="exportRapport()">
                Exporter en PDF
            </button>
        </div>
    </div>
    
    <!-- Sélection de période -->
    <div class="card" style="margin-bottom: 24px;">
        <div class="card-header">
            <h2> Période du rapport</h2>
        </div>
        <div class="card-body">
            <form method="GET" class="rapport-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="date_debut">Date de début</label>
                        <input type="date" 
                               name="date_debut" 
                               id="date_debut" 
                               value="<?= e($dateDebut) ?>"
                               max="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="form-group">
                        <label for="date_fin">Date de fin</label>
                        <input type="date" 
                               name="date_fin" 
                               id="date_fin" 
                               value="<?= e($dateFin) ?>"
                               max="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn-primary" style="width: 100%;">
                             Générer le rapport
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Résumé de la période -->
    <div class="rapport-header">
        <h2>Période: <?= format_date($dateDebut, 'd/m/Y') ?> - <?= format_date($dateFin, 'd/m/Y') ?></h2>
        <p>Rapport généré le <?= format_date(date('Y-m-d'), 'd/m/Y à H:i') ?></p>
    </div>
    
    <!-- Statistiques globales -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-value"><?= $nbReservations ?></div>
                <div class="stat-label">Réservations totales</div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-value"><?= count($statsParMembre ?? []) ?></div>
                <div class="stat-label">Utilisateurs actifs</div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-value"><?= count($tauxOccupation ?? []) ?></div>
                <div class="stat-label">Équipements suivis</div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-value">
                    <?php
                    $heuresTotal = 0;
                    if (!empty($statsParMembre)) {
                        foreach ($statsParMembre as $stat) {
                            $heuresTotal += $stat['heures_totales'] ?? 0;
                        }
                    }
                    echo round($heuresTotal, 0);
                    ?>
                </div>
                <div class="stat-label">Heures d'utilisation</div>
            </div>
        </div>
    </div>
    
    <!-- Taux d'occupation par équipement -->
    <?php if (!empty($tauxOccupation) && is_array($tauxOccupation)): ?>
    <div class="card" style="margin-top: 24px;">
        <div class="card-header">
            <h2> Taux d'occupation par équipement</h2>
        </div>
        <div class="card-body">
            <div class="occupation-list">
                <?php
                // Trier par taux d'occupation décroissant
                usort($tauxOccupation, function($a, $b) {
                    return ($b['taux'] ?? 0) <=> ($a['taux'] ?? 0);
                });
                
                foreach ($tauxOccupation as $item):
                    $taux = $item['taux'] ?? 0;
                    $color = $taux >= 75 ? '#EF4444' : ($taux >= 50 ? '#F59E0B' : '#10B981');
                ?>
                    <div class="occupation-item">
                        <div class="occupation-info">
                            <strong><?= e($item['nom'] ?? 'N/A') ?></strong>
                            <div class="occupation-bar-wrapper">
                                <div class="occupation-bar" style="width: <?= $taux ?>%; background: <?= $color ?>"></div>
                            </div>
                        </div>
                        <div class="occupation-value" style="color: <?= $color ?>">
                            <?= number_format($taux, 1) ?>%
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div class="card" style="margin-top: 24px;">
        <div class="card-header">
            <h2> Taux d'occupation par équipement</h2>
        </div>
        <div class="card-body">
            <p style="text-align: center; color: #9CA3AF; padding: 40px;">
                Aucune donnée d'occupation disponible pour cette période
            </p>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Top 10 utilisateurs -->
    <?php if (!empty($statsParMembre) && is_array($statsParMembre)): ?>
    <div class="card" style="margin-top: 24px;">
        <div class="card-header">
            <h2>Top utilisateurs</h2>
        </div>
        <div class="card-body">
            <div class="top-users-list">
                <?php
                $top = array_slice($statsParMembre, 0, 10);
                $position = 1;
                $maxReservations = !empty($statsParMembre) ? max(array_column($statsParMembre, 'nb_reservations')) : 1;
                
                foreach ($top as $stat):
                    $nbReservations = $stat['nb_reservations'] ?? 0;
                    $heures = $stat['heures_totales'] ?? 0;
                ?>
                    <div class="top-user-item">
                        <div class="top-user-position">
                            <?php
                            $medals = ['🥇', '🥈', '🥉'];
                            echo $position <= 3 ? $medals[$position - 1] : $position;
                            ?>
                        </div>
                        <div class="top-user-info">
                            <strong><?= e($stat['username'] ?? 'N/A') ?></strong>
                            <div style="color: #6B7280; font-size: 14px; margin-top: 4px;">
                                <?= $nbReservations ?> réservation(s) • 
                                <?= number_format($heures, 1) ?> heures
                            </div>
                        </div>
                        <div class="top-user-chart">
                            <div class="mini-bar" style="width: <?= $maxReservations > 0 ? min(100, ($nbReservations / $maxReservations) * 100) : 0 ?>%"></div>
                        </div>
                    </div>
                <?php 
                    $position++;
                endforeach; 
                ?>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div class="card" style="margin-top: 24px;">
        <div class="card-header">
            <h2> Top utilisateurs</h2>
        </div>
        <div class="card-body">
            <p style="text-align: center; color: #9CA3AF; padding: 40px;">
                Aucune réservation pour cette période
            </p>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Détails par utilisateur -->
    <?php if (!empty($statsParMembre) && is_array($statsParMembre)): ?>
    <div class="card" style="margin-top: 24px;">
        <div class="card-header">
            <h2>Détails par utilisateur</h2>
        </div>
        <div class="card-body">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Utilisateur</th>
                        <th style="text-align: center;">Nombre de réservations</th>
                        <th style="text-align: center;">Heures totales</th>
                        <th style="text-align: center;">Moyenne par réservation</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($statsParMembre as $stat): 
                        $nbRes = $stat['nb_reservations'] ?? 0;
                        $heures = $stat['heures_totales'] ?? 0;
                        $moyenne = $nbRes > 0 ? ($heures / $nbRes) : 0;
                    ?>
                        <tr>
                            <td><strong><?= e($stat['username'] ?? 'N/A') ?></strong></td>
                            <td style="text-align: center;"><?= $nbRes ?></td>
                            <td style="text-align: center;"><?= number_format($heures, 1) ?>h</td>
                            <td style="text-align: center;"><?= number_format($moyenne, 1) ?>h</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
.rapport-form {
    max-width: 800px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr auto;
    gap: 16px;
    align-items: end;
}

.rapport-header {
    background: linear-gradient(135deg, #5B7FFF 0%, #4461F2 100%);
    color: white;
    padding: 32px;
    border-radius: 12px;
    margin-bottom: 24px;
}

.rapport-header h2 {
    margin: 0 0 8px;
    font-size: 24px;
}

.rapport-header p {
    margin: 0;
    opacity: 0.9;
}

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

.occupation-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.occupation-item {
    display: flex;
    align-items: center;
    gap: 16px;
}

.occupation-info {
    flex: 1;
}

.occupation-bar-wrapper {
    margin-top: 8px;
    height: 8px;
    background: #F3F4F6;
    border-radius: 4px;
    overflow: hidden;
}

.occupation-bar {
    height: 100%;
    border-radius: 4px;
    transition: width 0.3s ease;
}

.occupation-value {
    font-size: 24px;
    font-weight: 700;
    min-width: 60px;
    text-align: right;
}

.top-users-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.top-user-item {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 16px;
    background: #F9FAFB;
    border-radius: 8px;
    border: 1px solid #E5E7EB;
}

.top-user-position {
    font-size: 24px;
    font-weight: 700;
    min-width: 40px;
    text-align: center;
}

.top-user-info {
    flex: 1;
}

.top-user-chart {
    width: 120px;
    height: 24px;
    background: #E5E7EB;
    border-radius: 4px;
    overflow: hidden;
}

.mini-bar {
    height: 100%;
    background: #5B7FFF;
    transition: width 0.3s ease;
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

@media print {
    .page-actions,
    .breadcrumbs,
    .card:first-of-type {
        display: none !important;
    }
    
    .container {
        max-width: 100% !important;
        padding: 0 !important;
    }
}

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
function exportRapport() {
    alert('Fonctionnalité d\'export PDF à implémenter avec une bibliothèque comme TCPDF ou mPDF');
    // TODO: Implémenter l'export PDF
}
</script>

<?php ViewComponents::renderFooter(['role' => 'admin']); ?>