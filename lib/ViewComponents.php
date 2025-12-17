<?php
/**
 * ViewComponents.php - Bibliothèque de composants de vues réutilisables
 * À placer dans : /TDW_project/lib/ViewComponents.php
 */

require_once __DIR__ . '/LabHelpers.php';
  
class ViewComponents {

    // ========================================
    // HEADERS ET NAVIGATION
    // ========================================
    
    /**
     * Générer un header universel
     */
    public static function renderHeader($config = []) {
        $title = $config['title'] ?? 'Laboratoire TDW';
        $username = $config['username'] ?? session('username');
        $role = $config['role'] ?? 'visiteur';
        $showLoginButton = $config['showLoginButton'] ?? false;
        $showLogout = $config['showLogout'] ?? true;
        $additionalCss = $config['additionalCss'] ?? [];
        $additionalJs = $config['additionalJs'] ?? [];

        $cssVersion = '1.0.5';
        
        ?>
        <!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?= htmlspecialchars($title) ?></title>
            
            <!-- Fonts -->
            <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
            
            <!-- CSS Architecture SMACSS -->
            <link rel="stylesheet" href="<?= base_url('assets/css/base.css')  ?>?v=<?= $cssVersion ?>">
            <link rel="stylesheet" href="<?= base_url('assets/css/layout.css')  ?>?v=<?= $cssVersion ?>">
            <link rel="stylesheet" href="<?= base_url('assets/css/modules.css')  ?>?v=<?= $cssVersion ?>">
            <link rel="stylesheet" href="<?= base_url('assets/css/state.css')  ?>?v=<?= $cssVersion ?>">
            <link rel="stylesheet" href="<?= base_url('assets/css/theme.css')  ?>?v=<?= $cssVersion ?>">
            
            <script src="<?= base_url('assets/js/table-enhancements.js') ?>" defer></script>
            
            <!-- CSS additionnels -->
            <?php foreach ($additionalCss as $css): ?>
                <link rel="stylesheet" href="<?= htmlspecialchars($css) ?>">
            <?php endforeach; ?>
            
            <!-- JavaScript additionnels -->
            <?php foreach ($additionalJs as $js): ?>
                <script src="<?= htmlspecialchars($js) ?>" defer></script>
            <?php endforeach; ?>
        </head>
        <body class="<?= htmlspecialchars($role) ?>-layout">
            
            <?php 
            // Afficher la navigation SIDEBAR en premier pour admin/membre
            if (in_array($role, ['admin', 'membre'])) {
                self::renderNavigation($role);
            }
            ?>
            
            <!-- Header -->
            <div class="header">
                <div class="header-left">
                    <!-- Logo (pour tous les rôles) -->
                    <img src="<?= base_url('assets/images/logo/laboratory.png') ?>" alt="Logo Laboratoire" class="header-logo">
                    <h1><?= htmlspecialchars($title) ?></h1>
                </div>
                <div class="user-info">
                    <?php if ($role === 'visiteur'): ?>
                        <!-- Liens réseaux sociaux pour visiteurs -->
                        <div class="header-social-links">
                            <a href="https://facebook.com" target="_blank" title="Facebook">
                                <img src="<?= base_url('assets/images/icons/facebook.png') ?>" alt="Facebook" width="20" height="20">
                            </a>
                            <a href="https://twitter.com" target="_blank" title="Twitter">
                                <img src="<?= base_url('assets/images/icons/twitter.png') ?>" alt="Twitter" width="20" height="20">
                            </a>
                            <a href="https://linkedin.com" target="_blank" title="LinkedIn">
                                <img src="<?= base_url('assets/images/icons/linkedin.png') ?>" alt="LinkedIn" width="20" height="20">
                            </a>
                            <a href="https://www.esi.dz" target="_blank" title="Site de l'esi">
                                <img src="<?= base_url('assets/images/icons/esi.png') ?>" alt="ESI" width="20" height="20">
                            </a>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($showLoginButton): ?>
                        <!-- Bouton de connexion pour visiteurs -->
                        <a href="<?= base_url('/login') ?>" class="btn-login-header">
                            Connexion
                        </a>
                    <?php endif; ?>
                    
                    <?php if ($username && $showLogout): ?>
                        <!-- Info utilisateur et déconnexion pour membres/admin -->
                        <span class="user-greeting">
                            Bonjour, <strong><?= htmlspecialchars($username) ?></strong>
                        </span>
                        <a href="<?= base_url('logout') ?>" class="logout-btn">Déconnexion</a>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php 
            // Afficher le menu horizontal pour les visiteurs
            if ($role === 'visiteur') {
                self::renderHorizontalMenu();
            }
            ?>
        <?php
    }

