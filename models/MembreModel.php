<?php
require_once __DIR__ . '/Model.php';
// ========================================
// MembreModel.php
// ========================================
class MembreModel extends Model {
    protected $table = 'Membre';
    
    public function getAllMembresWithUser() {
        $stmt = $this->db->query("
            SELECT m.*, u.username, u.email, e.nom as equipe_nom
            FROM Membre m
            JOIN User u ON m.user_id = u.id
            LEFT JOIN Equipe e ON m.equipe_id = e.id
        ");
        return $stmt->fetchAll();
    }

     /**
     * Récupérer les membres sans équipe (disponibles)
     */
    public function getMembresDisponibles() {
        $stmt = $this->db->prepare("
            SELECT m.*, u.username, u.email 
            FROM Membre m
            JOIN User u ON m.user_id = u.id
            WHERE m.equipe_id IS NULL
            ORDER BY u.username
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getByEquipe($equipeId) {
        $stmt = $this->db->prepare("
            SELECT m.*, u.username, u.email
            FROM Membre m
            JOIN User u ON m.user_id = u.id
            WHERE m.equipe_id = ?
        ");
        $stmt->execute([$equipeId]);
        return $stmt->fetchAll();
    }
    
    public function getByUserId($userId) {
        $stmt = $this->db->prepare("SELECT * FROM Membre WHERE user_id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }
}
