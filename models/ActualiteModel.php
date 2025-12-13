<?php
require_once __DIR__ . '/Model.php';
// ========================================
// ActualiteModel.php
// ========================================
class ActualiteModel extends Model {
    
    public function getAllScientifiques($limit = null) {
        $sql = "
            SELECT a.*, u.username as auteur_nom
            FROM Actualite_Scientifique a
            LEFT JOIN Membre m ON a.auteur_id = m.id
            LEFT JOIN User u ON m.user_id = u.id
            ORDER BY a.date_publication DESC
        ";
        if ($limit) $sql .= " LIMIT $limit";
        return $this->db->query($sql)->fetchAll();
    }
    
    public function getAllLaboratoire($limit = null) {
        $sql = "SELECT * FROM Actualite_Laboratoire ORDER BY date_publication DESC";
        if ($limit) $sql .= " LIMIT $limit";
        return $this->db->query($sql)->fetchAll();
    }
    
    //Fusionne actualités scientifiques + labo (pour carrousel accueil)
    public function getDiaporama($limit = 5) {
        $stmt = $this->db->query("
            (SELECT 'scientifique' as source, id, titre, 
                    LEFT(contenu, 200) as description, image, date_publication
             FROM Actualite_Scientifique ORDER BY date_publication DESC LIMIT $limit)
            UNION ALL
            (SELECT 'laboratoire' as source, id, titre, 
                    LEFT(descriptif, 200) as description, image, date_publication
             FROM Actualite_Laboratoire ORDER BY date_publication DESC LIMIT $limit)
            ORDER BY date_publication DESC LIMIT $limit
        ");
        return $stmt->fetchAll();
    }
}
