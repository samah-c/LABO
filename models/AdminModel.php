<?php
require_once __DIR__ . '/Model.php';
// ========================================
// AdminModel.php
// ========================================
class AdminModel extends Model {
    protected $table = 'Admin';
    
    public function getByUserId($userId) {
        $stmt = $this->db->prepare("SELECT * FROM Admin WHERE user_id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }
    
    public function isAdmin($userId) {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM Admin WHERE user_id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetchColumn() > 0;
    }
}