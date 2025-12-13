<?php
/**
 * AdminApiController.php - API REST pour le dashboard admin
 * À créer dans : controllers/admin/AdminApiController.php
 */

require_once __DIR__ . '/../../models/AdminModel.php';
require_once __DIR__ . '/../../models/MembreModel.php';
require_once __DIR__ . '/../../models/UserModel.php';
require_once __DIR__ . '/../../models/ProjetModel.php';
require_once __DIR__ . '/../../models/PublicationModel.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../auth/AuthController.php';

class AdminApiController {
    private $userModel;
    private $membreModel;
    private $projetModel;
    private $publicationModel;
    
    public function __construct() {
        AuthController::requireAdmin();
        $this->userModel = new UserModel();
        $this->membreModel = new MembreModel();
        $this->projetModel = new ProjetModel();
        $this->publicationModel = new PublicationModel();
    }
    
    /**
     * Obtenir les statistiques en temps réel
     */
    public function getStats() {
        $this->verifyAjaxRequest();
        
        try {
            $stats = [
                'total_users' => count($this->userModel->getAll()),
                'total_membres' => count($this->membreModel->getAll()),
                'total_projets' => count($this->projetModel->getAll()),
                'total_publications' => count($this->publicationModel->getAll()),
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
            $this->sendJsonResponse([
                'success' => true,
                'data' => $stats
            ]);
            
        } catch (Exception $e) {
            $this->sendJsonError('Erreur lors de la récupération des stats', 500);
        }
    }
    
    /**
     * Recherche globale
     */
    public function search() {
        $this->verifyAjaxRequest();
        
        $input = json_decode(file_get_contents('php://input'), true);
        $query = trim($input['query'] ?? '');
        
        if (empty($query)) {
            $this->sendJsonResponse([
                'success' => true,
                'results' => []
            ]);
        }
        
        try {
            $results = [];
            
            // Rechercher dans les utilisateurs
            $users = $this->searchUsers($query);
            $results = array_merge($results, $users);
            
            // Rechercher dans les projets
            $projets = $this->searchProjets($query);
            $results = array_merge($results, $projets);
            
            // Rechercher dans les publications
            $publications = $this->searchPublications($query);
            $results = array_merge($results, $publications);
            
            $this->sendJsonResponse([
                'success' => true,
                'results' => $results,
                'total' => count($results)
            ]);
            
        } catch (Exception $e) {
            $this->sendJsonError('Erreur de recherche', 500);
        }
    }
    
    /**
     * Rechercher des utilisateurs
     */
    private function searchUsers($query) {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            SELECT id, username, email, role 
            FROM User 
            WHERE username LIKE ? OR email LIKE ?
            LIMIT 5
        ");
        
        $searchTerm = "%$query%";
        $stmt->execute([$searchTerm, $searchTerm]);
        $users = $stmt->fetchAll();
        
        return array_map(function($user) {
            return [
                'type' => 'user',
                'title' => $user['username'],
                'description' => $user['email'] . ' - ' . ucfirst($user['role']),
                'url' => '/TDW_project/admin/users?id=' . $user['id']
            ];
        }, $users);
    }
    
    /**
     * Rechercher des projets
     */
    private function searchProjets($query) {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            SELECT id, titre, LEFT(descriptif, 100) as descriptif
            FROM Projet 
            WHERE titre LIKE ? OR descriptif LIKE ?
            LIMIT 5
        ");
        
        $searchTerm = "%$query%";
        $stmt->execute([$searchTerm, $searchTerm]);
        $projets = $stmt->fetchAll();
        
        return array_map(function($projet) {
            return [
                'type' => 'projet',
                'title' => $projet['titre'],
                'description' => $projet['descriptif'],
                'url' => '/TDW_project/admin/projets?id=' . $projet['id']
            ];
        }, $projets);
    }
    
    /**
     * Rechercher des publications
     */
    private function searchPublications($query) {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            SELECT id, titre, LEFT(resume, 100) as resume
            FROM Publication 
            WHERE titre LIKE ? OR resume LIKE ?
            LIMIT 5
        ");
        
        $searchTerm = "%$query%";
        $stmt->execute([$searchTerm, $searchTerm]);
        $publications = $stmt->fetchAll();
        
        return array_map(function($pub) {
            return [
                'type' => 'publication',
                'title' => $pub['titre'],
                'description' => $pub['resume'],
                'url' => '/TDW_project/admin/publications?id=' . $pub['id']
            ];
        }, $publications);
    }
    
    /**
     * Obtenir les notifications récentes
     */
    public function getNotifications() {
        $this->verifyAjaxRequest();
        
        try {
            // Simuler des notifications (à remplacer par une vraie table)
            $notifications = [
                [
                    'message' => 'Nouveau membre inscrit',
                    'type' => 'info',
                    'timestamp' => date('Y-m-d H:i:s')
                ]
            ];
            
            $this->sendJsonResponse([
                'success' => true,
                'notifications' => $notifications
            ]);
            
        } catch (Exception $e) {
            $this->sendJsonError('Erreur notifications', 500);
        }
    }
    
    /**
     * Vérifier que c'est une requête AJAX
     */
    private function verifyAjaxRequest() {
        if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
            $this->sendJsonError('Requête non autorisée', 403);
        }
    }
    
    /**
     * Envoyer une réponse JSON
     */
    private function sendJsonResponse($data) {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
    
    /**
     * Envoyer une erreur JSON
     */
    private function sendJsonError($message, $code = 400) {
        http_response_code($code);
        $this->sendJsonResponse([
            'success' => false,
            'error' => $message
        ]);
    }
}
?>