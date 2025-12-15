<?php
/**
 * Rapport détaillé d'un projet
 * À placer dans : /TDW_project/views/admin/projets/rapport.php
 */

require_once __DIR__ . '/../../../lib/helpers.php';
require_once __DIR__ . '/../../../lib/ViewComponents.php';

ViewComponents::renderHeader([
    'title' => 'Rapport - ' . e($projet['titre']),
    'username' => session('username'),
    'role' => 'admin'
]);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rapport - <?= e($projet['titre']) ?></title>
    <style>
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                font-size: 12pt;
            }
            .page-break {
                page-break-after: always;
            }
        }
        
        .rapport-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 40px;
            background: white;
        }
        
        .rapport-header {
            text-align: center;
            border-bottom: 3px solid #5B7FFF;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        
        .rapport-header h1 {
            color: #1F2937;
            margin: 0 0 10px;
        }
        
        .rapport-header .subtitle {
            color: #6B7280;
            font-size: 14px;
        }
        
        .rapport-section {
            margin-bottom: 30px;
        }
        
        .rapport-section h2 {
            color: #5B7FFF;
            border-left: 4px solid #5B7FFF;
            padding-left: 12px;
            margin-bottom: 15px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .info-item {
            padding: 12px;
            background: #F9FAFB;
            border-radius: 6px;
        }
        
        .info-label {
            font-weight: 600;
            color: #6B7280;
            font-size: 13px;
            display: block;
            margin-bottom: 4px;
        }
        
        .info-value {
            color: #1F2937;
            font-size: 15px;
        }
        
        .membre-list {
            list-style: none;
            padding: 0;
        }
        
        .membre-item {
            padding: 10px;
            background: #F9FAFB;
            margin-bottom: 8px;
            border-radius: 6px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .publication-item {
            padding: 12px;
            background: #F9FAFB;
            margin-bottom: 10px;
            border-radius: 6px;
            border-left: 3px solid #5B7FFF;
        }
        
        .publication-title {
            font-weight: 600;
            color: #1F2937;
            margin-bottom: 4px;
        }
        
        .publication-meta {
            color: #6B7280;
            font-size: 13px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin: 20px 0;
        }
        
        .stat-box {
            text-align: center;
            padding: 20px;
            background: #F9FAFB;
            border-radius: 8px;
        }
        
        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: #5B7FFF;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #6B7280;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <div class="rapport-container">
        <!-- Actions d'impression -->
<div class="no-print" style="margin-bottom: 30px; margin-top: 80px; display: flex; justify-content: space-between; align-items: center; gap: 10px;">
    <a href="<?= base_url('admin/projets/projets/view/' . $projet['id']) ?>" class="btn-secondary">
        Retour
    </a>
    <button onclick="window.print()" class="btn-primary">
        Imprimer / Exporter PDF
    </button>
</div>
        
        <!-- En-tête du rapport -->
        <div class="rapport-header">
            <h1>RAPPORT DE PROJET</h1>
            <div class="subtitle">
                Généré le <?= date('d/m/Y à H:i') ?>
            </div>
        </div>
        
        <!-- Informations générales -->
        <div class="rapport-section">
            <h2> Informations Générales</h2>
            
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">Titre du projet</span>
                    <span class="info-value"><?= e($projet['titre']) ?></span>
                </div>
                
                <div class="info-item">
                    <span class="info-label">Thématique</span>
                    <span class="info-value"><?= e($projet['thematique']) ?></span>
                </div>
                
                <div class="info-item">
                    <span class="info-label">Statut</span>
                    <span class="info-value"><?= e($projet['status']) ?></span>
                </div>
                
                <div class="info-item">
                    <span class="info-label">Responsable scientifique</span>
                    <span class="info-value">
                        <?= e($responsable['username'] ?? 'Non assigné') ?>
                    </span>
                </div>
                
                <div class="info-item">
                    <span class="info-label">Date de début</span>
                    <span class="info-value"><?= format_date($projet['date_debut']) ?></span>
                </div>
                
                <div class="info-item">
                    <span class="info-label">Date de fin prévue</span>
                    <span class="info-value">
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
        
        <!-- Description -->
        <div class="rapport-section">
            <h2> Description du projet</h2>
            <div style="line-height: 1.8; color: #374151;">
                <?= nl2br(e($projet['description'])) ?>
            </div>
        </div>
        
        <!-- Statistiques -->
        <div class="rapport-section">
            <h2> Statistiques</h2>
            
            <div class="stats-grid">
                <div class="stat-box">
                    <div class="stat-value"><?= count($membres) ?></div>
                    <div class="stat-label">Membres</div>
                </div>
                
                <div class="stat-box">
                    <div class="stat-value"><?= count($publications) ?></div>
                    <div class="stat-label">Publications</div>
                </div>
                
                <div class="stat-box">
                    <div class="stat-value">
                        <?= LabHelpers::calculateProjectProgress(
                            $projet['date_debut'], 
                            $projet['date_fin']
                        ) ?>%
                    </div>
                    <div class="stat-label">Progression</div>
                </div>
            </div>
        </div>
        
        <!-- Équipe du projet -->
        <div class="rapport-section page-break">
            <h2> Équipe du projet</h2>
            
            <?php if (!empty($membres)): ?>
                <ul class="membre-list">
                    <?php foreach ($membres as $membre): ?>
                        <li class="membre-item">
                            <div>
                                <strong><?= e($membre['username']) ?></strong>
                                <?php if (!empty($membre['grade'])): ?>
                                    <span style="color: #6B7280;"> - <?= e($membre['grade']) ?></span>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($membre['role'])): ?>
                                <span style="background: #5B7FFF; color: white; padding: 4px 10px; border-radius: 12px; font-size: 12px;">
                                    <?= e($membre['role']) ?>
                                </span>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p style="color: #9CA3AF; text-align: center; padding: 20px;">
                    Aucun membre assigné
                </p>
            <?php endif; ?>
        </div>
        
        <!-- Publications -->
        <div class="rapport-section">
            <h2>Publications liées</h2>
            
            <?php if (!empty($publications)): ?>
                <?php foreach ($publications as $index => $pub): ?>
                    <div class="publication-item">
                        <div class="publication-title">
                            [<?= $index + 1 ?>] <?= e($pub['titre']) ?>
                        </div>
                        <div class="publication-meta">
                            <?= e($pub['type_publication'] ?? 'Article') ?> • 
                            <?= format_date($pub['date_publication']) ?>
                            <?php if (!empty($pub['doi'])): ?>
                                • DOI: <?= e($pub['doi']) ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="color: #9CA3AF; text-align: center; padding: 20px;">
                    Aucune publication liée à ce projet
                </p>
            <?php endif; ?>
        </div>
        
        <!-- Pied de page -->
        <div style="margin-top: 50px; padding-top: 20px; border-top: 2px solid #E5E7EB; text-align: center; color: #9CA3AF; font-size: 12px;">
            <p>Rapport généré automatiquement le <?= date('d/m/Y à H:i') ?></p>
            <p>Système de Gestion de Laboratoire - LRI Lab</p>
        </div>
    </div>
    
    <script>
        // Auto-print si demandé
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('auto_print') === '1') {
            window.onload = function() {
                window.print();
            };
        }
    </script>
</body>
</html>

<?php ViewComponents::renderFooter(); ?>