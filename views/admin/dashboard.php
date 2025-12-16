<?php
/**Dashboard Admin
 */

require_once __DIR__ . '/../../lib/helpers.php';
require_once __DIR__ . '/../../lib/ViewComponents.php';

class Dashboard {
    private $stats;
    private $username;
    
    public function __construct($stats = [], $username = '') {
        $this->stats = $stats;
        $this->username = $username;
    }
    
    public function render() {
        ViewComponents::renderHeader([
            'title' => 'Laboratory Management System',
            'username' => $this->username,
            'role' => 'admin',
            'showLogout' => true,
            'additionalJs' => [
                '/TDW_project/assets/js/admin/admin-dashboard.js'
            ]
        ]);
        
        echo '<div class="container">';
        
        ViewComponents::renderBreadcrumbs([
            ['label' => 'Home', 'url' => base_url('admin/dashboard')],
            ['label' => 'Dashboard']
        ]);
        
        $this->renderWelcomeBanner();
        $this->renderStats();
        $this->renderMenu();
        
        echo '</div>';
        
        ViewComponents::renderFooter(['role' => 'admin']);
    }
    
    private function renderWelcomeBanner() {
        ?>
        <div class="welcome-banner">
            <h2>Welcome back, <?= e($this->username) ?></h2>
            <p>Manage all laboratory resources and activities</p>
        </div>
        <?php
    }
    
    private function renderStats() {
        $statsData = [
            [
                'label' => 'Total Users',
                'value' => $this->stats['total_users'] ?? 0,
                'change' => 5
            ],
            [
                'label' => 'Active Members',
                'value' => $this->stats['total_membres'] ?? 0,
                'change' => 12
            ],
            [
                'label' => 'Research Projects',
                'value' => $this->stats['total_projets'] ?? 0,
                'change' => -3
            ],
            [
                'label' => 'Publications',
                'value' => $this->stats['total_publications'] ?? 0,
                'change' => 8
            ]
        ];
        
        ViewComponents::renderStatsCards($statsData);
    }
    
    private function renderMenu() {
        echo '<h2 class="section-title">Laboratory Management</h2>';
        
        $menuItems = [
            [
                'titre' => 'Users',
                'description' => 'Manage accounts and permissions',
                'url' => base_url('admin/users/users')
            ],
            [
                'titre' => 'Teams',
                'description' => 'Research teams',
                'url' => base_url('admin/equipes/equipes')
            ],
            [
                'titre' => 'Projects',
                'description' => 'Research projects',
                'url' => base_url('admin/projets/projets')
            ],
            [
                'titre' => 'Equipment',
                'description' => 'Material resources',
                'url' => base_url('admin/equipements/dashboard')
            ],
            [
                'titre' => 'Publications',
                'description' => 'Document database',
                'url' => base_url('admin/publications/publications')
            ],
            [
                'titre' => 'Events',
                'description' => 'Scientific events',
                'url' => base_url('admin/evenements')
            ],
            [
                'titre' => 'Settings',
                'description' => 'System configuration',
                'url' => base_url('admin/parametres')
            ]
        ];
        
        ViewComponents::renderCardGrid([
            'items' => $menuItems,
            'gridClass' => 'menu-grid',
            'cardRenderer' => function($item) {
                ?>
                <a href="<?= e($item['url']) ?>" class="menu-card">
                    <h3><?= e($item['titre']) ?></h3>
                    <p><?= e($item['description']) ?></p>
                </a>
                <?php
            }
        ]);
    }
    
    public function setStats($stats) {
        $this->stats = $stats;
    }
    
    public function setUsername($username) {
        $this->username = $username;
    }
}
?>