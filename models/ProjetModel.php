<?php
require_once __DIR__ . '/Model.php';
// ========================================
// ProjetModel.php - VERSION CORRIGÉE
// ========================================
class ProjetModel extends Model {
    protected $table = 'Projet';

    public function getById($id) {
        $stmt = $this->db->prepare("
            SELECT p.*,
                   u.username as responsable_username
            FROM Projet p
            LEFT JOIN Membre m ON p.responsable_id = m.id
            LEFT JOIN User u ON m.user_id = u.id
            WHERE p.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Récupérer tous les projets avec responsables
     */
   public function getAllWithResponsables() {
    $stmt = $this->db->query("
        SELECT p.id,
               p.titre,
               p.description,
               p.thematique,
               p.status,
               p.date_debut,
               p.date_fin,
               p.type_financement,
               p.responsable_id,
               u.username as responsable_nom,
               (SELECT COUNT(*) FROM Projet_Membre WHERE projet_id = p.id) as nb_membres
        FROM Projet p
        LEFT JOIN Membre m ON p.responsable_id = m.id
        LEFT JOIN User u ON m.user_id = u.id
        ORDER BY p.date_debut DESC
    ");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
    
    /**
     * Récupérer les projets filtrés
     */
public function getAllFiltered($filters = []) {
    $sql = "SELECT p.id,
                   p.titre,
                   p.description,
                   p.thematique,
                   p.status,
                   p.date_debut,
                   p.date_fin,
                   p.type_financement,
                   p.responsable_id,
                   u.username as responsable_nom,
                   (SELECT COUNT(*) FROM Projet_Membre WHERE projet_id = p.id) as nb_membres
            FROM Projet p
            LEFT JOIN Membre m ON p.responsable_id = m.id
            LEFT JOIN User u ON m.user_id = u.id
            WHERE 1=1";
    
    $params = [];
    
    // Filtre par thématique
    if (!empty($filters['thematique'])) {
        $sql .= " AND p.thematique = :thematique";
        $params['thematique'] = $filters['thematique'];
    }
    
    // Filtre par statut
    if (!empty($filters['status'])) {
        $sql .= " AND p.status = :status";
        $params['status'] = $filters['status'];
    }
    
    // ← AJOUT IMPORTANT : Filtre par année
    if (!empty($filters['annee'])) {
        $sql .= " AND YEAR(p.date_debut) = :annee";
        $params['annee'] = (int)$filters['annee'];
    }
    
    // Filtre de recherche
    if (!empty($filters['search'])) {
        $sql .= " AND (p.titre LIKE :search 
                       OR u.username LIKE :search
                       OR p.thematique LIKE :search
                       OR p.description LIKE :search)";
        $params['search'] = '%' . $filters['search'] . '%';
    }
    
    // Tri par défaut
    $sql .= " ORDER BY p.date_debut DESC";
    
    $stmt = $this->db->prepare($sql);
    $stmt->execute($params);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
    
    /**
     * Récupérer les projets d'un membre (responsable + participant)
     */
    public function getByMembre($membreId, $limit = null) {
        $sql = "SELECT DISTINCT p.*, 
                   u.username as responsable_nom,
                   CASE 
                       WHEN p.responsable_id = :membre_id1 THEN 'Responsable'
                       ELSE pm.role_projet
                   END as role_dans_projet
            FROM Projet p
            JOIN Membre m ON p.responsable_id = m.id
            JOIN User u ON m.user_id = u.id
            LEFT JOIN Projet_Membre pm ON p.id = pm.projet_id
            WHERE p.responsable_id = :membre_id2 OR pm.membre_id = :membre_id3
            ORDER BY p.date_debut DESC";
        
        if ($limit) {
            $sql .= " LIMIT :limit";
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':membre_id1', $membreId, PDO::PARAM_INT);
        $stmt->bindValue(':membre_id2', $membreId, PDO::PARAM_INT);
        $stmt->bindValue(':membre_id3', $membreId, PDO::PARAM_INT);
        
        if ($limit) {
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function countByMembre($membreId) {
        $stmt = $this->db->prepare("
            SELECT COUNT(DISTINCT p.id) as total
            FROM Projet p
            LEFT JOIN Projet_Membre pm ON p.id = pm.projet_id
            WHERE p.responsable_id = ? OR pm.membre_id = ?
        ");
        $stmt->execute([$membreId, $membreId]);
        return $stmt->fetch()['total'];
    }

    public function countByMembreAndStatus($membreId, $statut) {
        $stmt = $this->db->prepare("
            SELECT COUNT(DISTINCT p.id) as total
            FROM Projet p
            LEFT JOIN Projet_Membre pm ON p.id = pm.projet_id
            WHERE (p.responsable_id = ? OR pm.membre_id = ?) AND p.status = ?
        ");
        $stmt->execute([$membreId, $membreId, $statut]);
        return $stmt->fetch()['total'];
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
        $stmt = $this->db->query("SELECT DISTINCT status FROM Projet ORDER BY status");
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

   public function getRecent($limit = 6) {
    $limit = (int) $limit;

    $stmt = $this->db->query("
        SELECT *, thematique as domaine_recherche, status as statut
        FROM Projet
        WHERE status = 'en_cours'
        ORDER BY date_debut DESC
        LIMIT $limit
    ");

    return $stmt->fetchAll();
}

    
    public function getAllPublic() {
        $stmt = $this->db->query("
            SELECT *, thematique as domaine_recherche, status as statut
            FROM Projet
            WHERE status = 'en_cours'
            ORDER BY date_debut DESC
        ");
        return $stmt->fetchAll();
    }
    
    public function getByEquipe($equipeId) {
        $stmt = $this->db->prepare("
            SELECT p.*, p.status as statut
            FROM Projet p
            JOIN Projet_Membre pm ON p.id = pm.projet_id
            JOIN Membre m ON pm.membre_id = m.id
            WHERE m.equipe_id = ?
            GROUP BY p.id
            ORDER BY p.date_debut DESC
        ");
        $stmt->execute([$equipeId]);
        return $stmt->fetchAll();
    }
    
    public function count() {
        $stmt = $this->db->query("SELECT COUNT(*) as total FROM Projet");
        return $stmt->fetch()['total'];
    }

}
?>