<?php
/**
 * Vue de la liste des équipements
 */
require_once __DIR__ . '/../../../lib/components/HeaderComponent.php';
require_once __DIR__ . '/../../../lib/components/NavigationComponent.php';  
require_once __DIR__ . '/../../../lib/components/PageHeaderComponent.php';
require_once __DIR__ . '/../../../lib/components/TableComponent.php';
require_once __DIR__ . '/../../../lib/components/FilterComponent.php';
require_once __DIR__ . '/../../../lib/components/FooterComponent.php';
require_once __DIR__ . '/../../../lib/components/ModalComponent.php';
class EquipementsListView
{
    private array $equipements;
    private ?array $pagination;

    public function __construct(array $equipements, ?array $pagination = null)
    {
        $this->equipements = $equipements;
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
        $this->renderFooter();
    }

    /**
     * Rendu de l'en-tête
     */
    private function renderHeader(): void
    {
        HeaderComponent::render([
            'title' => 'Gestion des Équipements',
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
            ['label' => 'Équipements']
        ]);
    }

    /**
     * Rendu de l'en-tête de page
     */
    private function renderPageHeader(): void
    {
        PageHeaderComponent::render([
            'title' => 'Équipements du Laboratoire',
            'actions' => [
                [
                    'type' => 'link',
                    'label' => 'Tableau de bord',
                    'url' => base_url('admin/equipements/equipements/dashboard')
                ],
                [
                    'type' => 'link',
                    'label' => 'Historique',
                    'url' => base_url('admin/equipements/equipements/historique')
                ],
                [
                    'type' => 'link',
                    'label' => 'Rapport',
                    'url' => base_url('admin/equipements/equipements/rapport')
                ],
                [
                    'type' => 'button',
                    'label' => 'Nouvel équipement',
                    'onclick' => 'equipements.openAddModal()',
                    'class' => 'btn-primary'
                ],
                [
                    'type' => 'button',
                    'label' => 'Exporter',
                    'onclick' => 'equipements.export()'
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
            'action' => base_url('admin/equipements/equipements'),
            'showSearch' => true,
            'searchPlaceholder' => 'Rechercher un équipement...',
            'filters' => [
                [
                    'type' => 'select',
                    'name' => 'type_equipement',
                    'label' => 'Type',
                    'options' => [
                        'Ordinateur' => 'Ordinateur',
                        'Serveur' => 'Serveur',
                        'Imprimante' => 'Imprimante',
                        'Scanner' => 'Scanner',
                        'Réseau' => 'Équipement réseau',
                        'Laboratoire' => 'Équipement de labo',
                        'robot' => 'Robot',
                        'salle' => 'Salle',
                        'Autre' => 'Autre'
                    ],
                    'defaultLabel' => 'Tous les types'
                ],
                [
                    'type' => 'select',
                    'name' => 'etat',
                    'label' => 'État',
                    'options' => [
                        'libre' => 'Libre',
                        'reserve' => 'Réservé',
                        'en_maintenance' => 'En maintenance',
                        'hors_service' => 'Hors service'
                    ],
                    'defaultLabel' => 'Tous les états'
                ],
                [
                    'type' => 'select',
                    'name' => 'localisation',
                    'label' => 'Localisation',
                    'options' => [
                        'Bâtiment A, 1er étage' => 'Bâtiment A, 1er étage',
                        'Salle serveurs' => 'Salle serveurs',
                        'Laboratoire robotique' => 'Laboratoire robotique',
                        'Bureau' => 'Bureau',
                        'Entrepôt' => 'Entrepôt'
                    ],
                    'defaultLabel' => 'Toutes les localisations'
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
            'data' => $this->equipements,
            'columns' => [
                [
                    'key' => 'nom',
                    'label' => 'Nom',
                    'formatter' => function($value) {
                        return '<strong>' . htmlspecialchars($value) . '</strong>';
                    }
                ],
                [
                    'key' => 'type_equipement',
                    'label' => 'Type',
                    'formatter' => function($value) {
                        return htmlspecialchars($value);
                    }
                ],
                [
                    'key' => 'numero_serie',
                    'label' => 'N° Série',
                    'formatter' => function($value) {
                        return '<code>' . htmlspecialchars($value ?? '-') . '</code>';
                    }
                ],
                [
                    'key' => 'localisation',
                    'label' => 'Localisation',
                    'formatter' => function($value) {
                        return $value ? htmlspecialchars($value) : '-';
                    }
                ],
                [
                    'key' => 'etat',
                    'label' => 'État',
                    'formatter' => function($value) {
                        $badges = [
                            'libre' => '<span class="badge badge-success">Libre</span>',
                            'reserve' => '<span class="badge badge-info">Réservé</span>',
                            'en_maintenance' => '<span class="badge badge-warning">Maintenance</span>',
                            'hors_service' => '<span class="badge badge-danger">Hors service</span>'
                        ];
                        return $badges[$value] ?? '<span class="badge badge-secondary">' . htmlspecialchars($value) . '</span>';
                    }
                ],
                [
                    'key' => 'equipe_nom',
                    'label' => 'Équipe',
                    'formatter' => function($value) {
                        return $value ? htmlspecialchars($value) : '-';
                    }
                ]
            ],
            'actions' => [
                function($row) {
                    return '<button class="btn-action btn-view" 
                                    onclick="equipements.view(' . $row['id'] . ')" 
                                    title="Voir détails">
                                    voir
                            </button>';
                },
                function($row) {
                    return '<button class="btn-action btn-edit" 
                                    onclick="equipements.edit(' . $row['id'] . ')"
                                    title="Modifier">
                                ✏️
                            </button>';
                },
                function($row) {
                    return '<button class="btn-action btn-delete" 
                                    onclick="equipements.delete(' . $row['id'] . ')"
                                    title="Supprimer">
                                🗑️
                            </button>';
                }
            ],
            'emptyMessage' => 'Aucun équipement trouvé'
        ]);
    }

    /**
     * Rendu de la pagination
     */
    private function renderPagination(): void
    {
        if ($this->pagination) {
            echo Utils::renderPagination($this->pagination, base_url('admin/equipements/equipements'));
        }
    }

    /**
     * Rendu de la modale
     */
    private function renderModal(): void
    {
        ModalComponent::render([
            'id' => 'equipement-modal',
            'title' => 'Ajouter un équipement',
            'content' => '<div id="modal-form-container"></div>',
            'size' => 'large'
        ]);
    }

    /**
     * Rendu du pied de page
     */
    private function renderFooter(): void
    {
        FooterComponent::render(['role' => 'admin']);
    }
}
