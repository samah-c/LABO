<?php
/**
 * Vue du tableau de bord des équipements
 */
require_once __DIR__ . '/../../../lib/components/HeaderComponent.php';
require_once __DIR__ . '/../../../lib/components/NavigationComponent.php';  
require_once __DIR__ . '/../../../lib/components/PageHeaderComponent.php';
require_once __DIR__ . '/../../../lib/components/TableComponent.php';
require_once __DIR__ . '/../../../lib/components/FilterComponent.php';
require_once __DIR__ . '/../../../lib/components/FooterComponent.php';

class EquipementsDashboardView
{
    private array $stats;
    private array $libres;
    private array $reserves;
    private array $maintenance;
    private array $conflits;

    public function __construct(
        array $stats,
        array $libres,
        array $reserves,
        array $maintenance,
        array $conflits = []
    ) {
        $this->stats = $stats;
        $this->libres = $libres;
        $this->reserves = $reserves;
        $this->maintenance = $maintenance;
        $this->conflits = $conflits;
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
        $this->renderStatsCards();
        $this->renderTypeDistribution();
        echo '<div class="grid-2-cols">';
        $this->renderStateDistribution();
        $this->renderTeamDistribution();
        echo '</div>';
        $this->renderConflicts();
        $this->renderMaintenanceList();
        echo '</div>';
        $this->renderStyles();
        $this->renderFooter();
    }

    /**
     * Rendu de l'en-tête
     */
    private function renderHeader(): void
    {
        HeaderComponent::render([
            'title' => 'Tableau de bord des équipements',
            'username' => session('username'),
            'role' => 'admin',
            'additionalJs' => [
                base_url('assets/js/ui.js'),
                base_url('assets/js/admin/equipements-handler.js')
            ]
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
            ['label' => 'Équipements', 'url' => base_url('admin/equipements/equipements')],
            ['label' => 'Tableau de bord']
        ]);
    }

    /**
     * Rendu de l'en-tête de page
     */
    private function renderPageHeader(): void
    {
        PageHeaderComponent::render([
            'title' => 'Tableau de bord des équipements',
            'actions' => [
                [
                    'type' => 'link',
                    'label' => 'Générer un rapport',
                    'url' => base_url('admin/equipements/equipements/rapport')
                ],
                [
                    'type' => 'link',
                    'label' => 'Historique complet',
                    'url' => base_url('admin/equipements/equipements/historique')
                ]
            ]
        ]);
    }

    /**
     * Rendu des cartes de statistiques
     */
    private function renderStatsCards(): void
    {
        TableComponent::renderStatsCards([
            [
                'label' => 'Équipements totaux',
                'value' => $this->stats['total']
            ],
            [
                'label' => 'Équipements libres',
                'value' => count($this->libres)
            ],
            [
                'label' => 'Équipements réservés',
                'value' => count($this->reserves)
            ],
            [
                'label' => 'En maintenance',
                'value' => count($this->maintenance)
            ]
        ]);
    }

    /**
     * Rendu de la répartition par type
     */
    private function renderTypeDistribution(): void
    {
        ?>
        <div class="card" style="margin-top: 24px;">
            <div class="card-header">
                <h2>Répartition par type</h2>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <?php foreach ($this->stats['par_type'] as $type): ?>
                        <div class="chart-bar-item">
                            <div class="chart-bar-label"><?= htmlspecialchars($type['type_equipement']) ?></div>
                            <div class="chart-bar-wrapper">
                                <div class="chart-bar" 
                                     style="width: <?= ($type['count'] / $this->stats['total']) * 100 ?>%; background: #5B7FFF;">
                                    <span class="chart-bar-value"><?= $type['count'] ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Rendu de la répartition par état
     */
    private function renderStateDistribution(): void
    {
        $colors = [
            'libre' => '#10B981',
            'reserve' => '#3B82F6',
            'en_maintenance' => '#F59E0B',
            'hors_service' => '#EF4444'
        ];
        ?>
        <div class="card">
            <div class="card-header">
                <h2>Répartition par état</h2>
            </div>
            <div class="card-body">
                <div class="pie-chart-legend">
                    <?php foreach ($this->stats['par_etat'] as $etat): ?>
                        <div class="legend-item">
                            <div class="legend-color" style="background: <?= $colors[$etat['etat']] ?? '#6B7280' ?>"></div>
                            <div class="legend-label"><?= htmlspecialchars($etat['etat']) ?></div>
                            <div class="legend-value"><?= $etat['count'] ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Rendu de la répartition par équipe
     */
    private function renderTeamDistribution(): void
    {
        ?>
        <div class="card">
            <div class="card-header">
                <h2>Répartition par équipe</h2>
            </div>
            <div class="card-body">
                <div class="pie-chart-legend">
                    <?php foreach ($this->stats['par_equipe'] as $equipe): ?>
                        <div class="legend-item">
                            <div class="legend-color" style="background: #5B7FFF"></div>
                            <div class="legend-label"><?= htmlspecialchars($equipe['nom'] ?: 'Non assigné') ?></div>
                            <div class="legend-value"><?= $equipe['count'] ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Rendu des conflits de réservation
     */
    private function renderConflicts(): void
    {
        if (empty($this->conflits)) {
            return;
        }
        ?>
        <div class="card" style="margin-top: 24px;">
            <div class="card-header">
                <h2>Conflits de réservation détectés</h2>
            </div>
            <div class="card-body">
                <div class="alert alert-warning">
                    <strong>Attention!</strong> <?= count($this->conflits) ?> conflit(s) de réservation détecté(s).
                </div>
                <div class="conflicts-list">
                    <?php foreach ($this->conflits as $conflit): ?>
                        <div class="conflict-item">
                            <div class="conflict-info">
                                <strong><?= htmlspecialchars($conflit['equipement_nom']) ?></strong>
                                <div style="color: #6B7280; font-size: 14px; margin-top: 4px;">
                                    Conflit entre <strong><?= htmlspecialchars($conflit['membre1']) ?></strong> 
                                    et <strong><?= htmlspecialchars($conflit['membre2']) ?></strong>
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
        <?php
    }

    /**
     * Rendu de la liste des équipements en maintenance
     */
    private function renderMaintenanceList(): void
    {
        if (empty($this->maintenance)) {
            return;
        }
        ?>
        <div class="card" style="margin-top: 24px;">
            <div class="card-header">
                <h2>Équipements en maintenance</h2>
            </div>
            <div class="card-body">
                <div class="maintenance-list">
                    <?php foreach ($this->maintenance as $eq): ?>
                        <div class="maintenance-item">
                            <div class="maintenance-info">
                                <strong><?= htmlspecialchars($eq['nom']) ?></strong>
                                <div style="color: #6B7280; font-size: 14px; margin-top: 4px;">
                                    <?= htmlspecialchars($eq['type_equipement']) ?> 
                                    <?php if (!empty($eq['localisation'])): ?>
                                       📍 <?= htmlspecialchars($eq['localisation']) ?>
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
        <?php
    }

    /**
     * Rendu des styles CSS
     */
    private function renderStyles(): void
    {
        ?>
        <style>
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

        .conflict-info, .maintenance-info {
            flex: 1;
        }

        .btn-sm {
            padding: 6px 14px;
            font-size: 13px;
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
}