<?php
require_once __DIR__ . '/../config/database.php';

class NotificationModel {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Récupérer une notification par ID
     */
    public function getById($id) {
        $stmt = $this->db->prepare("
            SELECT n.*, u.username as createur_nom
            FROM Notification n
            LEFT JOIN User u ON n.createur_id = u.id
            WHERE n.id = ?
        ");
        
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Créer une notification
     */
    public function create($data) {
        $stmt = $this->db->prepare("
            INSERT INTO Notification (titre, message, type_notification, priorite, 
                                     destinataire_type, destinataire_id, createur_id, lien)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $data['titre'],
            $data['message'],
            $data['type_notification'],
            $data['priorite'] ?? 'normale',
            $data['destinataire_type'],
            $data['destinataire_id'] ?? null,
            $data['createur_id'],
            $data['lien'] ?? null
        ]);
        
        return $this->db->lastInsertId();
    }
    
    /**
     * Récupérer les notifications pour un utilisateur spécifique
     */
    public function getForUser($userId) {
        // D'abord, récupérer le rôle de l'utilisateur
        $stmtRole = $this->db->prepare("SELECT role FROM User WHERE id = ?");
        $stmtRole->execute([$userId]);
        $user = $stmtRole->fetch(PDO::FETCH_ASSOC);
        $userRole = $user['role'] ?? 'membre';
        
        $stmt = $this->db->prepare("
            SELECT n.*, 
                   u.username as createur_nom,
                   nl.date_lecture as lu,
                   CASE 
                       WHEN nl.id IS NULL THEN 0 
                       ELSE 1 
                   END as est_lu
            FROM Notification n
            LEFT JOIN User u ON n.createur_id = u.id
            LEFT JOIN Notification_Lecture nl ON n.id = nl.notification_id AND nl.user_id = ?
            WHERE n.destinataire_type = 'tous'
               OR (n.destinataire_type = 'individuel' AND n.destinataire_id = ?)
               OR (n.destinataire_type = 'role' AND ? = ?)
            ORDER BY n.date_creation DESC
            LIMIT 50
        ");
        
        $stmt->execute([$userId, $userId, $userRole, $userRole]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Compter les notifications non lues
     */
    public function countUnread($userId) {
        // Récupérer le rôle de l'utilisateur
        $stmtRole = $this->db->prepare("SELECT role FROM User WHERE id = ?");
        $stmtRole->execute([$userId]);
        $user = $stmtRole->fetch(PDO::FETCH_ASSOC);
        $userRole = $user['role'] ?? 'membre';
        
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count
            FROM Notification n
            LEFT JOIN Notification_Lecture nl ON n.id = nl.notification_id AND nl.user_id = ?
            WHERE nl.id IS NULL
              AND (n.destinataire_type = 'tous'
                   OR (n.destinataire_type = 'individuel' AND n.destinataire_id = ?)
                   OR (n.destinataire_type = 'role' AND ? = ?))
        ");
        
        $stmt->execute([$userId, $userId, $userRole, $userRole]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$result['count'];
    }
    
    /**
     * Marquer une notification comme lue
     */
    public function markAsRead($notificationId, $userId) {
        $stmt = $this->db->prepare("
            INSERT IGNORE INTO Notification_Lecture (notification_id, user_id)
            VALUES (?, ?)
        ");
        
        return $stmt->execute([$notificationId, $userId]);
    }
    
    /**
     * Marquer toutes les notifications comme lues
     */
    public function markAllAsRead($userId) {
        $notifications = $this->getForUser($userId);
        
        foreach ($notifications as $notif) {
            if (!$notif['est_lu']) {
                $this->markAsRead($notif['id'], $userId);
            }
        }
        
        return true;
    }
    
    /**
     * Supprimer une notification
     */
    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM Notification WHERE id = ?");
        return $stmt->execute([$id]);
    }
    
    /**
     * Récupérer toutes les notifications
     */
    public function getAll() {
        $stmt = $this->db->query("
            SELECT n.*, u.username as createur_nom
            FROM Notification n
            LEFT JOIN User u ON n.createur_id = u.id
            ORDER BY n.date_creation DESC
        ");
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Récupérer la connexion (utile pour d'autres opérations)
     */
    public function getConnection() {
        return $this->db;
    }
}