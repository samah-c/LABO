<?php
require_once __DIR__ . '/Model.php';
// ========================================
// RechercheModel.php
// ========================================
class RechercheModel extends Model {
    protected $table = 'Recherche';
    
    public function getByMembre($membreId) {
        $stmt = $this->db->prepare("SELECT * FROM Recherche WHERE membre_id = ?");
        $stmt->execute([$membreId]);
        return $stmt->fetchAll();
    }
}