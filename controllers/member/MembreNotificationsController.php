<?php
/**
 * MembreNotificationsController.php
 * Contrôleur pour gérer les notifications côté membre
 * À créer dans : controllers/member/MembreNotificationsController.php
 */

require_once __DIR__ . '/../../models/NotificationModel.php';
require_once __DIR__ . '/../../models/MembreModel.php';
require_once __DIR__ . '/../../lib/helpers.php';
require_once __DIR__ . '/../auth/AuthController.php';

class MembreNotificationsController {
    private $notificationModel;
    private $membreModel;
    private $userId;
    
    public function __construct() {
        AuthController::requireMembre();
        
        $this->notificationModel = new NotificationModel();
        $this->membreModel = new MembreModel();
        $this->userId = session('user_id');
    }
    
    /**
     * Récupérer les notifications de l'utilisateur
     */
    public function getUserNotifications() {
        try {
            $notifications = $this->notificationModel->getForUser($this->userId);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'notifications' => $notifications
            ]);
            
        } catch (Exception $e) {
            error_log("Erreur getUserNotifications: " . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Erreur lors du chargement des notifications'
            ]);
        }
    }
    
    /**
     * Compter les notifications non lues
     */
    public function getUnreadCount() {
        try {
            $count = $this->notificationModel->countUnread($this->userId);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'count' => $count
            ]);
            
        } catch (Exception $e) {
            error_log("Erreur getUnreadCount: " . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'count' => 0
            ]);
        }
    }
    
    /**
     * Marquer une notification comme lue
     */
    public function markAsRead($id) {
        try {
            $success = $this->notificationModel->markAsRead($id, $this->userId);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => $success
            ]);
            
        } catch (Exception $e) {
            error_log("Erreur markAsRead: " . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false
            ]);
        }
    }
    
    /**
     * Marquer toutes les notifications comme lues
     */
    public function markAllAsRead() {
        try {
            $success = $this->notificationModel->markAllAsRead($this->userId);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => $success
            ]);
            
        } catch (Exception $e) {
            error_log("Erreur markAllAsRead: " . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false
            ]);
        }
    }
}
