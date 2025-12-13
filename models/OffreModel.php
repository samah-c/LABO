<?php
require_once __DIR__ . '/Model.php';
// ========================================
// OffreModel.php
// ========================================
class OffreModel extends Model {
    protected $table = 'Offre_Et_Opportunite';
    
    public function getActives($limit = null) {
        $sql = "
            SELECT * FROM Offre_Et_Opportunite 
            WHERE statut = 'active'
            AND (date_expiration IS NULL OR date_expiration >= CURDATE())
            ORDER BY date_publication DESC
        ";
        if ($limit) $sql .= " LIMIT $limit";
        return $this->db->query($sql)->fetchAll();
    }
    
    public function getByType($type) {
        $stmt = $this->db->prepare("
            SELECT * FROM Offre_Et_Opportunite 
            WHERE type_offre = ? AND statut = 'active'
        ");
        $stmt->execute([$type]);
        return $stmt->fetchAll();
    }
}