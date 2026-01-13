<?php
/**
 * SettingsLoader.php - Charge et applique les paramètres système
 * À placer dans : lib/SettingsLoader.php
 */

class SettingsLoader {
    private static $instance = null;
    private $settings = [];
    private $settingsFile;
    
    private function __construct() {
        $this->settingsFile = __DIR__ . '/../config/settings.json';
        $this->loadSettings();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Charger les paramètres depuis le fichier JSON
     */
    private function loadSettings() {
        if (file_exists($this->settingsFile)) {
            $content = file_get_contents($this->settingsFile);
            $this->settings = json_decode($content, true) ?: [];
        }
        
        // Fusionner avec les paramètres par défaut
        $this->settings = array_merge($this->getDefaultSettings(), $this->settings);
    }
    
    /**
     * Obtenir un paramètre
     */
    public function get($key, $default = null) {
        return $this->settings[$key] ?? $default;
    }
    
    /**
     * Obtenir tous les paramètres
     */
    public function getAll() {
        return $this->settings;
    }
    
    /**
     * Vérifier si le mode maintenance est actif
     */
    public function isMaintenanceMode() {
        return !empty($this->settings['maintenance_mode']);
    }
    
    /**
     * Obtenir le message de maintenance
     */
    public function getMaintenanceMessage() {
        return $this->get('maintenance_message', 'Site en maintenance, revenez bientôt.');
    }
    
    /**
     * Générer les variables CSS pour le thème
     */
    public function getThemeCSS() {
        $primaryColor = $this->get('primary_color', '#2563eb');
        $secondaryColor = $this->get('secondary_color', '#64748b');
        $themeMode = $this->get('theme_mode', 'light');
        
        $css = ":root {\n";
        $css .= "    --primary: {$primaryColor};\n";
        $css .= "    --secondary: {$secondaryColor};\n";
        
        // Générer des variantes de couleurs
        $css .= "    --primary-light: " . $this->adjustBrightness($primaryColor, 20) . ";\n";
        $css .= "    --primary-dark: " . $this->adjustBrightness($primaryColor, -20) . ";\n";
        $css .= "    --secondary-light: " . $this->adjustBrightness($secondaryColor, 20) . ";\n";
        $css .= "    --secondary-dark: " . $this->adjustBrightness($secondaryColor, -20) . ";\n";
        
        // Appliquer le mode sombre si nécessaire
        if ($themeMode === 'dark') {
            $css .= "    --bg-color: #1a202c;\n";
            $css .= "    --text-color: #f7fafc;\n";
            $css .= "    --border-color: #2d3748;\n";
        } else {
            $css .= "    --bg-color: #ffffff;\n";
            $css .= "    --text-color: #1a202c;\n";
            $css .= "    --border-color: #e2e8f0;\n";
        }
        
        $css .= "}\n";
        
        return $css;
    }
    
    /**
     * Obtenir l'URL du logo
     */
    public function getLogoUrl() {
        $logo = $this->get('lab_logo');
        if ($logo) {
            return base_url('uploads/' . $logo);
        }
        return base_url('assets/images/logo/laboratory.png');
    }
    
    /**
     * Obtenir les informations du laboratoire
     */
    public function getLabInfo() {
        return [
            'name' => $this->get('lab_name', 'Laboratoire TDW'),
            'description' => $this->get('lab_description', 'École Supérieure d\'Informatique'),
            'email' => $this->get('lab_email', 'contact@lab-tdw.dz'),
            'phone' => $this->get('lab_phone', '+213 (0)21 XX XX XX'),
            'address' => $this->get('lab_address', 'Alger, Algérie'),
            'logo' => $this->getLogoUrl()
        ];
    }
    
    /**
     * Obtenir les liens des réseaux sociaux
     */
    public function getSocialLinks() {
        return [
            'facebook' => $this->get('facebook_url', ''),
            'twitter' => $this->get('twitter_url', ''),
            'linkedin' => $this->get('linkedin_url', ''),
            'website' => $this->get('website_url', '')
        ];
    }
    
    /**
     * Ajuster la luminosité d'une couleur hexadécimale
     */
    private function adjustBrightness($hex, $percent) {
        // Retirer le # si présent
        $hex = str_replace('#', '', $hex);
        
        // Convertir en RGB
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        
        // Ajuster la luminosité
        $r = max(0, min(255, $r + ($r * $percent / 100)));
        $g = max(0, min(255, $g + ($g * $percent / 100)));
        $b = max(0, min(255, $b + ($b * $percent / 100)));
        
        // Reconvertir en hex
        return '#' . str_pad(dechex($r), 2, '0', STR_PAD_LEFT) 
                  . str_pad(dechex($g), 2, '0', STR_PAD_LEFT) 
                  . str_pad(dechex($b), 2, '0', STR_PAD_LEFT);
    }
    
    /**
     * Paramètres par défaut
     */
    private function getDefaultSettings() {
        return [
            'lab_name' => 'Laboratoire TDW',
            'lab_description' => 'École Supérieure d\'Informatique',
            'lab_email' => 'contact@lab-tdw.dz',
            'lab_phone' => '+213 (0)21 XX XX XX',
            'lab_address' => 'Alger, Algérie',
            'lab_logo' => null,
            'facebook_url' => '',
            'twitter_url' => '',
            'linkedin_url' => '',
            'website_url' => '',
            'primary_color' => '#2563eb',
            'secondary_color' => '#64748b',
            'theme_mode' => 'light',
            'maintenance_mode' => false,
            'maintenance_message' => 'Site en maintenance, revenez bientôt.'
        ];
    }
}

/**
 * Fonction helper pour accéder aux paramètres facilement
 */
function settings($key = null, $default = null) {
    $loader = SettingsLoader::getInstance();
    
    if ($key === null) {
        return $loader;
    }
    
    return $loader->get($key, $default);
}
