<?php
/**
 * Vue pour les rapports bibliographiques
 */

require_once __DIR__ . '/../../../lib/helpers.php';
require_once __DIR__ . '/../../../lib/ViewComponents.php';

ViewComponents::renderHeader([
    'title' => $data['titre'] ?? 'Rapport bibliographique',
    'username' => session('username'),
    'role' => 'admin'
]);
?>

<div class="container">
    <!-- Breadcrumbs -->
    <?php ViewComponents::renderBreadcrumbs([
        ['label' => 'Dashboard', 'url' => base_url('admin/dashboard')],
        ['label' => 'Publications', 'url' => base_url('admin/publications/publications')],
        ['label' => 'Rapport']
    ]); ?>
    
    <!-- En-tête du rapport -->
    <div class="rapport-header">
        <h1><?= e($data['titre'] ?? 'Rapport bibliographique') ?></h1>
        <div class="rapport-meta">
            <span>Généré le <?= date('d/m/Y à H:i') ?></span>
            <?php if (isset($data['annee'])): ?>
                <span> Année: <?= $data['annee'] ?></span>
            <?php endif; ?>
            <?php if (isset($data['membre'])): ?>
                <span>👤 Auteur: <?= e($data['membre']['username']) ?></span>
            <?php endif; ?>
        </div>
        
        <div class="rapport-actions">
            <button class="btn-secondary" onclick="window.print()">
                 Imprimer
            </button>
            <button class="btn-secondary" onclick="exportRapportPDF()">
                 Exporter PDF
            </button>
            <button class="btn-secondary" onclick="exportRapportCSV()">
                 Exporter CSV
            </button>
            <a href="<?= base_url('admin/publications/publications') ?>" class="btn-secondary">
                ← Retour
            </a>
        </div>
    </div>
    
    <!-- Statistiques du rapport -->
    <div class="rapport-stats">
        <div class="stat-box">
            <div class="stat-number"><?= $data['total'] ?? 0 ?></div>
            <div class="stat-label">Publications totales</div>
        </div>
        
        <?php if (isset($data['par_type'])): ?>
            <?php foreach ($data['par_type'] as $type => $count): ?>
                <div class="stat-box">
                    <div class="stat-number"><?= $count ?></div>
                    <div class="stat-label"><?= e($type) ?></div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <!-- Graphiques -->
    <?php if (isset($data['par_annee']) && !empty($data['par_annee'])): ?>
        <div class="rapport-section">
            <h2> Publications par année</h2>
            <div class="chart-container">
                <canvas id="annee-chart"></canvas>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if (isset($data['par_domaine']) && !empty($data['par_domaine'])): ?>
        <div class="rapport-section">
            <h2> Publications par domaine</h2>
            <div class="chart-container">
                <canvas id="domaine-chart"></canvas>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Liste des publications -->
    <div class="rapport-section">
        <h2> Liste des publications</h2>
        
        <?php if (empty($data['publications'])): ?>
            <div class="empty-state">
                <p>Aucune publication trouvée pour ce rapport</p>
            </div>
        <?php else: ?>
            <div class="publications-list">
                <?php 
                // Grouper par type
                $groupes = [];
                foreach ($data['publications'] as $pub) {
                    $type = $pub['type_publication'];
                    if (!isset($groupes[$type])) {
                        $groupes[$type] = [];
                    }
                    $groupes[$type][] = $pub;
                }
                
                // Afficher chaque groupe
                foreach ($groupes as $type => $pubs):
                ?>
                    <div class="publication-group">
                        <h3><?= e($type) ?> (<?= count($pubs) ?>)</h3>
                        
                        <?php foreach ($pubs as $index => $pub): ?>
                            <div class="publication-item">
                                <div class="pub-number">[<?= $index + 1 ?>]</div>
                                <div class="pub-content">
                                    <div class="pub-title">
                                        <strong><?= e($pub['titre']) ?></strong>
                                    </div>
                                    
                                    <?php if (!empty($pub['auteurs_noms'])): ?>
                                        <div class="pub-authors">
                                            👥 <?= e($pub['auteurs_noms']) ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="pub-meta">
                                        <?php if (!empty($pub['date_publication'])): ?>
                                            <span><?= date('Y', strtotime($pub['date_publication'])) ?></span>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($pub['domaine'])): ?>
                                            <span><?= e($pub['domaine']) ?></span>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($pub['doi'])): ?>
                                            <span>DOI: <code><?= e($pub['doi']) ?></code></span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <?php if (!empty($pub['resume'])): ?>
                                        <div class="pub-abstract">
                                            <?= nl2br(e($pub['resume'])) ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($pub['lien'])): ?>
                                        <div class="pub-link">
                                            <a href="<?= e($pub['lien']) ?>" target="_blank">
                                                 Accéder à la publication
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
@media print {
    .rapport-actions,
    nav,
    .breadcrumbs {
        display: none !important;
    }
    
    .container {
        max-width: 100%;
    }
}

