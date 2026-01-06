<?php
/**
 * Vue du Dashboard Admin
 */
require_once __DIR__ . '/../../lib/components/HeaderComponent.php';
require_once __DIR__ . '/../../lib/components/NavigationComponent.php';
require_once __DIR__ . '/../../lib/components/PageHeaderComponent.php';
require_once __DIR__ . '/../../lib/components/TableComponent.php';
require_once __DIR__ . '/../../lib/components/FooterComponent.php';

class DashboardView
{
    private array $stats;
    private string $username;

    public function __construct(array $stats = [], string $username = '')
    {
        $this->stats = $stats;
        $this->username = $username;
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
        $this->renderWelcomeBanner();
        $this->renderStats();
        $this->renderMenu();
        echo '</div>';
        $this->renderFooter();
    }

    /**
     * Rendu de l'en-tête
     */
    private function renderHeader(): void
    {
        HeaderComponent::render([
            'title' => 'Laboratory Management System',
            'username' => $this->username,
            'role' => 'admin',
            'showLogout' => true,
            'additionalJs' => [
                base_url('assets/js/admin/admin-dashboard.js')
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
            ['label' => 'Dashboard']
        ]);
    }

    /**
     * Rendu de la bannière de bienvenue
     */
    private function renderWelcomeBanner(): void
    {
        ?>
        <div class="welcome-banner">
            <h2>Bienvenue, <?= htmlspecialchars($this->username) ?></h2>
            <p>Gérez toutes les ressources et activités du laboratoire</p>
        </div>
        <?php
    }

    /**
     * Rendu des statistiques
     */
    private function renderStats(): void
    {
        $statsData = [
            [
                'label' => 'Utilisateurs',
                'value' => $this->stats['total_users'] ?? 0,
                'change' => 5
            ],
            [
                'label' => 'Membres Actifs',
                'value' => $this->stats['total_membres'] ?? 0,
                'change' => 12
            ],
            [
                'label' => 'Projets de Recherche',
                'value' => $this->stats['total_projets'] ?? 0,
                'change' => -3
            ],
            [
                'label' => 'Publications',
                'value' => $this->stats['total_publications'] ?? 0,
                'change' => 8
            ]
        ];

        TableComponent::renderStatsCards($statsData);
    }

    /**
     * Rendu du menu principal
     */
    private function renderMenu(): void
    {
        echo '<h2 class="section-title">Gestion du Laboratoire</h2>';

        $menuItems = [
            [
                'titre' => 'Utilisateurs',
                'description' => 'Gérer les comptes et permissions',
                'url' => base_url('admin/users')
            ],
            [
                'titre' => 'Équipes',
                'description' => 'Équipes de recherche',
                'url' => base_url('admin/equipes/equipes')
            ],
            [
                'titre' => 'Projets',
                'description' => 'Projets de recherche',
                'url' => base_url('admin/projets')
            ],
            [
                'titre' => 'Équipements',
                'description' => 'Ressources matérielles',
                'url' => base_url('admin/equipements/equipements/dashboard')
            ],
            [
                'titre' => 'Publications',
                'description' => 'Base documentaire',
                'url' => base_url('admin/publications/publications')
            ],
            [
                'titre' => 'Événements',
                'description' => 'Événements scientifiques',
                'url' => base_url('admin/evenements')
            ],
            [
                'titre' => 'Paramètres',
                'description' => 'Configuration système',
                'url' => base_url('admin/parametres')
            ]
        ];

        TableComponent::renderCardGrid([
            'items' => $menuItems,
            'gridClass' => 'menu-grid',
            'cardRenderer' => function($item) {
                ?>
                <a href="<?= htmlspecialchars($item['url']) ?>" class="menu-card">
                    <h3><?= htmlspecialchars($item['titre']) ?></h3>
                    <p><?= htmlspecialchars($item['description']) ?></p>
                </a>
                <?php
            }
        ]);
    }

    /**
     * Rendu du pied de page
     */
    private function renderFooter(): void
    {
        FooterComponent::render(['role' => 'admin']);
    }

    /**
     * Setter pour les statistiques
     */
    public function setStats(array $stats): void
    {
        $this->stats = $stats;
    }

    /**
     * Setter pour le nom d'utilisateur
     */
    public function setUsername(string $username): void
    {
        $this->username = $username;
    }
}