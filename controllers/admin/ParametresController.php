<?php
/**
 * ParametresController.php - Version corrigée
 * Corrections: Buffer de sortie propre + gestion du cache
 */

require_once __DIR__ . '/../../lib/helpers.php';
require_once __DIR__ . '/../auth/AuthController.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../views/admin/parametres/ParametresView.php';

class ParametresController {
    private $settingsFile;
    private $backupDir;
    private $uploadsDir;
    private $cacheDir;
    
    public function __construct() {
        AuthController::requireAdmin();
        $this->settingsFile = __DIR__ . '/../../config/settings.json';
        $this->backupDir = __DIR__ . '/../../backups';
        $this->uploadsDir = __DIR__ . '/../../uploads/logo';
        $this->cacheDir = __DIR__ . '/../../backups';
        
        // Créer les dossiers s'ils n'existent pas
        foreach ([$this->backupDir, $this->uploadsDir, $this->cacheDir] as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
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
                    $data['lab_logo'] = $logo;
                } else {
                    setError('Erreur lors de l\'upload du logo');
                    redirect(base_url('admin/parametres'));
                    return;
                }
            } else {
                $data['lab_logo'] = $settings['lab_logo'] ?? null;
            }
            
            $settings = array_merge($settings, $data);
            
            if ($this->saveSettings($settings)) {
                setSuccess('Paramètres généraux enregistrés avec succès');
                Utils::log("Paramètres généraux mis à jour par " . session('username'));
            } else {
                setError('Erreur lors de l\'enregistrement');
            }
            
        } catch (Exception $e) {
            Utils::log("Erreur sauvegarde paramètres: " . $e->getMessage(), 'ERROR');
            setError('Erreur: ' . $e->getMessage());
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
                setSuccess('Réseaux sociaux enregistrés avec succès');
            } else {
                setError('Erreur lors de l\'enregistrement');
            }
            
        } catch (Exception $e) {
            setError('Erreur: ' . $e->getMessage());
        }
        
        redirect(base_url('admin/parametres'));
    }
    
    /**
     * Créer une sauvegarde de la base de données
     * VERSION CORRIGÉE - Buffer de sortie propre
     */
    public function backupDatabase() {
        // CRITIQUE: Nettoyer TOUT buffer de sortie existant
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        // Démarrer un nouveau buffer propre
        ob_start();
        
        try {
            $filename = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
            $filepath = $this->backupDir . '/' . $filename;
            
            // Vérifier que le dossier existe et est accessible
            if (!is_dir($this->backupDir)) {
                if (!mkdir($this->backupDir, 0755, true)) {
                    throw new Exception("Impossible de créer le dossier de sauvegarde");
                }
            }
            
            if (!is_writable($this->backupDir)) {
                throw new Exception("Le dossier de sauvegarde n'est pas accessible en écriture");
            }
            
            // Connexion à la base de données
            $db = Database::getInstance()->getConnection();
            
            // Récupérer toutes les tables
            $tables = [];
            $result = $db->query("SHOW TABLES");
            while ($row = $result->fetch(PDO::FETCH_NUM)) {
                $tables[] = $row[0];
            }
            
            if (empty($tables)) {
                throw new Exception("Aucune table trouvée dans la base de données");
            }
            
            // Créer le contenu du backup
            $output = "-- Backup Database\n";
            $output .= "-- Date: " . date('Y-m-d H:i:s') . "\n";
            $output .= "-- Generated by: " . session('username') . "\n\n";
            $output .= "SET FOREIGN_KEY_CHECKS=0;\n";
            $output .= "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n";
            $output .= "SET time_zone = '+00:00';\n\n";
            
            foreach ($tables as $table) {
                try {
                    // Structure de la table
                    $result = $db->query("SHOW CREATE TABLE `$table`");
                    $row = $result->fetch(PDO::FETCH_ASSOC);
                    
                    $output .= "\n-- --------------------------------------------------------\n";
                    $output .= "-- Table: $table\n";
                    $output .= "-- --------------------------------------------------------\n\n";
                    $output .= "DROP TABLE IF EXISTS `$table`;\n";
                    $output .= $row['Create Table'] . ";\n\n";
                    
                    // Données de la table
                    $result = $db->query("SELECT * FROM `$table`");
                    $rows = $result->fetchAll(PDO::FETCH_ASSOC);
                    
                    if (!empty($rows)) {
                        $output .= "-- Data for table: $table\n";
                        $output .= "LOCK TABLES `$table` WRITE;\n";
                        
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
                        
                        $output .= "UNLOCK TABLES;\n\n";
                    }
                } catch (Exception $e) {
                    $output .= "-- Error with table $table: " . $e->getMessage() . "\n\n";
                }
            }
            
            $output .= "SET FOREIGN_KEY_CHECKS=1;\n";
            
            // Écrire le fichier
            $bytesWritten = file_put_contents($filepath, $output);
            
            if ($bytesWritten === false) {
                throw new Exception("Impossible d'écrire le fichier de sauvegarde");
            }
            
            Utils::log("Backup créé: $filename (" . $this->formatBytes($bytesWritten) . ") par " . session('username'));
            
            // Nettoyer le buffer avant d'envoyer la réponse
            ob_end_clean();
            
            // Envoyer UNIQUEMENT du JSON
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'Sauvegarde créée avec succès',
                'filename' => $filename,
                'size' => $bytesWritten,
                'size_formatted' => $this->formatBytes($bytesWritten)
            ], JSON_UNESCAPED_UNICODE);
            exit;
            
        } catch (Exception $e) {
            error_log("Erreur backup: " . $e->getMessage());
            Utils::log("Erreur backup: " . $e->getMessage(), 'ERROR');
            
            // Nettoyer le buffer avant d'envoyer l'erreur
            ob_end_clean();
            
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
    
    /**
     * Restaurer une sauvegarde
     */
    public function restoreDatabase() {
        // Nettoyer le buffer
        while (ob_get_level()) {
            ob_end_clean();
        }
        ob_start();
        
        try {
            if (!isset($_FILES['backup_file']) || $_FILES['backup_file']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('Aucun fichier fourni');
            }
            
            $file = $_FILES['backup_file'];
            
            // Vérifier l'extension
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            if ($ext !== 'sql') {
                throw new Exception('Format de fichier invalide. Seuls les fichiers .sql sont acceptés');
            }
            
            // Lire le contenu du fichier
            $sql = file_get_contents($file['tmp_name']);
            
            if (empty($sql)) {
                throw new Exception('Fichier vide');
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
            
            ob_end_clean();
            
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => true,
                'message' => 'Base de données restaurée avec succès'
            ], JSON_UNESCAPED_UNICODE);
            exit;
            
        } catch (Exception $e) {
            Utils::log("Erreur restauration: " . $e->getMessage(), 'ERROR');
            
            ob_end_clean();
            
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
    
    /**
     * Télécharger un backup
     */
    public function downloadBackup($filename) {
        $filepath = $this->backupDir . '/' . basename($filename);
        
        if (!file_exists($filepath)) {
            setError('Fichier introuvable');
            redirect(base_url('admin/parametres'));
            return;
        }
        
        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
        header('Content-Length: ' . filesize($filepath));
        readfile($filepath);
        exit;
    }
    
    /**
     * Vider le cache - VERSION CORRIGÉE
     */
    public function clearCache() {
        // Nettoyer le buffer
        while (ob_get_level()) {
            ob_end_clean();
        }
        ob_start();
        
        try {
            $deletedFiles = 0;
            $errors = [];
            
            // Vider le dossier cache
            if (is_dir($this->cacheDir)) {
                $files = glob($this->cacheDir . '/*');
                foreach ($files as $file) {
                    if (is_file($file)) {
                        if (unlink($file)) {
                            $deletedFiles++;
                        } else {
                            $errors[] = basename($file);
                        }
                    }
                }
            }
            
            // Vider les sessions si nécessaire
            $sessionPath = session_save_path();
            if (!empty($sessionPath) && is_dir($sessionPath)) {
                $sessionFiles = glob($sessionPath . '/sess_*');
                foreach ($sessionFiles as $file) {
                    // Ne pas supprimer la session actuelle
                    if (is_file($file) && basename($file) !== 'sess_' . session_id()) {
                        if (@unlink($file)) {
                            $deletedFiles++;
                        }
                    }
                }
            }
            
            // Vider le cache PHP opcode si disponible
            if (function_exists('opcache_reset')) {
                opcache_reset();
            }
            
            Utils::log("Cache vidé: $deletedFiles fichiers supprimés par " . session('username'));
            
            ob_end_clean();
            
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => true,
                'message' => "Cache vidé avec succès ($deletedFiles fichiers supprimés)",
                'deleted_files' => $deletedFiles,
                'errors' => $errors
            ], JSON_UNESCAPED_UNICODE);
            exit;
            
        } catch (Exception $e) {
            Utils::log("Erreur nettoyage cache: " . $e->getMessage(), 'ERROR');
            
            ob_end_clean();
            
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
    
    /**
     * Sauvegarder les paramètres de maintenance
     */
    public function saveMaintenance() {
        // Nettoyer le buffer
        while (ob_get_level()) {
            ob_end_clean();
        }
        ob_start();
        
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            $settings = $this->loadSettings();
            $settings['maintenance_mode'] = !empty($input['mode']);
            $settings['maintenance_message'] = Utils::sanitize($input['message'] ?? 'Site en maintenance, revenez bientôt.');
            
            if ($this->saveSettings($settings)) {
                Utils::log("Mode maintenance: " . ($settings['maintenance_mode'] ? 'activé' : 'désactivé') . " par " . session('username'));
                
                ob_end_clean();
                
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'success' => true,
                    'message' => 'Paramètres de maintenance enregistrés'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            } else {
                throw new Exception('Erreur lors de l\'enregistrement');
            }
            
        } catch (Exception $e) {
            ob_end_clean();
            
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
            exit;
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
    
    // NOUVEAU : Supprimer tous les fichiers existants dans le dossier logo
    $files = glob($this->uploadsDir . '/*');
    foreach ($files as $oldFile) {
        if (is_file($oldFile)) {
            unlink($oldFile);
        }
    }
    
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'logo.' . $ext; // Nom simple et fixe
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
     * Formater les bytes en taille lisible
     */
    private function formatBytes($bytes, $precision = 2) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
?>