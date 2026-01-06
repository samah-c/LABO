<?php
/**
 * Vue de l'historique des réservations d'équipements
 */
require_once __DIR__ . '/../../../lib/components/HeaderComponent.php';
require_once __DIR__ . '/../../../lib/components/NavigationComponent.php';  
require_once __DIR__ . '/../../../lib/components/PageHeaderComponent.php';
require_once __DIR__ . '/../../../lib/components/TableComponent.php';
require_once __DIR__ . '/../../../lib/components/FilterComponent.php';
require_once __DIR__ . '/../../../lib/components/FooterComponent.php';

class EquipementsHistoryView
{
    private array $creneaux;
    private ?array $equipement;
    private ?array $pagination;

    public function __construct(array $creneaux, ?array $equipement = null, ?array $pagination = null)
    {
        $this->creneaux = $creneaux;
        $this->equipement = $equipement;
        $this->pagination = $pagination;
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
        $this->renderFilters();
        $this->renderReservationsList();
        $this->renderPagination();
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
            'title' => 'Historique des réservations',
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
        $breadcrumbs = [
            ['label' => 'Dashboard', 'url' => base_url('admin/dashboard')],
            ['label' => 'Équipements', 'url' => base_url('admin/equipements/equipements')]
        ];

        if ($this->equipement) {
            $breadcrumbs[] = [
                'label' => htmlspecialchars($this->equipement['nom']),
                'url' => base_url('admin/equipements/equipements/view/' . $this->equipement['id'])
            ];
        } else {
            $breadcrumbs[] = ['label' => 'Historique global'];
        }

        $breadcrumbs[] = ['label' => 'Historique'];

        NavigationComponent::renderBreadcrumbs($breadcrumbs);
    }

    /**
     * Rendu de l'en-tête de page
     */
    private function renderPageHeader(): void
    {
        $title = 'Historique des réservations';
        if ($this->equipement) {
            $title .= ' - ' . htmlspecialchars($this->equipement['nom']);
        }

        $actions = [];

        if ($this->equipement) {
            $actions[] = [
                'type' => 'link',
                'label' => 'Voir l\'équipement',
                'url' => base_url('admin/equipements/equipements/view/' . $this->equipement['id'])
            ];
        }

        $actions[] = [
            'type' => 'link',
            'label' => 'Retour à la liste',
            'url' => base_url('admin/equipements/equipements')
        ];

        $actions[] = [
            'type' => 'button',
            'label' => 'Imprimer',
            'onclick' => 'window.print()'
        ];

        PageHeaderComponent::render([
            'title' => $title,
            'actions' => $actions
        ]);
    }

    /**
     * Rendu des cartes de statistiques
     */
    private function renderStatsCards(): void
    {
        $confirmes = count(array_filter($this->creneaux, fn($c) => $c['statut'] === 'confirme'));
        $enAttente = count(array_filter($this->creneaux, fn($c) => $c['statut'] === 'en_attente'));
        $annules = count(array_filter($this->creneaux, fn($c) => $c['statut'] === 'annule'));

        TableComponent::renderStatsCards([
            [
                'label' => 'Réservations totales',
                'value' => count($this->creneaux),
                'icon' => ''
            ],
            [
                'label' => 'Confirmées',
                'value' => $confirmes,
                'icon' => ''
            ],
            [
                'label' => 'En attente',
                'value' => $enAttente,
                'icon' => ''
            ],
            [
                'label' => 'Annulées',
                'value' => $annules,
                'icon' => ''
            ]
        ]);
    }

    /**
     * Rendu des filtres
     */
    private function renderFilters(): void
    {
        ?>
        <div class="card" style="margin-top: 24px;">
            <div class="card-header">
                <h2>🔍 Filtrer l'historique</h2>
            </div>
            <div class="card-body">
                <?php
                FilterComponent::render([
                    'action' => '',
                    'method' => 'GET',
                    'showSearch' => false,
                    'filters' => [
                        [
                            'type' => 'date',
                            'name' => 'date_debut',
                            'label' => 'Date début'
                        ],
                        [
                            'type' => 'date',
                            'name' => 'date_fin',
                            'label' => 'Date fin'
                        ],
                        [
                            'type' => 'select',
                            'name' => 'statut',
                            'label' => 'Statut',
                            'options' => [
                                'confirme' => 'Confirmé',
                                'en_attente' => 'En attente',
                                'annule' => 'Annulé',
                                'termine' => 'Terminé'
                            ],
                            'defaultLabel' => 'Tous les statuts'
                        ]
                    ]
                ]);
                ?>
            </div>
        </div>
        <?php
    }

