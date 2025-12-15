<?php
require_once __DIR__ . '/Model.php';
// ========================================
// ProjetModel.php - VERSION AMÉLIORÉE
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
     * Récupérer les projets filtrés - NOUVELLE FONCTION
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
            WHERE 1";
    
    $params = [];
    
    // Filtre par thématique
    if (!empty($filters['thematique'])) {
        $sql .= " AND p.thematique = :thematique";
        $params['thematique'] = $filters['thematique'];
    }
    
    // Filtre par status
    if (!empty($filters['status'])) {
        $sql .= " AND p.status = :status";
        $params['status'] = $filters['status'];
    }
    
    // Filtre de recherche
    if (!empty($filters['search'])) {
        $sql .= " AND (p.titre LIKE :search 
                       OR u.username LIKE :search
                       OR p.thematique LIKE :search
                       OR p.description LIKE :search)";
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
     * Récupérer les statuss disponibles
     */
    public function getstatuss() {
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
}
?>