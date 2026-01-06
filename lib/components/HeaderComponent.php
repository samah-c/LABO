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
}
?>