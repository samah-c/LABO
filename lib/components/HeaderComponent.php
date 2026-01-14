<?php
/**
 * HeaderComponent.php - Composant pour la gestion du header
 */

require_once __DIR__ . '/../LabHelpers.php';

class HeaderComponent {
    
    /**
     * Génère le header HTML complet avec balise <head>
     */
    public static function render($config = []) {
        $title = $config['title'] ?? 'Laboratoire TDW';
        $username = $config['username'] ?? session('username');
        $role = $config['role'] ?? 'visiteur';
        $showLoginButton = $config['showLoginButton'] ?? false;
        $showLogout = $config['showLogout'] ?? true;
        $showNotifications = $config['showNotifications'] ?? false; // NOUVEAU
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
            <link rel="stylesheet" href="<?= base_url('assets/css/base.css') ?>?v=<?= $cssVersion ?>">
            <link rel="stylesheet" href="<?= base_url('assets/css/layout.css') ?>?v=<?= $cssVersion ?>">
            <link rel="stylesheet" href="<?= base_url('assets/css/modules.css') ?>?v=<?= $cssVersion ?>">
            <link rel="stylesheet" href="<?= base_url('assets/css/state.css') ?>?v=<?= $cssVersion ?>">
            <link rel="stylesheet" href="<?= base_url('assets/css/theme.css') ?>?v=<?= $cssVersion ?>">
            
            <script src="<?= base_url('assets/js/table-enhancements.js') ?>" defer></script>
            
            <!-- CSS additionnels -->
            <?php foreach ($additionalCss as $css): ?>
                <link rel="stylesheet" href="<?= htmlspecialchars($css) ?>">
            <?php endforeach; ?>
            
            <!-- IMPORTANT: Définir baseUrl AVANT les scripts -->
            <script>
                window.baseUrl = '<?= base_url() ?>';
            </script>
            
            <!-- JavaScript additionnels -->
            <?php foreach ($additionalJs as $js): ?>
                <script src="<?= htmlspecialchars($js) ?>" defer></script>
            <?php endforeach; ?>
        </head>
        <body class="<?= htmlspecialchars($role) ?>-layout">
            
            <!-- Header -->
            <div class="header">
                <div class="header-left">
                    <img src="<?= base_url('assets/images/logo/laboratory.png') ?>" alt="Logo Laboratoire" class="header-logo">
                    <h1><?= htmlspecialchars($title) ?></h1>
                </div>
                <div class="user-info">
                    <?php if ($role === 'visiteur'): ?>
                        <?php self::renderSocialLinks(); ?>
                    <?php endif; ?>
                    
                    <?php if ($showLoginButton): ?>
                        <a href="<?= base_url('/login') ?>" class="btn-login-header">
                            Connexion
                        </a>
                    <?php endif; ?>
                    
                    <?php 
                    // Afficher les notifications si activé
                    if ($showNotifications && $username): 
                        self::renderNotificationBell($role);
                    endif; 
                    ?>
                    
                    <?php if ($username && $showLogout): ?>
                        <span class="user-greeting">
                            Bonjour, <strong><?= htmlspecialchars($username) ?></strong>
                        </span>
                        <a href="<?= base_url('logout') ?>" class="logout-btn">Déconnexion</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php
    }
    
    /**
     * Affiche les liens de réseaux sociaux
     */
    private static function renderSocialLinks() {
        $socialLinks = [
            ['url' => 'https://facebook.com', 'icon' => 'facebook.png', 'name' => 'Facebook'],
            ['url' => 'https://twitter.com', 'icon' => 'twitter.png', 'name' => 'Twitter'],
            ['url' => 'https://linkedin.com', 'icon' => 'linkedin.png', 'name' => 'LinkedIn'],
            ['url' => 'https://www.esi.dz', 'icon' => 'esi.png', 'name' => 'Site de l\'esi']
        ];
        ?>
        <div class="header-social-links">
            <?php foreach ($socialLinks as $link): ?>
                <a href="<?= htmlspecialchars($link['url']) ?>" target="_blank" title="<?= htmlspecialchars($link['name']) ?>">
                    <img src="<?= base_url('assets/images/icons/' . $link['icon']) ?>" 
                         alt="<?= htmlspecialchars($link['name']) ?>" 
                         width="20" height="20">
                </a>
            <?php endforeach; ?>
        </div>
        <?php
    }
    
    /**
     * NOUVEAU: Affiche la cloche de notification
     */
    private static function renderNotificationBell($role) {
        $prefix = $role === 'admin' ? 'notification' : 'membre-notification';
        ?>
        <div class="header-notifications">
            <div class="notification-bell" id="<?= $prefix ?>-bell">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                    <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                </svg>
                <span class="notification-badge" id="<?= $prefix ?>-count" style="display: none;">0</span>
            </div>
            
            <div class="notification-dropdown" id="<?= $prefix ?>-dropdown" style="display: none;">
                <div class="notification-header">
                    <h3>Notifications</h3>
                    <button onclick="markAllAsRead()" class="mark-all-read">Tout marquer comme lu</button>
                </div>
                <div class="notification-list" id="<?= $prefix ?>-list">
                    <div class="notification-loading">Chargement...</div>
                </div>
            </div>
        </div>
        
        <?php self::renderNotificationStyles(); ?>
        <?php
    }
    
