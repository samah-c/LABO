<?php
require_once __DIR__ . '/Model.php';
// ========================================
// VisiteurModel.php
// ========================================
class VisiteurModel extends Model {
    protected $table = 'Visiteur';
    
    public function getByUserId($userId) {
        $stmt = $this->db->prepare("SELECT * FROM Visiteur WHERE user_id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }
}