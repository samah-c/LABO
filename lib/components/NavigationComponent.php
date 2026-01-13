<?php
/**
 * NavigationComponent.php - Composant pour la gestion de la navigation
 */

require_once __DIR__ . '/../LabHelpers.php';

class NavigationComponent {
    
    /**
     * Génère la navigation selon le rôle (sidebar pour admin/membre)
     */
    public static function renderSidebar($role = 'visiteur') {
        $menuItems = self::getMenuItemsByRole($role);
        
        if (empty($menuItems) || !in_array($role, ['admin', 'membre'])) {
            return;
        }
        ?>
        <nav class="main-nav">
            <ul>
                <?php foreach ($menuItems as $item): ?>
                    <li class="<?= active_link($item['url']) ?>">
                        <a href="<?= base_url($item['url']) ?>">
                            <?php if (isset($item['icon'])): ?>
                                <span class="nav-icon"><?= $item['icon'] ?></span>
                            <?php endif; ?>
                            <?= htmlspecialchars($item['label']) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </nav>
        <?php
    }
    
    /**
     * Génère le menu horizontal pour les visiteurs
     */
    public static function renderHorizontalMenu($currentPage = null) {
        if ($currentPage === null) {
            $currentPage = $_SERVER['REQUEST_URI'] ?? '';
            $currentPage = parse_url($currentPage, PHP_URL_PATH);
            $currentPage = trim($currentPage, '/');
        }
        
        $menuItems = self::getVisitorMenuItems();
        ?>
        <nav class="horizontal-nav">
            <ul>
                <?php foreach ($menuItems as $item): ?>
                    <?php 
                    $isActive = self::isMenuItemActive($currentPage, $item['url']);
                    ?>
                    <li class="<?= $isActive ? 'active' : '' ?>">
                        <a href="<?= base_url($item['url']) ?>">
                            <?= htmlspecialchars($item['label']) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </nav>
        
        <?php self::renderHorizontalMenuStyles(); ?>
        <?php
    }
    
    /**
     * Vérifie si un item de menu est actif
     */
    private static function isMenuItemActive($currentPage, $itemUrl) {
        return ($currentPage === $itemUrl) || 
               (empty($itemUrl) && empty($currentPage)) ||
               (!empty($itemUrl) && strpos($currentPage, $itemUrl) === 0);
    }
    
    /**
     * Retourne les items de menu pour les visiteurs
     */
    private static function getVisitorMenuItems() {
        return [
            ['url' => '', 'label' => 'Accueil'],
            ['url' => 'projets', 'label' => 'Projets'],
            ['url' => 'publications', 'label' => 'Publications'],
            ['url' => 'equipements', 'label' => 'Équipements'],
            ['url' => 'membres', 'label' => 'Membres'],
            ['url' => 'contact', 'label' => 'Contact']
        ];
    }
    
    /**
     * Retourne les items de menu selon le rôle
     */
    private static function getMenuItemsByRole($role) {
        $menus = [
            'admin' => [
                ['url' => 'admin/dashboard', 'label' => 'Dashboard'],
                ['url' => 'admin/users', 'label' => 'Utilisateurs'],
                ['url' => 'admin/equipes/equipes', 'label' => 'Équipes'],
                ['url' => 'admin/projets', 'label' => 'Projets'],
                ['url' => 'admin/publications/publications', 'label' => 'Publications'],
                ['url' => 'admin/equipements', 'label' => 'Équipements'],
                ['url' => 'admin/evenements', 'label' => 'Événements'],
                ['url' => 'admin/parametres', 'label' => 'Paramètres']
            ],
            'membre' => [
                ['url' => 'membre/dashboard', 'label' => 'Tableau de bord'],
                ['url' => 'membre/profil', 'label' => 'Mon profil'],
                ['url' => 'membre/projets', 'label' => 'Mes projets'],
                ['url' => 'membre/publications', 'label' => 'Mes publications'],
                ['url' => 'membre/reservations', 'label' => 'Réservations']
            ]
        ];
        
        return $menus[$role] ?? [];
    }
    
    /**
     * Affiche les styles pour le menu horizontal
     */
    private static function renderHorizontalMenuStyles() {
        ?>
        <style>
        /* Styles pour le menu horizontal visiteur */
        .visiteur-layout .main-nav {
            display: none !important;
        }

        .visiteur-layout .header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            margin-left: 0 !important;
            z-index: 1000;
            background: white;
            border-bottom: 1px solid var(--border-color);
            padding: 12px 32px;
            height: 57px;
        }

        .visiteur-layout .container {
            margin-left: 0 !important;
            padding-top: 57px;
        }

        /* Navigation horizontale */
        .horizontal-nav {
            background: var(--bg-sidebar);
            box-shadow: var(--shadow-sm);
            position: fixed;
            top: 57px;
            left: 0;
            right: 0;
            z-index: 999;
            height: 51px;
        }

        .horizontal-nav ul {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 32px;
            list-style: none;
            display: flex;
            gap: 4px;
        }

        .horizontal-nav li a {
            display: block;
            padding: 14px 20px;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
            transition: var(--transition);
            border-radius: var(--border-radius-sm);
        }

        .horizontal-nav li a:hover,
        .horizontal-nav li.active a {
            background: var(--primary);
            color: white;
        }

        /* Container ajusté pour le menu horizontal */
        .visitor-container {
            margin: 0;
            padding: 0;
            max-width: 100%;
            padding-top: 108px; /* 57px header + 51px menu */
        }

        /* Responsive */
        @media (max-width: 768px) {
            .horizontal-nav ul {
                padding: 0 20px;
                flex-wrap: wrap;
                justify-content: center;
            }
            
            .horizontal-nav {
                height: auto;
            }
            
            .visitor-container {
                padding-top: 120px;
            }
        }

        @media (max-width: 480px) {
            .horizontal-nav ul {
                flex-direction: column;
                gap: 0;
            }
            
            .horizontal-nav li a {
                border-radius: 0;
                border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            }
        }
        </style>
        <?php
    }
    
    /**
     * Génère un fil d'Ariane (breadcrumbs)
     */
    public static function renderBreadcrumbs($items) {
        if (empty($items)) return;
        ?>
        <nav class="breadcrumbs">
            <?php foreach ($items as $index => $item): ?>
                <?php if ($index > 0): ?>
                    <span class="separator">›</span>
                <?php endif; ?>
                
                <?php if (isset($item['url'])): ?>
                    <a href="<?= htmlspecialchars($item['url']) ?>">
                        <?= htmlspecialchars($item['label']) ?>
                    </a>
                <?php else: ?>
                    <span class="current"><?= htmlspecialchars($item['label']) ?></span>
                <?php endif; ?>
            <?php endforeach; ?>
        </nav>
        <?php
    }
}
?>