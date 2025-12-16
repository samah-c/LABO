<?php
require_once __DIR__ . '/Model.php';
// ========================================
// EvenementModel.php
// ========================================
class EvenementModel extends Model {
    protected $table = 'Evenement';
    
    public function getAllWithOrganisateurs() {
        $stmt = $this->db->query("
            SELECT e.*, u.username as organisateur_nom
            FROM Evenement e
            LEFT JOIN Membre m ON e.organisateur_id = m.id
            LEFT JOIN User u ON m.user_id = u.id
        ");
        return $stmt->fetchAll();
    }
    
    public function getUpcoming($limit = null) {
        $sql = "
            SELECT e.*, u.username as organisateur_nom
            FROM Evenement e
            LEFT JOIN Membre m ON e.organisateur_id = m.id
            LEFT JOIN User u ON m.user_id = u.id
            WHERE e.date_evenement >= NOW()
            ORDER BY e.date_evenement ASC
        ";
        if ($limit) $sql .= " LIMIT $limit";
        return $this->db->query($sql)->fetchAll();
    }
    
    public function getByType($type) {
        $stmt = $this->db->prepare("
            SELECT e.*, u.username as organisateur_nom
            FROM Evenement e
            LEFT JOIN Membre m ON e.organisateur_id = m.id
            LEFT JOIN User u ON m.user_id = u.id
            WHERE e.type_evenement = ?
        ");
        $stmt->execute([$type]);
        return $stmt->fetchAll();
    }

    public function countUpcoming() {
    $stmt = $this->db->prepare("
        SELECT COUNT(*) as total
        FROM Evenement
        WHERE date_evenement >= CURDATE()
    ");
    $stmt->execute();
    return $stmt->fetch()['total'];
}

/**
 * Récupérer les événements récents
 */
public function getRecent($limit = 5) {
    $stmt = $this->db->prepare("
        SELECT e.*, u.username AS organisateur_nom
        FROM Evenement e
        LEFT JOIN Membre m ON e.organisateur_id = m.id
        LEFT JOIN User u ON m.user_id = u.id
        ORDER BY e.date_evenement DESC
        LIMIT ?
    ");
    $stmt->execute([intval($limit)]);
    return $stmt->fetchAll();
}

}