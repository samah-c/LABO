<?php
/**
 * EvenementsListView.php - Vue liste des événements
 */
require_once __DIR__ . '/../../../lib/components/HeaderComponent.php';
require_once __DIR__ . '/../../../lib/components/NavigationComponent.php';  
require_once __DIR__ . '/../../../lib/components/PageHeaderComponent.php';
require_once __DIR__ . '/../../../lib/components/TableComponent.php';
require_once __DIR__ . '/../../../lib/components/FilterComponent.php';
require_once __DIR__ . '/../../../lib/components/FooterComponent.php';
require_once __DIR__ . '/../../../lib/components/ModalComponent.php';

class EvenementsListView
{
    private array $evenements;
    private ?array $pagination;

    public function __construct(array $evenements, ?array $pagination = null)
    {
        $this->evenements = $evenements;
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
            'title' => 'Gestion des Événements',
            'username' => session('username'),
            'role' => 'admin',
            'additionalJs' => [
                base_url('assets/js/admin/evenements-handler.js')
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
            ['label' => 'Événements']
        ]);
    }

    /**
     * Rendu de l'en-tête de page
     */
    private function renderPageHeader(): void
    {
        PageHeaderComponent::render([
            'title' => 'Événements Scientifiques',
            'actions' => [
                [
                    'type' => 'button',
                    'label' => 'Nouvel événement',
                    'onclick' => 'openAddModal()',
                    'class' => 'btn-primary'
                ],
                [
                    'type' => 'button',
                    'label' => 'Exporter',
                    'onclick' => 'exportData()'
                ]
            ]
        ]);
    }

    /**
     * Rendu des filtres
     */
    private function renderFilters(): void
    {
        FilterComponent::render([
            'action' => base_url('admin/evenements/evenements'),
            'showSearch' => true,
            'searchPlaceholder' => 'Rechercher un événement...',
            'filters' => [
                [
                    'type' => 'select',
                    'name' => 'type_evenement',
                    'label' => 'Type',
                    'options' => [
                        'conference' => 'Conférence',
                        'atelier' => 'Atelier',
                        'seminaire' => 'Séminaire',
                        'soutenance' => 'Soutenance',
                        'autre' => 'Autre'
                    ],
                    'defaultLabel' => 'Tous les types'
                ],
                [
                    'type' => 'select',
                    'name' => 'statut',
                    'label' => 'Statut',
                    'options' => [
                        'a_venir' => 'À venir',
                        'termine' => 'Terminé'
                    ],
                    'defaultLabel' => 'Tous les statuts'
                ]
            ]
        ]);
    }

    /**
     * Rendu de la table
     */
    private function renderTable(): void
    {
        TableComponent::render([
            'data' => $this->evenements,
            'columns' => [
                [
                    'key' => 'titre',
                    'label' => 'Titre',
                    'formatter' => function($value) {
                        return '<strong>' . htmlspecialchars($value) . '</strong>';
                    }
                ],
                [
                    'key' => 'type_evenement',
                    'label' => 'Type',
                    'formatter' => function($value) {
                        $colors = [
                            'conference' => '#3B82F6',
                            'atelier' => '#8B5CF6',
                            'seminaire' => '#10B981',
                            'soutenance' => '#F59E0B',
                            'autre' => '#6B7280'
                        ];
                        $labels = [
                            'conference' => 'Conférence',
                            'atelier' => 'Atelier',
                            'seminaire' => 'Séminaire',
                            'soutenance' => 'Soutenance',
                            'autre' => 'Autre'
                        ];
                        $color = $colors[$value] ?? '#6B7280';
                        $label = $labels[$value] ?? $value;
                        return '<span class="badge" style="background: ' . $color . ';">' . htmlspecialchars($label) . '</span>';
                    }
                ],
                [
                    'key' => 'date_evenement',
                    'label' => 'Date',
                    'formatter' => function($value) {
                        return date('d/m/Y H:i', strtotime($value));
                    }
                ],
                [
                    'key' => 'organisateur_nom',
                    'label' => 'Organisateur',
                    'formatter' => function($value) {
                        return htmlspecialchars($value ?? 'Non défini');
                    }
                ],
                [
                    'key' => 'lieu',
                    'label' => 'Lieu',
                    'formatter' => function($value) {
                        return htmlspecialchars($value ?? '-');
                    }
                ],
                [
                    'key' => 'statut',
                    'label' => 'Statut',
                    'formatter' => function($value, $row) {
                        $isUpcoming = strtotime($row['date_evenement']) > time();
                        if ($isUpcoming) {
                            return '<span class="badge badge-info">À venir</span>';
                        } else {
                            return '<span class="badge badge-secondary">Terminé</span>';
                        }
                    }
                ]
            ],
            'actions' => [
                function($row) {
                    return '<button class="btn-action btn-view" 
                                    onclick="viewItem(' . $row['id'] . ')" 
                                    title="Voir détails">
                                 voir
                            </button>';
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
            ],
            'emptyMessage' => 'Aucun événement trouvé'
        ]);
    }

    /**
     * Rendu de la pagination
     */
    private function renderPagination(): void
    {
        if ($this->pagination) {
            echo Utils::renderPagination($this->pagination, base_url('admin/evenements/evenements'));
        }
    }

    /**
     * Rendu de la modale
     */
    private function renderModal(): void
    {
        ModalComponent::render([
            'id' => 'evenement-modal',
            'title' => 'Ajouter un événement',
            'content' => '<div id="modal-form-container"></div>',
            'size' => 'medium'
        ]);
    }

    /**
     * Rendu des styles
     */
    private function renderStyles(): void
    {
        ?>
        <style>
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            color: white;
        }

        .badge-info {
            background: #3B82F6;
        }

        .badge-secondary {
            background: #6B7280;
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