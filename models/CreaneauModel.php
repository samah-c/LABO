<?php
require_once __DIR__ . '/Model.php';
// ========================================
// CreneauModel.php
// ========================================
class CreneauModel extends Model {
    protected $table = 'Creneau';
    
    public function createReservation($equipementId, $membreId, $dateDebut, $dateFin, $motif = null) {
        if (!$this->checkDisponibilite($equipementId, $dateDebut, $dateFin)) {
            return false;
        }
        
        $data = [
            'equipement_id' => $equipementId,
            'membre_id' => $membreId,
            'date_debut' => $dateDebut,
            'date_fin' => $dateFin,
            'statut' => 'confirme',
            'motif' => $motif
        ];
        return $this->create($data);
    }
    
    private function checkDisponibilite($equipementId, $dateDebut, $dateFin) {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM Creneau
            WHERE equipement_id = ? AND statut = 'confirme'
            AND ((date_debut BETWEEN ? AND ?)
                OR (date_fin BETWEEN ? AND ?)
                OR (date_debut <= ? AND date_fin >= ?))
        ");
        $stmt->execute([$equipementId, $dateDebut, $dateFin, $dateDebut, $dateFin, $dateDebut, $dateFin]);
        return $stmt->fetchColumn() == 0;
    }
    
    public function getByMembre($membreId) {
        $stmt = $this->db->prepare("
            SELECT c.*, e.nom as equipement_nom
            FROM Creneau c
            JOIN Equipement e ON c.equipement_id = e.id
            WHERE c.membre_id = ?
        ");
        $stmt->execute([$membreId]);
        return $stmt->fetchAll();
    }
    
    public function getUpcoming($membreId = null) {
        $sql = "
            SELECT c.*, e.nom as equipement_nom, u.username
            FROM Creneau c
            JOIN Equipement e ON c.equipement_id = e.id
            JOIN Membre m ON c.membre_id = m.id
            JOIN User u ON m.user_id = u.id
            WHERE c.date_debut >= NOW()
        ";
        $params = [];
        if ($membreId) {
            $sql .= " AND c.membre_id = ?";
            $params[] = $membreId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}