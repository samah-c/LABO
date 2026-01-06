<?php
/**
 * ParametresController.php - Contrôleur complet pour la gestion des paramètres
 * Gère la configuration, thème, et backup/restauration de la BDD
 */

require_once __DIR__ . '/../../lib/helpers.php';
require_once __DIR__ . '/../auth/AuthController.php';
require_once __DIR__ . '/../../config/database.php';

require_once __DIR__ . '/../../views/admin/parametres/ParametresView.php';

class ParametresController {
    private $settingsFile;
    private $backupDir;
    private $uploadsDir;
    
    public function __construct() {
        AuthController::requireAdmin();
        $this->settingsFile = __DIR__ . '/../../config/settings.json';
        $this->backupDir = __DIR__ . '/../../backups';
        $this->uploadsDir = __DIR__ . '/../../uploads';
        
        // Créer les dossiers s'ils n'existent pas
        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0755, true);
        }
        if (!is_dir($this->uploadsDir)) {
            mkdir($this->uploadsDir, 0755, true);
        }
    }
    
    /**
     * Page principale des paramètres
     */
    public function index() {
        $settings = $this->loadSettings();
        $backups = $this->getBackupsList();
        
        $view = new ParametresView($settings, $backups);
        $view->render();
    }
    
    /**
     * Sauvegarder les paramètres généraux
     */
    public function saveGeneral() {
        try {
            $settings = $this->loadSettings();
            
            // Données du formulaire
            $data = [
                'lab_name' => Utils::sanitize($_POST['lab_name'] ?? ''),
                'lab_description' => Utils::sanitize($_POST['lab_description'] ?? ''),
                'lab_email' => Utils::sanitize($_POST['lab_email'] ?? ''),
                'lab_phone' => Utils::sanitize($_POST['lab_phone'] ?? ''),
                'lab_address' => Utils::sanitize($_POST['lab_address'] ?? '')
            ];
            
            // Gestion du logo
            if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
                $logo = $this->uploadLogo($_FILES['logo']);
                if ($logo) {
                    // Supprimer l'ancien logo
                    if (!empty($settings['lab_logo'])) {
                        $oldLogo = $this->uploadsDir . '/' . $settings['lab_logo'];
                        if (file_exists($oldLogo)) {
                            unlink($oldLogo);
                        }
                    }
                    $data['lab_logo'] = $logo;
                } else {
                    $_SESSION['error'] = 'Erreur lors de l\'upload du logo';
                    redirect(base_url('admin/parametres'));
                }
            } else {
                // Conserver l'ancien logo
                $data['lab_logo'] = $settings['lab_logo'] ?? null;
            }
            
            // Fusionner avec les paramètres existants
            $settings = array_merge($settings, $data);
            
            // Sauvegarder
            if ($this->saveSettings($settings)) {
                $_SESSION['success'] = 'Paramètres généraux enregistrés avec succès';
            } else {
                $_SESSION['error'] = 'Erreur lors de l\'enregistrement';
            }
            
            Utils::log("Paramètres généraux mis à jour par " . session('username'));
            
        } catch (Exception $e) {
            Utils::log("Erreur sauvegarde paramètres: " . $e->getMessage(), 'ERROR');
            $_SESSION['error'] = 'Erreur: ' . $e->getMessage();
        }
        
        redirect(base_url('admin/parametres'));
    }
    
    /**
     * Sauvegarder les réseaux sociaux
     */
    public function saveSocial() {
        try {
            $settings = $this->loadSettings();
            
            $data = [
                'facebook_url' => Utils::sanitize($_POST['facebook_url'] ?? ''),
                'twitter_url' => Utils::sanitize($_POST['twitter_url'] ?? ''),
                'linkedin_url' => Utils::sanitize($_POST['linkedin_url'] ?? ''),
                'website_url' => Utils::sanitize($_POST['website_url'] ?? '')
            ];
            
            $settings = array_merge($settings, $data);
            
            if ($this->saveSettings($settings)) {
                $_SESSION['success'] = 'Réseaux sociaux enregistrés avec succès';
            } else {
                $_SESSION['error'] = 'Erreur lors de l\'enregistrement';
            }
            
        } catch (Exception $e) {
            $_SESSION['error'] = 'Erreur: ' . $e->getMessage();
        }
        
        redirect(base_url('admin/parametres'));
    }
    
    /**
     * Sauvegarder le thème
     */
    public function saveTheme() {
        try {
            $settings = $this->loadSettings();
            
            $data = [
                'primary_color' => Utils::sanitize($_POST['primary_color'] ?? '#2563eb'),
                'secondary_color' => Utils::sanitize($_POST['secondary_color'] ?? '#64748b'),
                'theme_mode' => Utils::sanitize($_POST['theme_mode'] ?? 'light')
            ];
            
            $settings = array_merge($settings, $data);
            
            if ($this->saveSettings($settings)) {
                $_SESSION['success'] = 'Thème enregistré avec succès';
            } else {
                $_SESSION['error'] = 'Erreur lors de l\'enregistrement';
            }
            
        } catch (Exception $e) {
            $_SESSION['error'] = 'Erreur: ' . $e->getMessage();
        }
        
        redirect(base_url('admin/parametres'));
    }
    
    /**
     * Créer une sauvegarde de la base de données
     */
    public function backupDatabase() {
        try {
            $filename = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
            $filepath = $this->backupDir . '/' . $filename;
            
            // Connexion à la base de données
            $db = Database::getInstance()->getConnection();
            
            // Récupérer toutes les tables
            $tables = [];
            $result = $db->query("SHOW TABLES");
            while ($row = $result->fetch(PDO::FETCH_NUM)) {
                $tables[] = $row[0];
            }
            
            // Créer le contenu du backup
            $output = "-- Backup Database\n";
            $output .= "-- Date: " . date('Y-m-d H:i:s') . "\n";
            $output .= "-- Generated by: " . session('username') . "\n\n";
            $output .= "SET FOREIGN_KEY_CHECKS=0;\n\n";
            
            foreach ($tables as $table) {
                // Structure de la table
                $result = $db->query("SHOW CREATE TABLE `$table`");
                $row = $result->fetch(PDO::FETCH_ASSOC);
                
                $output .= "-- Table: $table\n";
                $output .= "DROP TABLE IF EXISTS `$table`;\n";
                $output .= $row['Create Table'] . ";\n\n";
                
                // Données de la table
                $result = $db->query("SELECT * FROM `$table`");
                $rows = $result->fetchAll(PDO::FETCH_ASSOC);
                
                if (!empty($rows)) {
                    $output .= "-- Data for table: $table\n";
                    
                    foreach ($rows as $row) {
                        $values = array_map(function($value) use ($db) {
                            if ($value === null) {
                                return 'NULL';
                            }
                            return $db->quote($value);
                        }, array_values($row));
                        
                        $columns = '`' . implode('`, `', array_keys($row)) . '`';
                        $output .= "INSERT INTO `$table` ($columns) VALUES (" . implode(', ', $values) . ");\n";
                    }
                    
                    $output .= "\n";
                }
            }
            
            $output .= "SET FOREIGN_KEY_CHECKS=1;\n";
            
            // Écrire le fichier
            file_put_contents($filepath, $output);
            
            Utils::log("Backup créé: $filename par " . session('username'));
            
            $this->jsonResponse([
                'success' => true,
                'message' => 'Sauvegarde créée avec succès',
                'filename' => $filename
            ]);
            
        } catch (Exception $e) {
            Utils::log("Erreur backup: " . $e->getMessage(), 'ERROR');
            $this->jsonResponse([
                'success' => false,
                'message' => 'Erreur lors de la sauvegarde: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Restaurer une sauvegarde
     */
    public function restoreDatabase() {
        try {
            if (!isset($_FILES['backup_file']) || $_FILES['backup_file']['error'] !== UPLOAD_ERR_OK) {
                $this->jsonResponse(['success' => false, 'message' => 'Aucun fichier fourni']);
            }
            
            $file = $_FILES['backup_file'];
            
            // Vérifier l'extension
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            if ($ext !== 'sql') {
                $this->jsonResponse(['success' => false, 'message' => 'Format de fichier invalide. Seuls les fichiers .sql sont acceptés']);
            }
            
            // Lire le contenu du fichier
            $sql = file_get_contents($file['tmp_name']);
            
            if (empty($sql)) {
                $this->jsonResponse(['success' => false, 'message' => 'Fichier vide']);
            }
            
            // Exécuter le SQL
            $db = Database::getInstance()->getConnection();
            
            // Désactiver les vérifications de clés étrangères
            $db->exec("SET FOREIGN_KEY_CHECKS=0");
            
            // Diviser le SQL en requêtes individuelles
            $queries = array_filter(
                array_map('trim', explode(';', $sql)),
                function($query) {
                    return !empty($query) && !preg_match('/^--/', $query);
                }
            );
            
            // Exécuter chaque requête
            foreach ($queries as $query) {
                if (!empty($query)) {
                    $db->exec($query);
                }
            }
            
            // Réactiver les vérifications
            $db->exec("SET FOREIGN_KEY_CHECKS=1");
            
            Utils::log("Base de données restaurée par " . session('username'));
            
            $this->jsonResponse([
                'success' => true,
                'message' => 'Base de données restaurée avec succès'
            ]);
            
        } catch (Exception $e) {
            Utils::log("Erreur restauration: " . $e->getMessage(), 'ERROR');
            $this->jsonResponse([
                'success' => false,
                'message' => 'Erreur lors de la restauration: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Télécharger un backup
     */
    public function downloadBackup($filename) {
        $filepath = $this->backupDir . '/' . basename($filename);
        
        if (!file_exists($filepath)) {
            $_SESSION['error'] = 'Fichier introuvable';
            redirect(base_url('admin/parametres'));
        }
        
        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
        header('Content-Length: ' . filesize($filepath));
        readfile($filepath);
        exit;
    }
    
    /**
     * Vider le cache
     */
    public function clearCache() {
        try {
            $cacheDir = __DIR__ . '/../../cache';
            
            if (is_dir($cacheDir)) {
                $files = glob($cacheDir . '/*');
                foreach ($files as $file) {
                    if (is_file($file)) {
                        unlink($file);
                    }
                }
            }
            
            Utils::log("Cache vidé par " . session('username'));
            
            $this->jsonResponse([
                'success' => true,
                'message' => 'Cache vidé avec succès'
            ]);
            
        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Sauvegarder les paramètres de maintenance
     */
    public function saveMaintenance() {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            $settings = $this->loadSettings();
            $settings['maintenance_mode'] = !empty($input['mode']);
            $settings['maintenance_message'] = Utils::sanitize($input['message'] ?? '');
            
            if ($this->saveSettings($settings)) {
                $this->jsonResponse([
                    'success' => true,
                    'message' => 'Paramètres de maintenance enregistrés'
                ]);
            } else {
                $this->jsonResponse([
                    'success' => false,
                    'message' => 'Erreur lors de l\'enregistrement'
                ]);
            }
            
        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Charger les paramètres
     */
    private function loadSettings() {
        if (!file_exists($this->settingsFile)) {
            return $this->getDefaultSettings();
        }
        
        $content = file_get_contents($this->settingsFile);
        $settings = json_decode($content, true);
        
        return is_array($settings) ? $settings : $this->getDefaultSettings();
    }
    
    /**
     * Sauvegarder les paramètres
     */
    private function saveSettings($settings) {
        $json = json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        return file_put_contents($this->settingsFile, $json) !== false;
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
    
    /**
     * Upload du logo
     */
    private function uploadLogo($file) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        
        if (!in_array($file['type'], $allowedTypes)) {
            return false;
        }
        
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'logo_' . time() . '.' . $ext;
        $destination = $this->uploadsDir . '/' . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $destination)) {
            return $filename;
        }
        
        return false;
    }
    
    /**
     * Liste des backups
     */
    private function getBackupsList() {
        $backups = [];
        
        if (!is_dir($this->backupDir)) {
            return $backups;
        }
        
        $files = glob($this->backupDir . '/*.sql');
        
        foreach ($files as $file) {
            $backups[] = [
                'filename' => basename($file),
                'date' => date('Y-m-d H:i:s', filemtime($file)),
                'size' => filesize($file)
            ];
        }
        
        // Trier par date décroissante
        usort($backups, function($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });
        
        return $backups;
    }
    
    /**
     * Envoyer une réponse JSON
     */
    private function jsonResponse($data) {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}
?>