    /**
     * Navigation dynamique selon le rôle
     */
    public static function renderNavigation($role = 'visiteur') {
        $menuItems = self::getMenuItemsByRole($role);
        
        if (empty($menuItems)) return;
        
        // Sidebar pour admin/membre uniquement
        if (in_array($role, ['admin', 'membre'])):
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
        endif;
    }
    
    /**
     * Menu horizontal pour les visiteurs (NOUVEAU)
     */
    public static function renderHorizontalMenu($currentPage = null) {
        // Déterminer la page actuelle automatiquement si non fournie
        if ($currentPage === null) {
            $currentPage = $_SERVER['REQUEST_URI'] ?? '';
            $currentPage = parse_url($currentPage, PHP_URL_PATH);
            $currentPage = trim($currentPage, '/');
        }
        
        $menuItems = [
            ['url' => '', 'label' => 'Accueil'],
            ['url' => 'projets', 'label' => 'Projets'],
            ['url' => 'publications', 'label' => 'Publications'],
            ['url' => 'equipements', 'label' => 'Équipements'],
            ['url' => 'membres', 'label' => 'Membres'],
            ['url' => 'contact', 'label' => 'Contact']
        ];
        ?>
        <nav class="horizontal-nav">
            <ul>
                <?php foreach ($menuItems as $item): ?>
                    <?php 
                    $isActive = ($currentPage === $item['url']) || 
                                (empty($item['url']) && empty($currentPage)) ||
                                (!empty($item['url']) && strpos($currentPage, $item['url']) === 0);
                    ?>
                    <li class="<?= $isActive ? 'active' : '' ?>">
                        <a href="<?= base_url($item['url']) ?>">
                            <?= htmlspecialchars($item['label']) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </nav>
        
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
     * Obtenir les items de menu selon le rôle
     */
    private static function getMenuItemsByRole($role) {
        $menus = [
            'admin' => [
                ['url' => 'admin/dashboard', 'label' => 'Dashboard', 'icon' => ''],
                ['url' => 'admin/users', 'label' => 'Utilisateurs', 'icon' => ''],
                ['url' => 'admin/equipes/equipes', 'label' => 'Équipes', 'icon' => ''],
                ['url' => 'admin/projets', 'label' => 'Projets', 'icon' => ''],
                ['url' => 'admin/publications/publications', 'label' => 'Publications', 'icon' => ''],
                ['url' => 'admin/equipements', 'label' => 'Équipements', 'icon' => ''],
                ['url' => 'admin/evenements', 'label' => 'Événements', 'icon' => '']
            ],
            'membre' => [
                ['url' => 'membre/dashboard', 'label' => 'Tableau de bord', 'icon' => ''],
                ['url' => 'membre/profil', 'label' => 'Mon profil', 'icon' => ''],
                ['url' => 'membre/projets', 'label' => 'Mes projets', 'icon' => ''],
                ['url' => 'membre/publications', 'label' => 'Mes publications', 'icon' => ''],
                ['url' => 'membre/reservations', 'label' => 'Réservations', 'icon' => '']
            ]
        ];
        
        return $menus[$role] ?? [];
    }
    
    /**
     * Footer universel
     */
    public static function renderFooter($config = []) {
        $year = date('Y');
        $showAdmin = $config['showAdmin'] ?? false;
        $role = $config['role'] ?? 'visiteur';
        ?>
        <footer class="main-footer <?= $role === 'visiteur' ? 'footer-full-width' : '' ?>">
            <div class="footer-content">
                <div class="footer-section">
                    <h4>Laboratoire TDW</h4>
                    <p>École Supérieure d'Informatique (ESI)</p>
                    <p>Alger, Algérie</p>
                </div>
                
                <div class="footer-section">
                    <h4>Liens rapides</h4>
                    <ul>
                        <li><a href="<?= base_url('projets') ?>">Projets</a></li>
                        <li><a href="<?= base_url('publications') ?>">Publications</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4>Contact</h4>
                    <p>📧 contact@lab-tdw.dz</p>
                    <p>📞 +213 (0)21 XX XX XX</p>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; <?= $year ?> Laboratoire TDW - Tous droits réservés</p>
                <?php if ($showAdmin): ?>
                    <a href="<?= base_url('admin/dashboard') ?>">Administration</a>
                <?php endif; ?>
            </div>
        </footer>
        
        <style>
            /* Footer pour visiteurs - pleine largeur */
            .footer-full-width {
                margin-left: 0 !important;
                width: 100% !important;
            }
        </style>
        </body>
        </html>
        <?php
    }

    // ========================================
    // TABLES DYNAMIQUES
    // ========================================
    
    /**
     * Générer une table générique avec actions
     */
    public static function renderTable($config) {
        $data = $config['data'] ?? [];
        $columns = $config['columns'] ?? [];
        $actions = $config['actions'] ?? [];
        $emptyMessage = $config['emptyMessage'] ?? 'Aucune donnée disponible';
        $tableClass = $config['class'] ?? 'table';
        
        $isEmpty = empty($data);
        $containerClass = $isEmpty ? 'table-container empty' : 'table-container';
        ?>
        <div class="<?= htmlspecialchars($containerClass) ?>">
            <?php if ($isEmpty): ?>
                <div class="empty-message">
                    <?= htmlspecialchars($emptyMessage) ?>
                </div>
            <?php else: ?>
                <table class="<?= htmlspecialchars($tableClass) ?>">
                    <thead>
                        <tr>
                            <?php foreach ($columns as $col): ?>
                                <th><?= htmlspecialchars($col['label']) ?></th>
                            <?php endforeach; ?>
                            
                            <?php if (!empty($actions)): ?>
                                <th>Actions</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($data as $row): ?>
                            <tr>
                                <?php foreach ($columns as $col): ?>
                                    <td>
                                        <?php 
                                        $value = $row[$col['key']] ?? '-';
                                        
                                        if (isset($col['formatter'])) {
                                            echo $col['formatter']($value, $row);
                                        } else {
                                            echo htmlspecialchars($value);
                                        }
                                        ?>
                                    </td>
                                <?php endforeach; ?>
                                
                                <?php if (!empty($actions)): ?>
                                    <td class="actions-cell">
                                        <?php 
                                        foreach ($actions as $action) {
                                            echo $action($row);
                                        }
                                        ?>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php
    }

    // ========================================
    // GRILLES DE CARTES
    // ========================================
    
    /**
     * Générer une grille de cartes (projets, publications, etc.)
     */
    public static function renderCardGrid($config) {
        $items = $config['items'] ?? [];
        $cardRenderer = $config['cardRenderer'] ?? null;
        $emptyMessage = $config['emptyMessage'] ?? 'Aucun élément à afficher';
        $gridClass = $config['gridClass'] ?? 'card-grid';
        ?>
        <div class="<?= htmlspecialchars($gridClass) ?>">
            <?php if (empty($items)): ?>
                <p class="empty-message"><?= htmlspecialchars($emptyMessage) ?></p>
            <?php else: ?>
                <?php foreach ($items as $item): ?>
                    <?php 
                    if (is_callable($cardRenderer)) {
                        echo $cardRenderer($item);
                    } else {
                        self::renderDefaultCard($item);
                    }
                    ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Carte par défaut
     */
    private static function renderDefaultCard($item) {
        ?>
        <div class="card">
            <div class="card-header">
                <h3><?= htmlspecialchars($item['titre'] ?? 'Sans titre') ?></h3>
            </div>
            <div class="card-body">
                <p><?= truncate($item['description'] ?? '', 150) ?></p>
            </div>
            <?php if (isset($item['url'])): ?>
                <div class="card-footer">
                    <a href="<?= htmlspecialchars($item['url']) ?>" class="btn-primary">
                        Voir détails
                    </a>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
    
    // ========================================
    // FORMULAIRES
    // ========================================
    
    /**
     * Générer un formulaire avec validation
     */
    public static function renderForm($config) {
        $action = $config['action'] ?? '';
        $method = $config['method'] ?? 'POST';
        $fields = $config['fields'] ?? [];
        $submitText = $config['submitText'] ?? 'Enregistrer';
        $cancelUrl = $config['cancelUrl'] ?? null;
        ?>
        <form action="<?= htmlspecialchars($action) ?>" 
              method="<?= htmlspecialchars($method) ?>" 
              class="dynamic-form">
            <?= csrf_field() ?>
            
            <?php foreach ($fields as $field): ?>
                <?php self::renderFormField($field); ?>
            <?php endforeach; ?>
            
            <div class="form-actions">
                <button type="submit" class="btn-primary">
                    <?= htmlspecialchars($submitText) ?>
                </button>
                
                <?php if ($cancelUrl): ?>
                    <a href="<?= htmlspecialchars($cancelUrl) ?>" class="btn-secondary">
                        Annuler
                    </a>
                <?php endif; ?>
            </div>
        </form>
        <?php
    }
    
    /**
     * Générer un champ de formulaire
     */
    public static function renderFormField($field) {
        $type = $field['type'] ?? 'text';
        $name = $field['name'] ?? '';
        $label = $field['label'] ?? '';
        $value = $field['value'] ?? old($name);
        $required = $field['required'] ?? false;
        $options = $field['options'] ?? [];
        $placeholder = $field['placeholder'] ?? '';
        ?>
        <div class="form-group">
            <?php if ($label): ?>
                <label for="<?= htmlspecialchars($name) ?>">
                    <?= htmlspecialchars($label) ?>
                    <?php if ($required): ?>
                        <span class="required">*</span>
                    <?php endif; ?>
                </label>
            <?php endif; ?>
            
            <?php if ($type === 'select'): ?>
                <select name="<?= htmlspecialchars($name) ?>" 
                        id="<?= htmlspecialchars($name) ?>"
                        <?= $required ? 'required' : '' ?>>
                    <?= select_options($options, $value, $placeholder) ?>
                </select>
                
            <?php elseif ($type === 'textarea'): ?>
                <textarea name="<?= htmlspecialchars($name) ?>" 
                          id="<?= htmlspecialchars($name) ?>"
                          placeholder="<?= htmlspecialchars($placeholder) ?>"
                          <?= $required ? 'required' : '' ?>><?= htmlspecialchars($value) ?></textarea>
                          
            <?php else: ?>
                <input type="<?= htmlspecialchars($type) ?>" 
                       name="<?= htmlspecialchars($name) ?>" 
                       id="<?= htmlspecialchars($name) ?>"
                       value="<?= htmlspecialchars($value) ?>"
                       placeholder="<?= htmlspecialchars($placeholder) ?>"
                       <?= $required ? 'required' : '' ?>>
            <?php endif; ?>
            
            <?= show_error($name) ?>
        </div>
        <?php
    }
    
    // ========================================
    // FILTRES ET RECHERCHE
    // ========================================
    
    /**
     * Générer une barre de filtres
     */
    public static function renderFilters($config) {
        $filters = $config['filters'] ?? [];
        $searchPlaceholder = $config['searchPlaceholder'] ?? 'Rechercher...';
        $showSearch = $config['showSearch'] ?? true;
        ?>
        <div class="filters-bar">
            <?php if ($showSearch): ?>
                <div class="search-box">
                    <input type="text" 
                           id="search-input" 
                           placeholder="<?= htmlspecialchars($searchPlaceholder) ?>"
                           value="<?= htmlspecialchars(get('search', '')) ?>">
                </div>
            <?php endif; ?>
            
            <div class="filters">
                <?php foreach ($filters as $filter): ?>
                    <div class="filter-item">
                        <label><?= htmlspecialchars($filter['label']) ?></label>
                        <select name="<?= htmlspecialchars($filter['name']) ?>" 
                                class="filter-select">
                            <?= select_options(
                                $filter['options'], 
                                get($filter['name']), 
                                'Tous'
                            ) ?>
                        </select>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <button type="button" class="btn-primary" id="apply-filters">
                Filtrer
            </button>
        </div>
        <?php
    }
    
    // ========================================
    // STATISTIQUES
    // ========================================
    
    /**
     * Générer des cartes de statistiques
     */
    public static function renderStatsCards($stats) {
        ?>
        <div class="stats-grid">
            <?php foreach ($stats as $stat): ?>
                <div class="stat-card">
                    <?php if (isset($stat['icon'])): ?>
                        <div class="stat-icon"><?= $stat['icon'] ?></div>
                    <?php endif; ?>
                    
                    <h3><?= htmlspecialchars($stat['label']) ?></h3>
                    <div class="number"><?= htmlspecialchars($stat['value']) ?></div>
                    
                    <?php if (isset($stat['change'])): ?>
                        <div class="stat-change <?= $stat['change'] >= 0 ? 'positive' : 'negative' ?>">
                            <?= $stat['change'] >= 0 ? '↑' : '↓' ?>
                            <?= abs($stat['change']) ?>%
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
    }
    
    // ========================================
    // MODALES
    // ========================================
    
    /**
     * Générer une modale
     */
    public static function renderModal($config) {
        $id = $config['id'] ?? 'modal';
        $title = $config['title'] ?? '';
        $content = $config['content'] ?? '';
        $footer = $config['footer'] ?? null;
        ?>
        <div id="<?= htmlspecialchars($id) ?>" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2><?= htmlspecialchars($title) ?></h2>
                    <span class="modal-close">&times;</span>
                </div>
                
                <div class="modal-body">
                    <?= $content ?>
                </div>
                
                <?php if ($footer): ?>
                    <div class="modal-footer">
                        <?= $footer ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
    
    // ========================================
    // BREADCRUMBS
    // ========================================
    
    /**
     * Générer un fil d'Ariane
     */
    public static function renderBreadcrumbs($items) {
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