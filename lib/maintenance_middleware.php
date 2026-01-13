<?php
/**
 * MaintenanceMiddleware.php - Vérifie le mode maintenance
 * À placer dans : lib/MaintenanceMiddleware.php
 */

require_once __DIR__ . '/SettingsLoader.php';

class MaintenanceMiddleware {
    
    /**
     * Vérifier si le site est en maintenance
     * Bloque l'accès sauf pour les administrateurs
     */
    public static function check() {
        $settings = SettingsLoader::getInstance();
        
        // Si le mode maintenance n'est pas actif, continuer normalement
        if (!$settings->isMaintenanceMode()) {
            return;
        }
        
        // Vérifier si l'utilisateur est connecté et est admin
        session_start();
        $userRole = $_SESSION['user_role'] ?? null;
        
        // Autoriser les admins à accéder au site
        if ($userRole === 'admin') {
            // Afficher un bandeau d'avertissement pour les admins
            self::showAdminWarning();
            return;
        }
        
        // Afficher la page de maintenance pour tous les autres
        self::showMaintenancePage();
        exit;
    }
    
    /**
     * Afficher la page de maintenance
     */
    private static function showMaintenancePage() {
        $settings = SettingsLoader::getInstance();
        $labInfo = $settings->getLabInfo();
        $message = $settings->getMaintenanceMessage();
        
        http_response_code(503);
        header('Retry-After: 3600'); // Réessayer dans 1 heure
        
        ?>
        <!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Maintenance - <?= htmlspecialchars($labInfo['name']) ?></title>
            <style>
                * {
                    margin: 0;
                    padding: 0;
                    box-sizing: border-box;
                }
                
                body {
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    min-height: 100vh;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    color: white;
                    padding: 2rem;
                }
                
                .maintenance-container {
                    text-align: center;
                    max-width: 600px;
                }
                
                .logo {
                    max-width: 150px;
                    margin-bottom: 2rem;
                    border-radius: 12px;
                    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
                }
                
                .icon {
                    font-size: 5rem;
                    margin-bottom: 1rem;
                    animation: pulse 2s infinite;
                }
                
                @keyframes pulse {
                    0%, 100% { transform: scale(1); }
                    50% { transform: scale(1.1); }
                }
                
                h1 {
                    font-size: 2.5rem;
                    margin-bottom: 1rem;
                    font-weight: 700;
                }
                
                p {
                    font-size: 1.25rem;
                    line-height: 1.6;
                    opacity: 0.9;
                    margin-bottom: 2rem;
                }
                
                .back-link {
                    display: inline-block;
                    padding: 0.75rem 2rem;
                    background: rgba(255, 255, 255, 0.2);
                    border: 2px solid white;
                    border-radius: 8px;
                    color: white;
                    text-decoration: none;
                    font-weight: 600;
                    transition: all 0.3s;
                }
                
                .back-link:hover {
                    background: white;
                    color: #667eea;
                    transform: translateY(-2px);
                    box-shadow: 0 10px 20px rgba(0,0,0,0.2);
                }
                
                .info {
                    margin-top: 3rem;
                    padding: 1rem;
                    background: rgba(255, 255, 255, 0.1);
                    border-radius: 8px;
                    font-size: 0.875rem;
                }
            </style>
        </head>
        <body>
            <div class="maintenance-container">
                <img src="<?= $labInfo['logo'] ?>" alt="Logo" class="logo">
                
                <div class="icon">🔧</div>
                
                <h1>Site en Maintenance</h1>
                
                <p><?= nl2br(htmlspecialchars($message)) ?></p>
                
                <a href="<?= base_url() ?>" class="back-link">Actualiser la page</a>
                
                <div class="info">
                    <p>Nous nous excusons pour la gêne occasionnée.<br>
                    Nous serons bientôt de retour !</p>
                </div>
            </div>
        </body>
        </html>
        <?php
    }
    
    /**
     * Afficher un bandeau d'avertissement pour les admins
     */
    private static function showAdminWarning() {
        ?>
        <style>
        .admin-maintenance-banner {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: #f59e0b;
            color: white;
            padding: 0.75rem;
            text-align: center;
            font-weight: 600;
            z-index: 9999;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        body {
            padding-top: 50px !important;
        }
        </style>
        
        <div class="admin-maintenance-banner">
            ⚠️ MODE MAINTENANCE ACTIF - Vous êtes connecté en tant qu'administrateur, vous pouvez accéder au site.
        </div>
        <?php
    }
}
