<?php
/**
 * Vue de génération de rapports d'utilisation des équipements
 */
require_once __DIR__ . '/../../../lib/components/HeaderComponent.php';
require_once __DIR__ . '/../../../lib/components/NavigationComponent.php';  
require_once __DIR__ . '/../../../lib/components/PageHeaderComponent.php';
require_once __DIR__ . '/../../../lib/components/TableComponent.php';
require_once __DIR__ . '/../../../lib/components/FilterComponent.php';
require_once __DIR__ . '/../../../lib/components/FooterComponent.php';
require_once __DIR__ . '/../../../lib/components/FormComponent.php';

class EquipementsReportView
{
    private string $dateDebut;
    private string $dateFin;
    private int $nbReservations;
    private array $statsParMembre;
    private array $tauxOccupation;

    public function __construct(
        string $dateDebut,
        string $dateFin,
        int $nbReservations,
        array $statsParMembre,
        array $tauxOccupation
    ) {
        $this->dateDebut = $dateDebut;
        $this->dateFin = $dateFin;
        $this->nbReservations = $nbReservations;
        $this->statsParMembre = $statsParMembre;
        $this->tauxOccupation = $tauxOccupation;
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
        $this->renderPeriodSelector();
        $this->renderReportHeader();
        $this->renderGlobalStats();
        $this->renderOccupationRate();
        $this->renderTopUsers();
        $this->renderDetailedStats();
        echo '</div>';
        $this->renderStyles();
        $this->renderScript();
        $this->renderFooter();
    }