    /**
     * NOUVEAU: Styles CSS pour les notifications
     */
    private static function renderNotificationStyles() {
        static $stylesRendered = false;
        if ($stylesRendered) return;
        $stylesRendered = true;
        ?>
        <style>
        /* Styles pour les notifications */
        .header-notifications {
            position: relative;
            margin-right: 20px;
        }
        
        .notification-bell {
            position: relative;
            cursor: pointer;
            padding: 8px;
            border-radius: 8px;
            transition: all 0.3s ease;
            color: var(--gray-600, #4B5563);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .notification-bell:hover {
            background: var(--gray-100, #F3F4F6);
            color: var(--primary, #6366F1);
        }
        
        .notification-bell svg {
            display: block;
        }
        
        .notification-badge {
            position: absolute;
            top: 2px;
            right: 2px;
            background: #EF4444;
            color: white;
            font-size: 10px;
            font-weight: 700;
            padding: 2px 6px;
            border-radius: 10px;
            min-width: 18px;
            height: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: notifPulse 2s infinite;
            box-shadow: 0 2px 8px rgba(239, 68, 68, 0.4);
        }
        
        @keyframes notifPulse {
            0%, 100% { 
                opacity: 1;
                transform: scale(1);
            }
            50% { 
                opacity: 0.8;
                transform: scale(1.05);
            }
        }
        
        .notification-dropdown {
            position: absolute;
            top: calc(100% + 10px);
            right: 0;
            width: 380px;
            max-height: 500px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            z-index: 1000;
            overflow: hidden;
            border: 1px solid var(--border-color, #E5E7EB);
        }
        
        .notification-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px;
            border-bottom: 1px solid var(--border-color, #E5E7EB);
            background: var(--gray-50, #F9FAFB);
        }
        
        .notification-header h3 {
            margin: 0;
            font-size: 16px;
            font-weight: 600;
            color: var(--gray-900, #111827);
        }
        
        .mark-all-read {
            background: none;
            border: none;
            color: var(--primary, #6366F1);
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            padding: 4px 8px;
            border-radius: 4px;
            transition: all 0.2s;
        }
        
        .mark-all-read:hover {
            background: var(--primary, #6366F1);
            color: white;
        }
        
        .notification-list {
            max-height: 400px;
            overflow-y: auto;
        }
        
        .notification-item {
            padding: 14px 16px;
            border-bottom: 1px solid var(--border-color, #E5E7EB);
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .notification-item:last-child {
            border-bottom: none;
        }
        
        .notification-item:hover {
            background: var(--gray-50, #F9FAFB);
        }
        
        .notification-item.unread {
            background: #EFF6FF;
            border-left: 3px solid var(--primary, #6366F1);
        }
        
        .notification-item.read {
            opacity: 0.7;
        }
        
        .notification-content {
            position: relative;
        }
        
        .notification-content .notification-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 6px;
            padding: 0;
            border: none;
            background: none;
        }
        
        .notification-content strong {
            font-size: 13px;
            font-weight: 600;
            color: var(--gray-900, #111827);
            flex: 1;
        }
        
        .notification-content p {
            margin: 6px 0;
            font-size: 12px;
            color: var(--gray-600, #4B5563);
            line-height: 1.4;
        }
        
        .notification-time {
            font-size: 11px;
            color: var(--gray-500, #6B7280);
            margin-top: 4px;
            display: block;
        }
        
        .priority-badge {
            background: #EF4444;
            color: white;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 12px;
            margin-left: 8px;
            flex-shrink: 0;
        }
        
        .notification-empty,
        .notification-loading {
            padding: 40px 20px;
            text-align: center;
            color: var(--gray-500, #6B7280);
        }
        
        .notification-empty p,
        .notification-loading {
            margin: 0;
            font-size: 14px;
        }
        
        /* Scrollbar personnalisée */
        .notification-list::-webkit-scrollbar {
            width: 6px;
        }
        
        .notification-list::-webkit-scrollbar-track {
            background: var(--gray-100, #F3F4F6);
        }
        
        .notification-list::-webkit-scrollbar-thumb {
            background: var(--gray-400, #9CA3AF);
            border-radius: 3px;
        }
        
        .notification-list::-webkit-scrollbar-thumb:hover {
            background: var(--gray-500, #6B7280);
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .notification-dropdown {
                width: 320px;
                right: -20px;
            }
            
            .header-notifications {
                margin-right: 10px;
            }
        }
        
        @media (max-width: 480px) {
            .notification-dropdown {
                width: 280px;
                right: -40px;
            }
        }
        </style>
        <?php
    }
}
?>