    /**
     * Rendu de la liste des réservations
     */
    private function renderReservationsList(): void
    {
        ?>
        <div class="card" style="margin-top: 24px;">
            <div class="card-header">
                <h2>Réservations (<?= count($this->creneaux) ?>)</h2>
            </div>
            <div class="card-body">
                <?php
                $columns = $this->buildTableColumns();
                
                TableComponent::render([
                    'data' => $this->creneaux,
                    'columns' => $columns,
                    'actions' => [
                        function($row) {
                            return '<button class="btn-action btn-view" 
                                            onclick="voirDetails(' . $row['id'] . ')"
                                            title="Voir détails">
                                        voir
                                    </button>';
                        }
                    ],
                    'emptyMessage' => 'Aucune réservation trouvée'
                ]);
                ?>
            </div>
        </div>
        <?php
    }

    /**
     * Construction des colonnes du tableau
     */
    private function buildTableColumns(): array
    {
        $columns = [];

        // Colonne équipement (seulement si pas d'équipement spécifique)
        if (!$this->equipement) {
            $columns[] = [
                'key' => 'equipement_nom',
                'label' => 'Équipement',
                'formatter' => function($value, $row) {
                    $html = '<strong>' . htmlspecialchars($value) . '</strong>';
                    if (!empty($row['type_equipement'])) {
                        $html .= '<div style="font-size: 12px; color: #6B7280;">' 
                              . htmlspecialchars($row['type_equipement']) . '</div>';
                    }
                    return $html;
                }
            ];
        }

        // Colonnes communes
        $columns = array_merge($columns, [
            [
                'key' => 'membre_nom',
                'label' => 'Membre',
                'formatter' => function($value, $row) {
                    $html = '<strong>' . htmlspecialchars($value) . '</strong>';
                    if (!empty($row['membre_poste'])) {
                        $html .= '<div style="font-size: 12px; color: #6B7280;">' 
                              . htmlspecialchars($row['membre_poste']) . '</div>';
                    }
                    return $html;
                }
            ],
            [
                'key' => 'date_debut',
                'label' => 'Date début',
                'formatter' => function($value) {
                    return format_date($value, 'd/m/Y H:i');
                }
            ],
            [
                'key' => 'date_fin',
                'label' => 'Date fin',
                'formatter' => function($value) {
                    return format_date($value, 'd/m/Y H:i');
                }
            ],
            [
                'key' => 'date_debut',
                'label' => 'Durée',
                'formatter' => function($value, $row) {
                    $debut = new DateTime($row['date_debut']);
                    $fin = new DateTime($row['date_fin']);
                    $duree = $debut->diff($fin);
                    $heures = ($duree->days * 24) + $duree->h;
                    
                    if ($heures > 24) {
                        return $duree->days . ' jour(s)';
                    } else {
                        return $heures . 'h ' . $duree->i . 'min';
                    }
                }
            ],
            [
                'key' => 'motif',
                'label' => 'Motif',
                'formatter' => function($value) {
                    return '<div style="max-width: 200px; overflow: hidden; text-overflow: ellipsis;">' 
                         . htmlspecialchars($value ?? '-') . '</div>';
                }
            ],
            [
                'key' => 'statut',
                'label' => 'Statut',
                'formatter' => function($value) {
                    $badges = [
                        'confirme' => '<span class="badge badge-success">✓ Confirmé</span>',
                        'en_attente' => '<span class="badge badge-warning">⏳ En attente</span>',
                        'annule' => '<span class="badge badge-danger">✗ Annulé</span>',
                        'termine' => '<span class="badge badge-secondary">✓ Terminé</span>'
                    ];
                    return $badges[$value] ?? '<span class="badge">' . htmlspecialchars($value) . '</span>';
                }
            ]
        ]);

        return $columns;
    }

    /**
     * Rendu de la pagination
     */
    private function renderPagination(): void
    {
        if ($this->pagination) {
            $url = $this->equipement 
                ? base_url('admin/equipements/equipements/historique/' . $this->equipement['id']) 
                : base_url('admin/equipements/equipements/historique');
            
            echo Utils::renderPagination($this->pagination, $url);
        }
    }

    /**
     * Rendu des styles CSS
     */
    private function renderStyles(): void
    {
        ?>
        <style>
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