.rapport-header {
    background: white;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    margin-bottom: 30px;
}

.rapport-header h1 {
    margin: 0 0 15px 0;
    color: #1F2937;
}

.rapport-meta {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
    margin-bottom: 20px;
    color: #6B7280;
    font-size: 14px;
}

.rapport-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.rapport-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-box {
    background: white;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    text-align: center;
}

.stat-number {
    font-size: 36px;
    font-weight: 700;
    color: #3B82F6;
    margin-bottom: 5px;
}

.stat-label {
    font-size: 13px;
    color: #6B7280;
}

.rapport-section {
    background: white;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    margin-bottom: 30px;
}

.rapport-section h2 {
    margin: 0 0 20px 0;
    color: #1F2937;
    font-size: 22px;
    border-bottom: 2px solid #E5E7EB;
    padding-bottom: 10px;
}

.chart-container {
    max-width: 800px;
    margin: 0 auto;
    padding: 20px 0;
}

.publication-group {
    margin-bottom: 40px;
}

.publication-group h3 {
    color: #3B82F6;
    margin-bottom: 20px;
    font-size: 18px;
    border-left: 4px solid #3B82F6;
    padding-left: 12px;
}

.publication-item {
    display: flex;
    gap: 15px;
    margin-bottom: 30px;
    padding: 20px;
    background: #F9FAFB;
    border-radius: 8px;
    border-left: 3px solid #3B82F6;
}

.pub-number {
    font-weight: 700;
    color: #6B7280;
    min-width: 40px;
}

.pub-content {
    flex: 1;
}

.pub-title {
    font-size: 16px;
    margin-bottom: 8px;
    color: #1F2937;
}

.pub-authors {
    color: #4B5563;
    font-size: 14px;
    margin-bottom: 8px;
}

.pub-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    font-size: 13px;
    color: #6B7280;
    margin-bottom: 10px;
}

.pub-meta code {
    background: white;
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 11px;
}

.pub-abstract {
    margin-top: 10px;
    padding: 12px;
    background: white;
    border-radius: 6px;
    font-size: 14px;
    color: #4B5563;
    line-height: 1.6;
}

.pub-link {
    margin-top: 10px;
}

.pub-link a {
    color: #3B82F6;
    text-decoration: none;
    font-size: 14px;
}

.pub-link a:hover {
    text-decoration: underline;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #9CA3AF;
}

@media (max-width: 768px) {
    .rapport-actions {
        flex-direction: column;
    }
    
    .rapport-actions button,
    .rapport-actions a {
        width: 100%;
    }
    
    .publication-item {
        flex-direction: column;
    }
}
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Graphique par année
<?php if (isset($data['par_annee']) && !empty($data['par_annee'])): ?>
const anneeData = <?= json_encode($data['par_annee']) ?>;
const anneeChart = new Chart(document.getElementById('annee-chart'), {
    type: 'bar',
    data: {
        labels: Object.keys(anneeData),
        datasets: [{
            label: 'Publications',
            data: Object.values(anneeData),
            backgroundColor: '#3B82F6',
            borderRadius: 8
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        }
    }
});
<?php endif; ?>

// Graphique par domaine
<?php if (isset($data['par_domaine']) && !empty($data['par_domaine'])): ?>
const domaineData = <?= json_encode($data['par_domaine']) ?>;
const domaineChart = new Chart(document.getElementById('domaine-chart'), {
    type: 'doughnut',
    data: {
        labels: Object.keys(domaineData),
        datasets: [{
            data: Object.values(domaineData),
            backgroundColor: [
                '#3B82F6',
                '#10B981',
                '#F59E0B',
                '#EF4444',
                '#8B5CF6',
                '#EC4899'
            ]
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});
<?php endif; ?>

// Export PDF (simulé - nécessite une bibliothèque côté serveur)
function exportRapportPDF() {
    const params = new URLSearchParams(window.location.search);
    params.set('format', 'pdf');
    window.location.href = '<?= base_url('admin/publications/publications/rapport') ?>?' + params.toString();
}

// Export CSV
function exportRapportCSV() {
    const params = new URLSearchParams(window.location.search);
    params.set('format', 'csv');
    window.location.href = '<?= base_url('admin/publications/publications/rapport') ?>?' + params.toString();
}
</script>

<?php ViewComponents::renderFooter(); ?>