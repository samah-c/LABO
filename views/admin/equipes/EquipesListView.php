<?php
/**
 * Vue de la liste des équipes
 */
require_once __DIR__ . '/../../../lib/components/HeaderComponent.php';
require_once __DIR__ . '/../../../lib/components/NavigationComponent.php';  
require_once __DIR__ . '/../../../lib/components/PageHeaderComponent.php';
require_once __DIR__ . '/../../../lib/components/TableComponent.php';
require_once __DIR__ . '/../../../lib/components/FilterComponent.php';
require_once __DIR__ . '/../../../lib/components/FooterComponent.php';
require_once __DIR__ . '/../../../lib/components/ModalComponent.php';

class EquipesListView
{
    private array $equipes;
    private ?array $pagination;

    public function __construct(array $equipes, ?array $pagination = null)
    {
        $this->equipes = $equipes;
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
            'title' => 'Gestion des Équipes',
            'username' => session('username'),
            'role' => 'admin',
            'additionalJs' => [
                base_url('assets/js/ui.js'),
                base_url('assets/js/table-enhancements.js'),
                base_url('assets/js/admin/equipes-handler.js')
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
            ['label' => 'Équipes']
        ]);
    }

    /**
     * Rendu de l'en-tête de page
     */
    private function renderPageHeader(): void
    {
        PageHeaderComponent::render([
            'title' => 'Équipes de Recherche',
            'actions' => [
                [
                    'type' => 'button',
                    'label' => 'Nouvelle équipe',
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
            'action' => base_url('admin/equipes/equipes'),
            'showSearch' => true,
            'searchPlaceholder' => 'Rechercher une équipe...',
            'filters' => [
                [
                    'type' => 'select',
                    'name' => 'domaine',
                    'label' => 'Domaine',
                    'options' => [
                        'Intelligence Artificielle' => 'Intelligence Artificielle',
                        'Sécurité' => 'Sécurité',
                        'Cloud' => 'Cloud',
                        'Réseaux' => 'Réseaux',
                        'Systèmes Embarqués' => 'Systèmes Embarqués',
                        'Big Data' => 'Big Data'
                    ],
                    'defaultLabel' => 'Tous les domaines'
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
            'data' => $this->equipes,
            'columns' => [
                [
                    'key' => 'nom',
                    'label' => 'Nom de l\'équipe',
                    'formatter' => function($value) {
                        return '<strong>' . htmlspecialchars($value) . '</strong>';
                    }
                ],
                [
                    'key' => 'chef_nom',
                    'label' => 'Chef d\'équipe',
                    'formatter' => function($value) {
                        return $value ? htmlspecialchars($value) : '<em style="color: #9CA3AF;">Non assigné</em>';
                    }
                ],
                [
                    'key' => 'nb_membres',
                    'label' => 'Membres',
                    'formatter' => function($value) {
                        $count = intval($value);
                        $badge_class = $count > 0 ? 'badge-blue' : 'badge-gray';
                        return '<span class="badge ' . $badge_class . '">' . $count . ' membre' . ($count > 1 ? 's' : '') . '</span>';
                    }
                ],
                [
                    'key' => 'domaine',
                    'label' => 'Domaine',
                    'formatter' => function($value) {
                        return htmlspecialchars($value);
                    }
                ],
                [
                    'key' => 'date_creation',
                    'label' => 'Création',
                    'formatter' => function($value) {
                        return format_date($value);
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
            'emptyMessage' => 'Aucune équipe trouvée'
        ]);
    }

    /**
     * Rendu de la pagination
     */
    private function renderPagination(): void
    {
        if ($this->pagination) {
            echo Utils::renderPagination($this->pagination, base_url('admin/equipes/equipes'));
        }
    }

    /**
     * Rendu de la modale
     */
    private function renderModal(): void
    {
        ModalComponent::render([
            'id' => 'equipe-modal',
            'title' => 'Ajouter une équipe',
            'content' => '<div id="modal-form-container"></div>',
            'size' => 'medium'
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