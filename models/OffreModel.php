<?php
require_once __DIR__ . '/Model.php';

class OffreModel extends Model {
    protected $table = 'Offre_Et_Opportunite';
    
    public function getActives($limit = null) {
        $sql = "
            SELECT * FROM Offre_Et_Opportunite 
            WHERE statut = 'active'
            ORDER BY date_publication DESC
        ";
        if ($limit) $sql .= " LIMIT $limit";
        return $this->db->query($sql)->fetchAll();
    }
    
    public function getByType($type) {
        $stmt = $this->db->prepare("
            SELECT * FROM Offre_Et_Opportunite 
            WHERE type_offre = ? AND statut = 'active'
            ORDER BY date_publication DESC
        ");
        $stmt->execute([$type]);
        return $stmt->fetchAll();
    }
    
    public function getById($id) {
        $stmt = $this->db->prepare("
            SELECT * FROM Offre_Et_Opportunite 
            WHERE id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    public function getAll() {
        $sql = "SELECT * FROM Offre_Et_Opportunite ORDER BY date_publication DESC";
        return $this->db->query($sql)->fetchAll();
    }
}