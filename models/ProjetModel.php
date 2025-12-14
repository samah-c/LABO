<?php
require_once __DIR__ . '/Model.php';
// ========================================
// ProjetModel.php - VERSION AMÉLIORÉE
// ========================================
class ProjetModel extends Model {
    protected $table = 'Projet';
    
    /**
     * Récupérer tous les projets avec responsables
     */
    public function getAllWithResponsables() {
        $stmt = $this->db->query("
            SELECT p.*, u.username as responsable_nom,
                   (SELECT COUNT(*) FROM Projet_Membre WHERE projet_id = p.id) as nb_membres
            FROM Projet p
            JOIN Membre m ON p.responsable_id = m.id
            JOIN User u ON m.user_id = u.id
        ");
        return $stmt->fetchAll();
    }
    
    /**
     * Récupérer les projets filtrés - NOUVELLE FONCTION
     */
    public function getAllFiltered($filters = []) {
        $sql = "SELECT p.*, 
                       u.username as responsable_nom,
                       (SELECT COUNT(*) FROM Projet_Membre WHERE projet_id = p.id) as nb_membres
                FROM Projet p
                JOIN Membre m ON p.responsable_id = m.id
                JOIN User u ON m.user_id = u.id
                WHERE 1";
        
        $params = [];
        
        // Filtre par thématique
        if (!empty($filters['thematique'])) {
            $sql .= " AND p.thematique = :thematique";
            $params['thematique'] = $filters['thematique'];
        }
        
        // Filtre par statut
        if (!empty($filters['statut'])) {
            $sql .= " AND p.statut = :statut";
            $params['statut'] = $filters['statut'];
        }
        
        // Filtre de recherche
        if (!empty($filters['search'])) {
            $sql .= " AND (p.titre LIKE :search 
                           OR u.username LIKE :search
                           OR p.thematique LIKE :search
                           OR p.descriptif LIKE :search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }
        
        // Tri par défaut (les plus récents en premier)
        $sql .= " ORDER BY p.date_debut DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Récupérer les projets d'un membre (responsable + participant)
     */
    public function getByMembre($membreId) {
        $stmt = $this->db->prepare("
            SELECT DISTINCT p.*, 
                   u.username as responsable_nom,
                   CASE 
                       WHEN p.responsable_id = ? THEN 'Responsable'
                       ELSE pm.role_projet
                   END as role_dans_projet
            FROM Projet p
            JOIN Membre m ON p.responsable_id = m.id
            JOIN User u ON m.user_id = u.id
            LEFT JOIN Projet_Membre pm ON p.id = pm.projet_id
            WHERE p.responsable_id = ? OR pm.membre_id = ?
            ORDER BY p.date_debut DESC
        ");
        $stmt->execute([$membreId, $membreId, $membreId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Récupérer par thématique
     */
    public function getByThematique($thematique) {
        $stmt = $this->db->prepare("
            SELECT p.*, u.username as responsable_nom
            FROM Projet p
            JOIN Membre m ON p.responsable_id = m.id
            JOIN User u ON m.user_id = u.id
            WHERE p.thematique = ?
        ");
        $stmt->execute([$thematique]);
        return $stmt->fetchAll();
    }
    
    /**
     * Récupérer les membres d'un projet
     */
    public function getMembres($projetId) {
        $stmt = $this->db->prepare("
            SELECT m.*, u.username, pm.role_projet
            FROM Projet_Membre pm
            JOIN Membre m ON pm.membre_id = m.id
            JOIN User u ON m.user_id = u.id
            WHERE pm.projet_id = ?
        ");
        $stmt->execute([$projetId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Récupérer les publications d'un projet
     */
    public function getPublications($projetId) {
        $stmt = $this->db->prepare("SELECT * FROM Publication WHERE projet_id = ?");
        $stmt->execute([$projetId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Récupérer les thématiques disponibles
     */
    public function getThematiques() {
        $stmt = $this->db->query("SELECT DISTINCT thematique FROM Projet ORDER BY thematique");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    /**
     * Récupérer les statuts disponibles
     */
    public function getStatuts() {
        $stmt = $this->db->query("SELECT DISTINCT statut FROM Projet ORDER BY statut");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    /**
     * Vérifier si un membre participe à un projet
     */
    public function membreParticipe($projetId, $membreId) {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) 
            FROM Projet p
            LEFT JOIN Projet_Membre pm ON p.id = pm.projet_id
            WHERE p.id = ? AND (p.responsable_id = ? OR pm.membre_id = ?)
        ");
        $stmt->execute([$projetId, $membreId, $membreId]);
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Ajouter un membre à un projet
     */
    public function addMembre($projetId, $membreId, $role = 'Participant') {
        $stmt = $this->db->prepare("
            INSERT INTO Projet_Membre (projet_id, membre_id, role_projet)
            VALUES (?, ?, ?)
        ");
        return $stmt->execute([$projetId, $membreId, $role]);
    }
    
    /**
     * Retirer un membre d'un projet
     */
    public function removeMembre($projetId, $membreId) {
        $stmt = $this->db->prepare("
            DELETE FROM Projet_Membre 
            WHERE projet_id = ? AND membre_id = ?
        ");
        return $stmt->execute([$projetId, $membreId]);
    }
}
?>