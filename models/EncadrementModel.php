<?php
require_once __DIR__ . '/Model.php';
// ========================================
// EncadrementModel.php
// ========================================
class EncadrementModel extends Model {
    protected $table = 'Encadrement';
    
    public function getByEncadrant($encadrantId) {
        $stmt = $this->db->prepare("
            SELECT e.*, u.username as encadre_nom, p.titre as projet_titre
            FROM Encadrement e
            JOIN Membre m ON e.encadre_id = m.id
            JOIN User u ON m.user_id = u.id
            LEFT JOIN Projet p ON e.projet_id = p.id
            WHERE e.encadrant_id = ?
        ");
        $stmt->execute([$encadrantId]);
        return $stmt->fetchAll();
    }
}