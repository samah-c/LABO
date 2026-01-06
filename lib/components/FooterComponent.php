<?php
/**
 * FooterComponent.php - Composant pour la gestion du footer
 * À placer dans : /TDW_project/lib/components/FooterComponent.php
 */

require_once __DIR__ . '/../LabHelpers.php';

class FooterComponent {
    
    /**
     * Génère le footer universel
     */
    public static function render($config = []) {
        $year = date('Y');
        $showAdmin = $config['showAdmin'] ?? false;
        $role = $config['role'] ?? 'visiteur';
        ?>
        <footer class="main-footer <?= $role === 'visiteur' ? 'footer-full-width' : '' ?>">
            <div class="footer-content">
                <?php self::renderFooterSection('Laboratoire TDW', [
                    'École Supérieure d\'Informatique (ESI)',
                    'Alger, Algérie'
                ]); ?>
                
                <?php self::renderQuickLinks(); ?>
                
                <?php self::renderContactInfo(); ?>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; <?= $year ?> Laboratoire TDW - Tous droits réservés</p>
                <?php if ($showAdmin): ?>
                    <a href="<?= base_url('admin/dashboard') ?>">Administration</a>
                <?php endif; ?>
            </div>
        </footer>
        
        <style>
            .footer-full-width {
                margin-left: 0 !important;
                width: 100% !important;
            }
        </style>
        </body>
        </html>
        <?php
    }
    
    /**
     * Génère une section du footer
     */
    private static function renderFooterSection($title, $items) {
        ?>
        <div class="footer-section">
            <h4><?= htmlspecialchars($title) ?></h4>
            <?php foreach ($items as $item): ?>
                <p><?= htmlspecialchars($item) ?></p>
            <?php endforeach; ?>
        </div>
        <?php
    }
    
    /**
     * Affiche les liens rapides
     */
    private static function renderQuickLinks() {
        $links = [
            ['url' => 'projets', 'label' => 'Projets'],
            ['url' => 'publications', 'label' => 'Publications']
        ];
        ?>
        <div class="footer-section">
            <h4>Liens rapides</h4>
            <ul>
                <?php foreach ($links as $link): ?>
                    <li><a href="<?= base_url($link['url']) ?>"><?= htmlspecialchars($link['label']) ?></a></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php
    }
    
    /**
     * Affiche les informations de contact
     */
    private static function renderContactInfo() {
        ?>
        <div class="footer-section">
            <h4>Contact</h4>
            <p>📧 contact@lab-tdw.dz</p>
            <p>📞 +213 (0)21 XX XX XX</p>
        </div>
        <?php
    }
}
?>