    /**
     * Rendu de l'en-tête
     */
    private function renderHeader(): void
    {
        HeaderComponent::render([
            'title' => 'Rapport d\'utilisation',
            'username' => session('username'),
            'role' => 'admin',
            'additionalJs' => [
                'https://code.jquery.com/jquery-3.6.0.min.js',
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
            ['label' => 'Rapport d\'utilisation']
        ]);
    }

    /**
     * Rendu de l'en-tête de page
     */
    private function renderPageHeader(): void
    {
        PageHeaderComponent::render([
            'title' => 'Rapport d\'utilisation des équipements',
            'actions' => [

                [
                    'type' => 'button',
                    'label' => 'Exporter en PDF',
                    'onclick' => 'exportRapport()'
                ]
            ]
        ]);
    }

    /**
     * Rendu du sélecteur de période
     */
    private function renderPeriodSelector(): void
    {
        ?>
        <div class="card" style="margin-bottom: 24px;">
            <div class="card-header">
                <h2>Période du rapport</h2>
            </div>
            <div class="card-body">
                <?php
                FormComponent::render([
                    'action' => '',
                    'method' => 'GET',
                    'submitText' => 'Générer le rapport',
                    'cancelUrl' => null,
                    'formClass' => 'rapport-form',
                    'fields' => [
                        [
                            'type' => 'date',
                            'name' => 'date_debut',
                            'label' => 'Date de début',
                            'value' => $this->dateDebut,
                            'required' => true,
                            'attributes' => ['max' => date('Y-m-d')]
                        ],
                        [
                            'type' => 'date',
                            'name' => 'date_fin',
                            'label' => 'Date de fin',
                            'value' => $this->dateFin,
                            'required' => true,
                            'attributes' => ['max' => date('Y-m-d')]
                        ]
                    ]
                ]);
                ?>
            </div>
        </div>
        <?php
    }

    /**
     * Rendu de l'en-tête du rapport
     */
    private function renderReportHeader(): void
    {
        ?>
        <div class="rapport-header">
            <h2>Période: <?= format_date($this->dateDebut, 'd/m/Y') ?> - <?= format_date($this->dateFin, 'd/m/Y') ?></h2>
            <p>Rapport généré le <?= date('d/m/Y à H:i') ?></p>
        </div>
        <?php
    }

    /**
     * Rendu des statistiques globales
     */
    private function renderGlobalStats(): void
    {
        $heuresTotal = 0;
        if (!empty($this->statsParMembre)) {
            foreach ($this->statsParMembre as $stat) {
                $heuresTotal += $stat['heures_totales'] ?? 0;
            }
        }

        TableComponent::renderStatsCards([
            [
                'label' => 'Réservations totales',
                'value' => $this->nbReservations
            ],
            [
                'label' => 'Utilisateurs actifs',
                'value' => count($this->statsParMembre)
            ],
            [
                'label' => 'Équipements suivis',
                'value' => count($this->tauxOccupation)
            ],
            [
                'label' => 'Heures d\'utilisation',
                'value' => round($heuresTotal, 0)
            ]
        ]);
    }

    /**
     * Rendu du taux d'occupation par équipement
     */
    private function renderOccupationRate(): void
    {
        ?>
        <div class="card" style="margin-top: 24px;">
            <div class="card-header">
                <h2>Taux d'occupation par équipement</h2>
            </div>
            <div class="card-body">
                <?php if (!empty($this->tauxOccupation) && is_array($this->tauxOccupation)): ?>
                    <div class="occupation-list">
                        <?php
                        // Créer une copie pour le tri
                        $tauxOccupationSorted = $this->tauxOccupation;
                        usort($tauxOccupationSorted, function($a, $b) {
                            return ($b['taux'] ?? 0) <=> ($a['taux'] ?? 0);
                        });
                        
                        foreach ($tauxOccupationSorted as $item):
                            $taux = $item['taux'] ?? 0;
                            $color = $taux >= 75 ? '#EF4444' : ($taux >= 50 ? '#F59E0B' : '#10B981');
                        ?>
                            <div class="occupation-item">
                                <div class="occupation-info">
                                    <strong><?= htmlspecialchars($item['nom'] ?? 'N/A') ?></strong>
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
                <?php else: ?>
                    <p style="text-align: center; color: #9CA3AF; padding: 40px;">
                        Aucune donnée d'occupation disponible pour cette période
                    </p>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Rendu du top utilisateurs
     */
    private function renderTopUsers(): void
    {
        ?>
        <div class="card" style="margin-top: 24px;">
            <div class="card-header">
                <h2>Top utilisateurs</h2>
            </div>
            <div class="card-body">
                <?php if (!empty($this->statsParMembre) && is_array($this->statsParMembre)): ?>
                    <div class="top-users-list">
                        <?php
                        $top = array_slice($this->statsParMembre, 0, 10);
                        $position = 1;
                        $maxReservations = !empty($this->statsParMembre) ? max(array_column($this->statsParMembre, 'nb_reservations')) : 1;
                        
                        foreach ($top as $stat):
                            $nbReservations = $stat['nb_reservations'] ?? 0;
                            $heures = $stat['heures_totales'] ?? 0;
                        ?>
                            <div class="top-user-item">
                                <div class="top-user-position">
                                    <?= $position ?>
                                </div>
                                <div class="top-user-info">
                                    <strong><?= htmlspecialchars($stat['username'] ?? 'N/A') ?></strong>
                                    <div style="color: #6B7280; font-size: 14px; margin-top: 4px;">
                                        <?= $nbReservations ?> réservation(s) - 
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
                <?php else: ?>
                    <p style="text-align: center; color: #9CA3AF; padding: 40px;">
                        Aucune réservation pour cette période
                    </p>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Rendu des statistiques détaillées
     */
    private function renderDetailedStats(): void
    {
        if (empty($this->statsParMembre) || !is_array($this->statsParMembre)) {
            return;
        }
        ?>
        <div class="card" style="margin-top: 24px;">
            <div class="card-header">
                <h2>Détails par utilisateur</h2>
            </div>
            <div class="card-body">
                <?php
                $tableData = array_map(function($stat) {
                    $nbRes = $stat['nb_reservations'] ?? 0;
                    $heures = $stat['heures_totales'] ?? 0;
                    $moyenne = $nbRes > 0 ? ($heures / $nbRes) : 0;
                    
                    return [
                        'username' => $stat['username'] ?? 'N/A',
                        'nb_reservations' => $nbRes,
                        'heures_totales' => $heures,
                        'moyenne' => $moyenne
                    ];
                }, $this->statsParMembre);
                
                TableComponent::render([
                    'data' => $tableData,
                    'columns' => [
                        [
                            'key' => 'username',
                            'label' => 'Utilisateur',
                            'formatter' => function($value) {
                                return '<strong>' . htmlspecialchars($value) . '</strong>';
                            }
                        ],
                        [
                            'key' => 'nb_reservations',
                            'label' => 'Nombre de réservations'
                        ],
                        [
                            'key' => 'heures_totales',
                            'label' => 'Heures totales',
                            'formatter' => function($value) {
                                return number_format($value, 1) . 'h';
                            }
                        ],
                        [
                            'key' => 'moyenne',
                            'label' => 'Moyenne par réservation',
                            'formatter' => function($value) {
                                return number_format($value, 1) . 'h';
                            }
                        ]
                    ],
                    'emptyMessage' => 'Aucune donnée disponible'
                ]);
                ?>
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
        .rapport-form {
            max-width: 800px;
        }

        .rapport-form .form-group {
            display: inline-block;
            width: 48%;
            margin-right: 2%;
        }

        .rapport-form .form-actions {
            margin-top: 20px;
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
            color: #5B7FFF;
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
            .rapport-form .form-group {
                width: 100%;
                margin-right: 0;
            }
        }
        </style>
        <?php
    }

    /**
     * Rendu du script JavaScript
     */
    private function renderScript(): void
    {
        ?>
        <script>
        function exportRapport() {
            const dateDebut = document.querySelector('input[name="date_debut"]').value;
            const dateFin = document.querySelector('input[name="date_fin"]').value;
            window.location.href = '<?= base_url("admin/equipements/equipements/export-pdf") ?>?date_debut=' + dateDebut + '&date_fin=' + dateFin;
        }
        </script>
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