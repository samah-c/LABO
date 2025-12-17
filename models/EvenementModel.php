<?php
require_once __DIR__ . '/Model.php';

/**
 * EvenementModel.php - Version corrigée
 */
class EvenementModel extends Model {
    protected $table = 'Evenement';
    
    public function getAllWithOrganisateurs() {
        $stmt = $this->db->query("
            SELECT e.*, u.username as organisateur_nom
            FROM Evenement e
            LEFT JOIN Membre m ON e.organisateur_id = m.id
            LEFT JOIN User u ON m.user_id = u.id
            ORDER BY e.date_evenement DESC
        ");
        return $stmt->fetchAll();
    }
    
    /**
     * Récupérer les événements à venir
     * VERSION CORRIGÉE : Si aucun événement futur, retourne les plus récents
     */
    public function getUpcoming($limit = null) {
        try {
            error_log("EvenementModel::getUpcoming($limit) - Début");
            
            // D'abord, essayer de récupérer les événements futurs
            $sql = "
                SELECT e.*, u.username as organisateur_nom
                FROM Evenement e
                LEFT JOIN Membre m ON e.organisateur_id = m.id
                LEFT JOIN User u ON m.user_id = u.id
                WHERE e.date_evenement >= CURDATE()
                ORDER BY e.date_evenement ASC
            ";
            
            if ($limit) {
                $sql .= " LIMIT " . intval($limit);
            }
            
            $stmt = $this->db->query($sql);
            $evenements = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            error_log("Événements futurs trouvés: " . count($evenements));
            
            // Si aucun événement futur, prendre les plus récents (passés ou futurs)
            if (empty($evenements)) {
                error_log("⚠ Aucun événement futur, récupération des plus récents...");
                
                $sql = "
                    SELECT e.*, u.username as organisateur_nom
                    FROM Evenement e
                    LEFT JOIN Membre m ON e.organisateur_id = m.id
                    LEFT JOIN User u ON m.user_id = u.id
                    ORDER BY e.date_evenement DESC
                ";
                
                if ($limit) {
                    $sql .= " LIMIT " . intval($limit);
                }
                
                $stmt = $this->db->query($sql);
                $evenements = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                error_log("Événements récents trouvés: " . count($evenements));
            }
            
            return $evenements;
            
        } catch (Exception $e) {
            error_log("✗ Erreur getUpcoming(): " . $e->getMessage());
            return [];
        }
    }
    
    public function getByType($type) {
        $stmt = $this->db->prepare("
            SELECT e.*, u.username as organisateur_nom
            FROM Evenement e
            LEFT JOIN Membre m ON e.organisateur_id = m.id
            LEFT JOIN User u ON m.user_id = u.id
            WHERE e.type_evenement = ?
            ORDER BY e.date_evenement DESC
        ");
        $stmt->execute([$type]);
        return $stmt->fetchAll();
    }

    public function countUpcoming() {
        try {
            $stmt = $this->db->query("
                SELECT COUNT(*) as total
                FROM Evenement
                WHERE date_evenement >= CURDATE()
            ");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['total'];
        } catch (Exception $e) {
            error_log("Erreur countUpcoming(): " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Récupérer les événements récents
     */
    public function getRecent($limit = 5) {
        try {
            $stmt = $this->db->prepare("
                SELECT e.*, u.username AS organisateur_nom
                FROM Evenement e
                LEFT JOIN Membre m ON e.organisateur_id = m.id
                LEFT JOIN User u ON m.user_id = u.id
                ORDER BY e.date_evenement DESC
                LIMIT ?
            ");
            $stmt->execute([intval($limit)]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Erreur getRecent(): " . $e->getMessage());
            return [];
        }
    }
}