<?php
/**
 * Vue de la liste des publications
 */
require_once __DIR__ . '/../../../lib/components/HeaderComponent.php';
require_once __DIR__ . '/../../../lib/components/NavigationComponent.php';  
require_once __DIR__ . '/../../../lib/components/PageHeaderComponent.php';
require_once __DIR__ . '/../../../lib/components/TableComponent.php';
require_once __DIR__ . '/../../../lib/components/FilterComponent.php';
require_once __DIR__ . '/../../../lib/components/FooterComponent.php';
require_once __DIR__ . '/../../../lib/components/ModalComponent.php';

class PublicationsListView
{
    private array $publications;
    private array $types;
    private array $annees;
    private array $domaines;
    private array $projets;
    private ?array $pagination;

    public function __construct(
        array $publications,
        array $types = [],
        array $annees = [],
        array $domaines = [],
        array $projets = [],
        ?array $pagination = null
    ) {
        $this->publications = $publications;
        $this->types = $types;
        $this->annees = $annees;
        $this->domaines = $domaines;
        $this->projets = $projets;
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
        $this->renderTable();
        $this->renderPagination();
        echo '</div>';
        $this->renderModal();
        $this->renderStyles();
        $this->renderFooter();
    }

    /**
     * Rendu de l'en-tête
     */
    private function renderHeader(): void
    {
        HeaderComponent::render([
            'title' => 'Gestion des Publications',
            'username' => session('username'),
            'role' => 'admin',
            'additionalJs' => [
                base_url('assets/js/admin/publications-handler.js')
            ]
        ]);
    }

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
            ['label' => 'Publications']
        ]);
    }

    /**
     * Rendu de l'en-tête de page
     */
    private function renderPageHeader(): void
    {
        PageHeaderComponent::render([
            'title' => ' Publications',
            'actions' => [
                [
                    'type' => 'button',
                    'label' => ' Nouvelle publication',
                    'onclick' => 'openAddModal()',
                    'class' => 'btn-primary'
                ],
                [
                    'type' => 'button',
                    'label' => ' Rapport bibliographique',
                    'onclick' => 'genererRapport()'
                ],
                [
                    'type' => 'button',
                    'label' => ' Exporter CSV',
                    'onclick' => 'exportData()'
                ]
            ]
        ]);
    }

    /**
     * Rendu des cartes de statistiques
     */
    private function renderStatsCards(): void
    {
        $valides = count(array_filter($this->publications, fn($p) => 
            ($p['statut_validation'] ?? 'en_attente') === 'valide'
        ));
        $enAttente = count(array_filter($this->publications, fn($p) => 
            ($p['statut_validation'] ?? 'en_attente') === 'en_attente'
        ));
        $rejetes = count(array_filter($this->publications, fn($p) => 
            ($p['statut_validation'] ?? 'en_attente') === 'rejete'
        ));
        ?>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-details">
                    <div class="stat-value"><?= count($this->publications) ?></div>
                    <div class="stat-label">Total publications</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-details">
                    <div class="stat-value"><?= $valides ?></div>
                    <div class="stat-label">Validées</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-details">
                    <div class="stat-value"><?= $enAttente ?></div>
                    <div class="stat-label">En attente</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-details">
                    <div class="stat-value"><?= $rejetes ?></div>
                    <div class="stat-label">Rejetées</div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Rendu des filtres
     */
    private function renderFilters(): void
    {
        $filterOptions = [
            [
                'name' => 'type',
                'label' => 'Type',
                'options' => array_combine($this->types, $this->types)
            ],
            [
                'name' => 'annee',
                'label' => 'Année',
                'options' => array_combine($this->annees, $this->annees)
            ],
            [
                'name' => 'domaine',
                'label' => 'Domaine',
                'options' => array_combine($this->domaines, $this->domaines)
            ],
            [
                'name' => 'statut',
                'label' => 'Statut',
                'options' => [
                    'en_attente' => 'En attente',
                    'valide' => 'Validé',
                    'rejete' => 'Rejeté'
                ]
            ]
        ];
        
        
        FilterComponent::render([
            'showSearch' => true,
            'searchPlaceholder' => 'Rechercher une publication...',
            'filters' => $filterOptions
        ]);
    }

    /**
     * Rendu du tableau
     */
    private function renderTable(): void
    {
        $columns = $this->buildTableColumns();
        $actions = $this->buildTableActions();
        
        TableComponent::render([
            'data' => $this->publications,
            'columns' => $columns,
            'actions' => $actions,
            'emptyMessage' => 'Aucune publication trouvée'
        ]);
    }

    /**
     * Construction des colonnes du tableau
     */
    private function buildTableColumns(): array
    {
        $columns = [
            [
                'key' => 'titre',
                'label' => 'Titre',
                'formatter' => function($value, $row) {
                    $statut = $row['statut_validation'] ?? 'en_attente';
                    return '<div><strong>' . e($value) . '</strong><br><small style="color: #6B7280;">'  . ucfirst(str_replace('_', ' ', $statut)) . '</small></div>';
                }
            ],
            [
                'key' => 'type_publication',
                'label' => 'Type',
                'formatter' => function($value) {
                    $colors = [
                        'Article' => '#3B82F6',
                        'Conférence' => '#8B5CF6',
                        'Thèse' => '#EC4899',
                        'Rapport' => '#F59E0B',
                        'Livre' => '#10B981',
                        'Chapitre' => '#6366F1'
                    ];
                    $color = $colors[$value] ?? '#6B7280';
                    return '<span class="badge" style="background: ' . $color . ';">' . e($value) . '</span>';
                }
            ],
            [
                'key' => 'date_publication',
                'label' => 'Date',
                'formatter' => function($value) {
                    return date('d/m/Y', strtotime($value));
                }
            ],
            [
                'key' => 'auteurs',
                'label' => 'Auteurs',
                'formatter' => function($value, $row) {
                    $auteurs = $row['auteurs_noms'] ?? 'Non défini';
                    if (strlen($auteurs) > 50) {
                        return '<span title="' . e($auteurs) . '">' . e(substr($auteurs, 0, 50)) . '...</span>';
                    }
                    return e($auteurs);
                }
            ],
            [
                'key' => 'domaine',
                'label' => 'Domaine',
                'formatter' => function($value) {
                    return $value ? '<span class="badge badge-secondary">' . e($value) . '</span>' : '-';
                }
            ]
        ];
        
        if (!empty($this->publications[0]['doi'])) {
            $columns[] = [
                'key' => 'doi',
                'label' => 'DOI',
                'formatter' => function($value) {
                    return $value ? '<code style="font-size: 11px;">' . e($value) . '</code>' : '-';
                }
            ];
        }
        
        return $columns;
    }

    /**
     * Construction des actions du tableau
     */
    private function buildTableActions(): array
    {
        return [
            function($row) {
                return '<button class="btn-action btn-view" 
                                onclick="viewItem(' . $row['id'] . ')" 
                                title="Voir les détails">
                             voir
                        </button>';
            },
            function($row) {
                $statut = $row['statut_validation'] ?? 'en_attente';
                if ($statut === 'en_attente') {
                    return '<button class="btn-action btn-success" 
                                    onclick="validerPublication(' . $row['id'] . ')" 
                                    title="Valider">
                                 valider
                            </button>';
                }
                return '';
            },
            function($row) {
                $statut = $row['statut_validation'] ?? 'en_attente';
                if ($statut === 'en_attente' || $statut === 'valide') {
                    return '<button class="btn-action btn-warning" 
                                    onclick="rejeterPublication(' . $row['id'] . ')" 
                                    title="Rejeter">
                                rejeter
                            </button>';
                }
                return '';
            },
            function($row) {
                return '<button class="btn-action btn-edit" 
                                onclick="editItem(' . $row['id'] . ')" 
                                title="Modifier">
                            ✏️
                        </button>';
            },
            function($row) {
                return '<button class="btn-action btn-delete" 
                                onclick="deleteItem(' . $row['id'] . ')" 
                                title="Supprimer">
                            🗑️
                        </button>';
            }
        ];
    }

    /**
     * Rendu de la pagination
     */
    private function renderPagination(): void
    {
        if ($this->pagination) {
            echo Utils::renderPagination($this->pagination, base_url('admin/publications'));
        }
    }

    /**
     * Rendu de la modale
     */
    private function renderModal(): void
    {
        ModalComponent::render([
            'id' => 'publication-modal',
            'title' => 'Ajouter une publication',
            'content' => '<div id="modal-form-container"></div>'
        ]);
    }

    /**
     * Rendu des styles CSS
     */
    private function renderStyles(): void
    {
        ?>
        <style>
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
        }

        .stat-details {
            flex: 1;
        }

        .stat-value {
            font-size: 28px;
            font-weight: 700;
            color: #1F2937;
        }

        .stat-label {
            font-size: 13px;
            color: #6B7280;
            margin-top: 4px;
        }

        .btn-success {
            background: #10B981;
            color: white;
        }

        .btn-success:hover {
            background: #059669;
        }

        .btn-warning {
            background: #F59E0B;
            color: white;
        }

        .btn-warning:hover {
            background: #D97706;
        }

        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            color: white;
        }

        .badge-secondary {
            background: #6B7280;
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr 1fr;
            }
            
            .page-actions {
                flex-direction: column;
                gap: 10px;
            }
            
            .page-actions button {
                width: 100%;
            }
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

