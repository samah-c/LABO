<?php
/**
 * Vue de la liste des utilisateurs
 */
require_once __DIR__ . '/../../../lib/components/HeaderComponent.php';
require_once __DIR__ . '/../../../lib/components/NavigationComponent.php';
require_once __DIR__ . '/../../../lib/components/PageHeaderComponent.php';
require_once __DIR__ . '/../../../lib/components/TableComponent.php';
require_once __DIR__ . '/../../../lib/components/FilterComponent.php';
require_once __DIR__ . '/../../../lib/components/FooterComponent.php';
require_once __DIR__ . '/../../../lib/components/ModalComponent.php';

class UsersListView
{
    private array $users;
    private ?array $pagination;

    public function __construct(array $users, ?array $pagination = null)
    {
        $this->users = $users;
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
            'title' => 'Gestion des Utilisateurs',
            'username' => session('username'),
            'role' => 'admin',
            'additionalJs' => [
                base_url('assets/js/ui.js'),
                base_url('assets/js/table-enhancements.js'),
                base_url('assets/js/admin/users-handler.js')
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
            ['label' => 'Utilisateurs']
        ]);
    }

    /**
     * Rendu de l'en-tête de page
     */
    private function renderPageHeader(): void
    {
        PageHeaderComponent::render([
            'title' => 'Gestion des Utilisateurs',
            'actions' => [
                [
                    'type' => 'button',
                    'label' => 'Nouvel utilisateur',
                    'onclick' => 'openAddModal()',
                    'class' => 'btn-primary'
                ],
                [
                    'type' => 'button',
                    'label' => 'Exporter',
                    'onclick' => 'exportData()',
                    'class' => 'btn-secondary'
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
            'action' => base_url('admin/users/users'),
            'showSearch' => true,
            'searchPlaceholder' => 'Rechercher un utilisateur...',
            'filters' => [
                [
                    'type' => 'select',
                    'name' => 'role',
                    'label' => 'Rôle',
                    'options' => [
                        'admin' => 'Administrateur',
                        'membre' => 'Membre',
                        'visiteur' => 'Visiteur'
                    ],
                    'defaultLabel' => 'Tous les rôles'
                ],
                [
                    'type' => 'select',
                    'name' => 'statut',
                    'label' => 'Statut',
                    'options' => [
                        'actif' => 'Actif',
                        'suspendu' => 'Suspendu',
                        'inactif' => 'Inactif'
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
            'data' => $this->users,
            'columns' => [
                [
                    'key' => 'username',
                    'label' => 'Nom d\'utilisateur',
                    'formatter' => function($value) {
                        return '<strong>' . htmlspecialchars($value) . '</strong>';
                    }
                ],
                [
                    'key' => 'email',
                    'label' => 'Email',
                    'formatter' => function($value) {
                        return htmlspecialchars($value);
                    }
                ],
                [
                    'key' => 'role',
                    'label' => 'Rôle',
                    'formatter' => function($value) {
                        $badges = [
                            'admin' => '<span class="badge badge-red">Admin</span>',
                            'membre' => '<span class="badge badge-blue">Membre</span>',
                            'visiteur' => '<span class="badge badge-gray">Visiteur</span>'
                        ];
                        return $badges[$value] ?? htmlspecialchars($value);
                    }
                ],
                [
                    'key' => 'derniere_connexion',
                    'label' => 'Dernière connexion',
                    'formatter' => function($value) {
                        return $value ? time_ago($value) : '<em style="color: #9CA3AF;">Jamais</em>';
                    }
                ],
                [
                    'key' => 'statut',
                    'label' => 'Statut',
                    'formatter' => function($value, $row) {
                        $statut = $value ?? 'actif';
                        $badges = [
                            'actif' => '<span class="badge badge-success">✓ Actif</span>',
                            'suspendu' => '<span class="badge badge-warning">⚠ Suspendu</span>',
                            'inactif' => '<span class="badge badge-secondary">○ Inactif</span>'
                        ];
                        return $badges[$statut] ?? htmlspecialchars($statut);
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
                    // Ne pas afficher le bouton supprimer pour l'utilisateur connecté
                    if ($row['id'] == session('user_id')) {
                        return '';
                    }
                    return '<button class="btn-action btn-delete" 
                                    onclick="deleteItem(' . $row['id'] . ')"
                                    title="Supprimer">
                                🗑️
                            </button>';
                }
            ],
            'emptyMessage' => 'Aucun utilisateur trouvé'
        ]);
    }

    /**
     * Rendu de la pagination
     */
    private function renderPagination(): void
    {
        if ($this->pagination) {
            echo Utils::renderPagination($this->pagination, base_url('admin/users/users'));
        }
    }

    /**
     * Rendu de la modale
     */
    private function renderModal(): void
    {
        ModalComponent::render([
            'id' => 'user-modal',
            'title' => 'Ajouter un utilisateur',
            'content' => '<div id="modal-form-container"></div>'
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